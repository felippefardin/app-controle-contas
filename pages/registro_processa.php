<?php
require_once '../includes/session_init.php';
include('../database.php'); 

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
$stmt = $master_conn->prepare("SELECT id FROM tenants WHERE admin_email = ?");
$stmt->bind_param("s", $email_admin);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    die(estilizarMensagem("Este e-mail já está em uso por outra conta.", "erro"));
}
$stmt->close();
$master_conn->close();

try {
    // --- Criação do banco e usuário do tenant ---
    $novo_db_nome = 'tenant_' . bin2hex(random_bytes(8));
    $novo_db_user = 'user_' . bin2hex(random_bytes(8));
    $novo_db_pass = bin2hex(random_bytes(16));

    // Conexão admin do MySQL
    $admin_conn = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    $admin_conn->set_charset("utf8mb4");
    if ($admin_conn->connect_error) {
        throw new Exception("Falha na conexão como admin: " . $admin_conn->connect_error);
    }

    $admin_conn->query("CREATE DATABASE `{$novo_db_nome}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $admin_conn->query("CREATE USER '{$novo_db_user}'@'localhost' IDENTIFIED BY '{$novo_db_pass}'");
    $admin_conn->query("GRANT ALL PRIVILEGES ON `{$novo_db_nome}`.* TO '{$novo_db_user}'@'localhost'");
    $admin_conn->close();

    // --- Executa schema no banco do tenant ---
    $schema_sql = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema_sql === false) throw new Exception("Arquivo schema.sql não encontrado.");

    $tenant_conn = new mysqli($env['DB_HOST'], $novo_db_user, $novo_db_pass, $novo_db_nome);
    $tenant_conn->set_charset("utf8mb4");
    if (!$tenant_conn->multi_query($schema_sql)) {
        throw new Exception("Erro ao criar as tabelas do tenant: " . $tenant_conn->error);
    }
    while ($tenant_conn->next_result()) {;}

    // --- Insere usuário admin ---
    $senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);
    $stmt = $tenant_conn->prepare(
        "INSERT INTO usuarios (nome, email, cpf, telefone, senha, nivel_acesso, status) 
         VALUES (?, ?, ?, ?, ?, 'proprietario', 'ativo')"
    );
    $stmt->bind_param("sssss", $nome_empresa, $email_admin, $cpf, $telefone, $senha_hash);
    $stmt->execute();
    $stmt->close();
    $tenant_conn->close();

    // --- Salva tenant no banco master ---
    $master_conn = getMasterConnection();
    $stmt = $master_conn->prepare(
        "INSERT INTO tenants (nome_empresa, admin_email, db_host, db_database, db_user, db_password) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssss", $nome_empresa, $email_admin, $env['DB_HOST'], $novo_db_nome, $novo_db_user, $novo_db_pass);
    $stmt->execute();
    $stmt->close();
    $master_conn->close();

    echo estilizarMensagem("Cadastro da empresa realizado com sucesso! <br><a href='login.php'>Ir para o login</a>", "sucesso");
    exit;

} catch (Exception $e) {
    die(estilizarMensagem("Erro crítico no cadastro: " . $e->getMessage(), "erro"));
}
?>
