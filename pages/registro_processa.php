<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Carrega $env e getMasterConnection()

// --- Função para exibir mensagens (mantida apenas para uso futuro) ---
function estilizarMensagem($mensagem, $tipo = "info") {
    $cor = $tipo === "sucesso" ? "#27ae60" : ($tipo === "erro" ? "#e74c3c" : "#3498db");
    return "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <title>Cadastro</title>
        <style>
            body { background: #121212; color: #eee; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .caixa { background: #1e1e1e; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 0 12px rgba(0,0,0,0.5); max-width: 400px; }
            h1 { margin-bottom: 20px; color: {$cor}; }
            a { display: inline-block; margin-top: 15px; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; transition: 0.3s; }
            a:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class='caixa'>
            <h1>{$mensagem}</h1>
            <a href='registro.php'>Voltar</a>
        </div>
    </body>
    </html>";
}

// --- Garante que o método é POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
}

// --- Captura os dados do formulário ---
$nome_empresa = trim($_POST['nome'] ?? '');
$email_admin  = trim(strtolower($_POST['email'] ?? ''));
$senha_admin  = $_POST['senha'] ?? '';
$documento    = trim($_POST['documento'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');

// --- Validação dos campos obrigatórios ---
if (empty($nome_empresa) || empty($email_admin) || empty($senha_admin) || empty($documento)) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header('Location: registro.php?msg=campos_vazios');
    exit;
}

// --- Conexão com banco master ---
$master_conn = getMasterConnection();
if (!$master_conn) {
    $_SESSION['erro_registro'] = "Falha ao conectar ao banco de dados principal.";
    header('Location: registro.php?msg=db_master_fail');
    exit;
}

// --- Verifica duplicidade de e-mail ---
$stmt = $master_conn->prepare("SELECT id FROM tenants WHERE admin_email = ?");
$stmt->bind_param("s", $email_admin);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    $master_conn->close();
    $_SESSION['erro_registro'] = "Este e-mail já está em uso por outra conta.";
    header('Location: registro.php?msg=email_duplicado');
    exit;
}
$stmt->close();

// --- Criação de variáveis do novo tenant ---
$tenantId = 0;
$novo_user_id = 0;
$novo_db_nome = 'tenant_' . bin2hex(random_bytes(8));
$novo_db_user = 'user_' . bin2hex(random_bytes(8));
$novo_db_pass = bin2hex(random_bytes(16));
$senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);

try {
    // ✅ Etapa 1: Inserir tenant no banco master
    $stmt_tenant = $master_conn->prepare("
        INSERT INTO tenants (nome_empresa, admin_email, db_host, db_database, db_user, db_password, senha)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_tenant->bind_param(
        "sssssss",
        $nome_empresa,
        $email_admin,
        $env['DB_HOST'],
        $novo_db_nome,
        $novo_db_user,
        $novo_db_pass,
        $senha_hash
    );
    $stmt_tenant->execute();
    $tenantId = $stmt_tenant->insert_id;
    $stmt_tenant->close();

    // ✅ Etapa 2: Inserir o usuário proprietário no banco master
    $stmt_main_user = $master_conn->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel_acesso, tenant_id, tipo_pessoa, perfil, tipo, status, cpf, telefone)
        VALUES (?, ?, ?, 'proprietario', ?, '', 'admin', 'admin', 'ativo', ?, ?)
    ");
    $stmt_main_user->bind_param("sssiss", $nome_empresa, $email_admin, $senha_hash, $tenantId, $documento, $telefone);
    $stmt_main_user->execute();
    $novo_user_id = $master_conn->insert_id;
    $stmt_main_user->close();
    $master_conn->close();

    // ✅ Etapa 3: Criar banco do tenant
    $admin_conn = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    $admin_conn->set_charset("utf8mb4");
    if ($admin_conn->connect_error) {
        throw new Exception("Falha na conexão como admin: " . $admin_conn->connect_error);
    }

    $admin_conn->query("CREATE DATABASE `{$novo_db_nome}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $admin_conn->query("CREATE USER '{$novo_db_user}'@'localhost' IDENTIFIED BY '{$novo_db_pass}'");
    $admin_conn->query("GRANT ALL PRIVILEGES ON `{$novo_db_nome}`.* TO '{$novo_db_user}'@'localhost'");
    $admin_conn->close();

    // ✅ Etapa 4: Executar schema
    $schema_sql = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema_sql === false) throw new Exception("Arquivo schema.sql não encontrado.");

    $tenant_conn = new mysqli($env['DB_HOST'], $novo_db_user, $novo_db_pass, $novo_db_nome);
    $tenant_conn->set_charset("utf8mb4");
    if ($tenant_conn->connect_error) {
        throw new Exception("Falha ao conectar ao banco do tenant.");
    }

    if (!$tenant_conn->multi_query($schema_sql)) {
        throw new Exception("Erro ao criar tabelas do tenant: " . $tenant_conn->error);
    }
    while ($tenant_conn->next_result()) {;} // Limpa o buffer

    // ✅ Etapa 5: Inserir usuário admin no tenant
    $stmt_tenant_user = $tenant_conn->prepare("
        INSERT INTO usuarios (nome, email, cpf, telefone, senha, nivel_acesso, status)
        VALUES (?, ?, ?, ?, ?, 'proprietario', 'ativo')
    ");
    $stmt_tenant_user->bind_param("sssss", $nome_empresa, $email_admin, $documento, $telefone, $senha_hash);
    $stmt_tenant_user->execute();
    $stmt_tenant_user->close();
    $tenant_conn->close();

    // ✅ Mensagem de sucesso
    $_SESSION['registro_sucesso'] = "Cadastro da empresa realizado com sucesso! Você já pode fazer o login.";
    header('Location: login.php?msg=cadastro_sucesso');
    exit;

} catch (Exception $e) {
    // Rollback em caso de falha
    if ($tenantId > 0) {
        $master_conn_rollback = getMasterConnection();
        if ($master_conn_rollback) {
            $master_conn_rollback->query("DELETE FROM tenants WHERE id = $tenantId");
            $master_conn_rollback->query("DELETE FROM usuarios WHERE tenant_id = $tenantId");
            $master_conn_rollback->close();
        }
    }

    // Remove banco e usuário MySQL criados
    $admin_conn_rollback = @new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    if (!$admin_conn_rollback->connect_error) {
        $admin_conn_rollback->query("DROP DATABASE IF EXISTS `{$novo_db_nome}`");
        $admin_conn_rollback->query("DROP USER IF EXISTS '{$novo_db_user}'@'localhost'");
        $admin_conn_rollback->close();
    }

    error_log("Erro no cadastro de tenant: " . $e->getMessage());
    $_SESSION['erro_registro'] = "Erro crítico no cadastro. Por favor, contate o suporte.";
    header('Location: registro.php?msg=critical_error');
    exit;
}
?>
