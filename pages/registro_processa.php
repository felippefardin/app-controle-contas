<?php
// pages/registro_processa.php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database.php'; 

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$conn = getMasterConnection();

// Captura dados
$nome        = trim($_POST['nome'] ?? '');
$email       = trim($_POST['email'] ?? '');
$senha       = trim($_POST['senha'] ?? '');
$tipo_pessoa = trim($_POST['tipo_pessoa'] ?? 'fisica');
$documento   = trim($_POST['documento'] ?? '');
$telefone    = trim($_POST['telefone'] ?? '');

// Captura o plano escolhido no formulário (radio button)
$plano_post = trim($_POST['plano'] ?? 'basico');

// Validação e Definição dos Dias de Teste
// Essencial tem 30 dias, os outros 15 dias
if ($plano_post === 'essencial') {
    $dias_teste = 30;
    $plano_escolhido = 'essencial';
} elseif ($plano_post === 'plus') {
    $dias_teste = 15;
    $plano_escolhido = 'plus';
} else {
    $dias_teste = 15;
    $plano_escolhido = 'basico'; // Default
}

if (!$nome || !$email || !$senha) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/registro.php");
    exit;
}

if ($email === 'contatotech.tecnologia@gmail.com.br') {
    $_SESSION['erro_registro'] = "Este e-mail é reservado.";
    header("Location: ../pages/registro.php");
    exit;
}

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {
    // 1. Check email
    $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception("Este e-mail já está cadastrado.");
    }
    $stmtCheck->close();

    // 2. Insert Master User
    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo_pessoa, documento, telefone, nivel_acesso, perfil, tipo, status, is_master)
        VALUES (?, ?, ?, ?, ?, ?, 'proprietario', 'admin', 'admin', 'ativo', 1)
    ");
    $stmtUser->bind_param("ssssss", $nome, $email, $senha_hash, $tipo_pessoa, $documento, $telefone);
    $stmtUser->execute();
    $new_usuario_id = $conn->insert_id;
    $stmtUser->close();

    // 3. Tenant ID & Database
    $tenantId = 'T' . substr(md5(uniqid($email, true)), 0, 32);
    $dbHost     = $_ENV['DB_HOST'] ?? 'localhost';
    $dbDatabase = 'tenant_db_' . $new_usuario_id;
    $dbUser     = 'dbuser_' . $new_usuario_id;
    $dbPassword = bin2hex(random_bytes(16));
    $nome_empresa = $nome; 

    // Inserir Tenant com o Plano Escolhido e Status TRIAL
    // O sistema de login vai checar se NOW() > data_inicio_teste + 15 dias (ou 30)
    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (
            tenant_id, usuario_id, nome, nome_empresa, admin_email, senha, 
            status_assinatura, data_inicio_teste, plano_atual, 
            db_host, db_database, db_user, db_password
        ) VALUES (?, ?, ?, ?, ?, ?, 'trial', NOW(), ?, ?, ?, ?, ?)
    ");

    $stmtTenant->bind_param(
        "sisssssssss", 
        $tenantId, $new_usuario_id, $nome, $nome_empresa, $email, $senha_hash,
        $plano_escolhido, // Aqui entra 'basico', 'plus' ou 'essencial'
        $dbHost, $dbDatabase, $dbUser, $dbPassword
    );
    $stmtTenant->execute();
    $stmtTenant->close();

    // 4. Update User Tenant ID
    $conn->query("UPDATE usuarios SET tenant_id = '$tenantId' WHERE id = $new_usuario_id");

    // 5. Create Tenant DB
    $rootConn = new mysqli($dbHost, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $safeDbName = $rootConn->real_escape_string($dbDatabase);
    $safeDbUser = $rootConn->real_escape_string($dbUser);
    $safeDbPass = $rootConn->real_escape_string($dbPassword);

    $rootConn->query("CREATE DATABASE IF NOT EXISTS `$safeDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $rootConn->query("CREATE USER '$safeDbUser'@'localhost' IDENTIFIED BY '$safeDbPass'");
    $rootConn->query("GRANT ALL PRIVILEGES ON `$safeDbName`.* TO '$safeDbUser'@'localhost'");
    $rootConn->query("FLUSH PRIVILEGES");

    // 6. Run Schema
    $schemaPath = __DIR__ . '/../schema.sql';
    if (file_exists($schemaPath)) {
        $tenantConn = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
        $schemaSql = file_get_contents($schemaPath);
        if ($tenantConn->multi_query($schemaSql)) {
            do { if ($res = $tenantConn->store_result()) $res->free(); } while ($tenantConn->more_results() && $tenantConn->next_result());
        }
        
        // Insert Admin User inside Tenant DB
        $stmtTI = $tenantConn->prepare("INSERT INTO usuarios (nome, email, senha, tipo_pessoa, documento, telefone, nivel_acesso, perfil, status, is_master, tenant_id) VALUES (?, ?, ?, ?, ?, ?, 'proprietario', 'admin', 'ativo', 1, ?)");
        $stmtTI->bind_param("sssssss", $nome, $email, $senha_hash, $tipo_pessoa, $documento, $telefone, $tenantId);
        $stmtTI->execute();
        $stmtTI->close();
        $tenantConn->close();
    }
    $rootConn->close();

    $conn->commit();

    $_SESSION['registro_sucesso'] = "Cadastro realizado! Seu plano $plano_escolhido está ativo com $dias_teste dias grátis.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['erro_registro'] = "Erro: " . $e->getMessage();
    header("Location: ../pages/registro.php");
    exit;
}
?>