<?php
require_once '../includes/session_init.php';
include('../database.php'); 

global $env;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
}

// Função para estilizar mensagens (definida no início para ficar organizada)
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
        </div>
    </body>
    </html>
    ";
}

// 1. CAPTURA E VALIDA OS DADOS DO NOVO CLIENTE
$nome_empresa = trim($_POST['nome'] ?? '');
$email_admin  = trim(strtolower($_POST['email'] ?? ''));
$senha_admin  = $_POST['senha'] ?? '';
$cpf          = trim($_POST['cpf'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');

if (empty($nome_empresa) || empty($email_admin) || empty($senha_admin)) {
    die(estilizarMensagem("Preencha todos os campos obrigatórios.", "erro"));
}

// --- VERIFICAÇÃO DE DUPLICIDADE NO BANCO MASTER ---
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


// 2. CRIA UM NOVO BANCO DE DADOS E UM USUÁRIO PARA O CLIENTE
try {
    $novo_db_nome = 'tenant_' . bin2hex(random_bytes(8));
    $novo_db_user = 'user_' . bin2hex(random_bytes(8));
    $novo_db_pass = bin2hex(random_bytes(16));

    $admin_conn = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    $admin_conn->query("CREATE DATABASE `{$novo_db_nome}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $admin_conn->query("CREATE USER '{$novo_db_user}'@'localhost' IDENTIFIED BY '{$novo_db_pass}'");
    $admin_conn->query("GRANT ALL PRIVILEGES ON `{$novo_db_nome}`.* TO '{$novo_db_user}'@'localhost'");
    $admin_conn->close();

    // 3. EXECUTA O SCRIPT SQL PARA CRIAR AS TABELAS
    $schema_sql = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema_sql === false) {
        throw new Exception("Arquivo de schema (schema.sql) não encontrado na raiz do projeto.");
    }

    $tenant_conn = new mysqli($env['DB_HOST'], $novo_db_user, $novo_db_pass, $novo_db_nome);
    if (!$tenant_conn->multi_query($schema_sql)) {
         throw new Exception("Erro ao criar as tabelas no banco de dados do cliente.");
    }
    while ($tenant_conn->next_result()) {;}
    
    // 4. INSERE O PRIMEIRO USUÁRIO (ADMIN) NO BANCO DO CLIENTE
    $senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);
    $stmt = $tenant_conn->prepare(
        "INSERT INTO usuarios (nome, email, cpf, telefone, senha, nivel_acesso, status) 
         VALUES (?, ?, ?, ?, ?, 'proprietario', 'ativo')"
    );
    $stmt->bind_param("sssss", $nome_empresa, $email_admin, $cpf, $telefone, $senha_hash);
    $stmt->execute();
    $stmt->close();
    $tenant_conn->close();

    // 5. SALVA AS INFORMAÇÕES DO NOVO TENANT NO BANCO MASTER
    $master_conn = getMasterConnection();
    $stmt = $master_conn->prepare(
        "INSERT INTO tenants (nome_empresa, admin_email, db_host, db_database, db_user, db_password) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssss", $nome_empresa, $email_admin, $env['DB_HOST'], $novo_db_nome, $novo_db_user, $novo_db_pass);
    $stmt->execute();
    $stmt->close();
    $master_conn->close();

    // Sucesso!
    echo estilizarMensagem("Cadastro da empresa realizado com sucesso! <br><a href='login.php'>Ir para o login</a>", "sucesso");
    exit;

} catch (Exception $e) {
    // Em caso de qualquer erro, exibe a mensagem de falha
    die(estilizarMensagem("Erro crítico no cadastro: " . $e->getMessage(), "erro"));
}
?>