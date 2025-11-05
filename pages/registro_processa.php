<?php
require_once '../includes/session_init.php';
include('../database.php'); // Carrega $env e getMasterConnection()

// --- Função para estilizar mensagens ---
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
    </html>
    ";
}

// --- Verifica se o método é POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
}

// --- Captura os dados do formulário ---
$nome_empresa = trim($_POST['nome'] ?? '');
$email_admin  = trim(strtolower($_POST['email'] ?? ''));
$senha_admin  = $_POST['senha'] ?? '';
$cpf          = trim($_POST['cpf'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');

if (empty($nome_empresa) || empty($email_admin) || empty($senha_admin)) {
    die(estilizarMensagem("Preencha todos os campos obrigatórios.", "erro"));
}

// --- Verifica duplicidade no banco master ---
$master_conn = getMasterConnection();
if (!$master_conn) {
    die(estilizarMensagem("Falha ao conectar ao banco de dados principal.", "erro"));
}

$stmt = $master_conn->prepare("SELECT id FROM tenants WHERE admin_email = ?");
$stmt->bind_param("s", $email_admin);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    $master_conn->close();
    die(estilizarMensagem("Este e-mail já está em uso por outra conta.", "erro"));
}
$stmt->close();
// Deixamos a $master_conn aberta por enquanto

// Variáveis para o try/catch
$tenantId = 0;
$novo_db_nome = 'tenant_' . bin2hex(random_bytes(8));
$novo_db_user = 'user_' . bin2hex(random_bytes(8));
$novo_db_pass = bin2hex(random_bytes(16));

// --- Hashear a senha (Fazemos isso UMA VEZ) ---
$senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);

try {
    // --- ✅ ETAPA 1: Salva tenant no banco master (para obter o ID) ---
    // (O $master_conn já foi aberto acima)
    $stmt_tenant = $master_conn->prepare(
        "INSERT INTO tenants (nome_empresa, admin_email, db_host, db_database, db_user, db_password, senha) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    // Usamos o $senha_hash, como definido no schema do app_controle_contas (2).sql
    $stmt_tenant->bind_param("sssssss", $nome_empresa, $email_admin, $env['DB_HOST'], $novo_db_nome, $novo_db_user, $novo_db_pass, $senha_hash);
    $stmt_tenant->execute();
    $tenantId = $stmt_tenant->insert_id; // <-- Obtemos o ID do Tenant
    $stmt_tenant->close();

    // --- ✅ ETAPA 2: (A CORREÇÃO) Insere o usuário proprietário no banco master ---
    $stmt_main_user = $master_conn->prepare(
        "INSERT INTO usuarios (nome, email, senha, nivel_acesso, tenant_id, tipo_pessoa, perfil, tipo, status, cpf, telefone) 
         VALUES (?, ?, ?, 'proprietario', ?, '', 'admin', 'admin', 'ativo', ?, ?)"
    );
    
    // ✅ LINHA 93 CORRIGIDA: A string de tipos agora é "sssiss" (6 caracteres)
    $stmt_main_user->bind_param("sssiss", $nome_empresa, $email_admin, $senha_hash, $tenantId, $cpf, $telefone);
    
    $stmt_main_user->execute();
    $stmt_main_user->close();
    $master_conn->close(); // Fechamos a conexão master


    // --- ✅ ETAPA 3: Cria o banco de dados do tenant ---
    $admin_conn = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    $admin_conn->set_charset("utf8mb4");
    if ($admin_conn->connect_error) {
        throw new Exception("Falha na conexão como admin: " . $admin_conn->connect_error);
    }

    $admin_conn->query("CREATE DATABASE `{$novo_db_nome}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $admin_conn->query("CREATE USER '{$novo_db_user}'@'localhost' IDENTIFIED BY '{$novo_db_pass}'");
    $admin_conn->query("GRANT ALL PRIVILEGES ON `{$novo_db_nome}`.* TO '{$novo_db_user}'@'localhost'");
    $admin_conn->close();

    // --- ✅ ETAPA 4: Executa schema no banco do tenant ---
    $schema_sql = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema_sql === false) throw new Exception("Arquivo schema.sql não encontrado.");

    $tenant_conn = new mysqli($env['DB_HOST'], $novo_db_user, $novo_db_pass, $novo_db_nome);
    $tenant_conn->set_charset("utf8mb4");
    if ($tenant_conn->connect_error) {
        throw new Exception("Falha ao conectar ao banco do tenant: Por favor, contate o suporte.");
    }
    if (!$tenant_conn->multi_query($schema_sql)) {
        throw new Exception("Erro ao criar as tabelas do tenant: " . $tenant_conn->error);
    }
    while ($tenant_conn->next_result()) {;} // Limpa os resultados do multi_query

    // --- ✅ ETAPA 5: Insere usuário admin no banco do tenant ---
    $stmt_tenant_user = $tenant_conn->prepare(
        "INSERT INTO usuarios (nome, email, cpf, telefone, senha, nivel_acesso, status) 
         VALUES (?, ?, ?, ?, ?, 'proprietario', 'ativo')"
    );
    // Usamos o mesmo $senha_hash
    $stmt_tenant_user->bind_param("sssss", $nome_empresa, $email_admin, $cpf, $telefone, $senha_hash);
    $stmt_tenant_user->execute();
    $stmt_tenant_user->close();
    $tenant_conn->close();

    echo estilizarMensagem("Cadastro da empresa realizado com sucesso! <br><a href='login.php'>Ir para o login</a>", "sucesso");
    exit;

} catch (Exception $e) {
    // Lógica de Rollback (reversão) em caso de falha
    // Se algo falhou, precisamos apagar o que foi criado
    if ($tenantId > 0) {
        $master_conn_rollback = getMasterConnection();
        $master_conn_rollback->query("DELETE FROM tenants WHERE id = $tenantId");
        $master_conn_rollback->query("DELETE FROM usuarios WHERE tenant_id = $tenantId"); // Remove o usuário do master
        $master_conn_rollback->close();
    }
    // Tenta apagar o banco de dados e usuário MySQL se eles foram criados
    $admin_conn_rollback = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    if (!$admin_conn_rollback->connect_error) {
        $admin_conn_rollback->query("DROP DATABASE IF EXISTS `{$novo_db_nome}`");
        $admin_conn_rollback->query("DROP USER IF EXISTS '{$novo_db_user}'@'localhost'");
        $admin_conn_rollback->close();
    }

    die(estilizarMensagem("Erro crítico no cadastro: " . $e->getMessage(), "erro"));
}
?>