<?php
require_once '../includes/session_init.php';
include('../database.php'); // Agora inclui as novas funções de conexão

// Apenas o usuário principal logado pode adicionar novos usuários
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do novo cliente (tenant)
    $nome_empresa = trim($_POST['nome_empresa'] ?? '');
    $email_admin = trim(strtolower($_POST['email'] ?? ''));
    $senha_admin = $_POST['senha'] ?? '';
    // ... outros campos que você possa ter no formulário de cadastro de cliente

    // --- 1. Criar um novo banco de dados e um usuário para o cliente ---
    $novo_db_nome = 'tenant_' . uniqid();
    $novo_db_user = 'user_' . uniqid();
    $novo_db_pass = password_hash(uniqid(), PASSWORD_DEFAULT); // Gere uma senha segura

    // Conexão com privilégios para criar bancos e usuários
    $admin_conn = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    if ($admin_conn->connect_error) {
        die("Erro ao conectar como admin do banco de dados.");
    }

    $admin_conn->query("CREATE DATABASE `{$novo_db_nome}`");
    $admin_conn->query("CREATE USER '{$novo_db_user}'@'localhost' IDENTIFIED BY '{$novo_db_pass}'");
    $admin_conn->query("GRANT ALL PRIVILEGES ON `{$novo_db_nome}`.* TO '{$novo_db_user}'@'localhost'");
    $admin_conn->close();

    // --- 2. Executar o script SQL para criar as tabelas no novo banco de dados ---
    $tenant_conn = new mysqli($env['DB_HOST'], $novo_db_user, $novo_db_pass, $novo_db_nome);
    $schema_sql = file_get_contents('../caminho/para/seu/schema.sql'); // Tenha um arquivo .sql com a estrutura das suas tabelas
    $tenant_conn->multi_query($schema_sql);
    $tenant_conn->close();

    // --- 3. Inserir o primeiro usuário (admin) no banco de dados do novo cliente ---
    $tenant_conn = new mysqli($env['DB_HOST'], $novo_db_user, $novo_db_pass, $novo_db_nome);
    $senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);
    $stmt = $tenant_conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, 'proprietario')");
    $stmt->bind_param("sss", $nome_empresa, $email_admin, $senha_hash);
    $stmt->execute();
    $stmt->close();
    $tenant_conn->close();

    // --- 4. Salvar as informações do novo tenant no banco de dados principal (master) ---
    $master_conn = getMasterConnection();
    $stmt = $master_conn->prepare("INSERT INTO tenants (nome_empresa, db_host, db_database, db_user, db_password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome_empresa, $env['DB_HOST'], $novo_db_nome, $novo_db_user, $novo_db_pass);
    $stmt->execute();
    $stmt->close();
    $master_conn->close();

    header("Location: ../pages/clientes.php?sucesso=1");
    exit;
}
?>