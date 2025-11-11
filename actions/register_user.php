<?php
// actions/register_user.php
session_start();

require_once '../includes/config/config.php';
require_once '../database.php'; // Funções getMasterConnection()

// --- Captura segura dos dados do formulário ---
$dados = $_POST ?? [];

$nome        = trim($dados['nome'] ?? '');
$email       = trim($dados['email'] ?? '');
$senha       = trim($dados['senha'] ?? '');
$tipo_pessoa = trim($dados['tipo_pessoa'] ?? '');
$documento   = trim($dados['documento'] ?? '');
$telefone    = trim($dados['telefone'] ?? '');
$plano_escolhido = trim($dados['plano_escolhido'] ?? ($_GET['plano'] ?? 'mensal'));
$plano_escolhido = in_array($plano_escolhido, ['mensal', 'trimestral']) ? $plano_escolhido : 'mensal';
$dias_teste = ($plano_escolhido === 'trimestral') ? 30 : 15;

// --- Validação básica ---
if (!$nome || !$email || !$senha) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/registro.php");
    exit;
}

// --- Hash da senha ---
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// --- Conexão com o banco master ---
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = trim($_ENV['DB_PASSWORD'] ?? '');
$db   = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha ao conectar ao banco master: " . $conn->connect_error);
}

try {
    // --- 1. Verificar duplicidade de e-mail ---
    $stmtCheck = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $row = $result->fetch_assoc();
    $stmtCheck->close();

    if ($row['total'] > 0) {
        $_SESSION['erro_registro'] = "Este e-mail já está cadastrado.";
        header("Location: ../pages/registro.php?msg=email_duplicado");
        exit;
    }

    // --- 2. Criar tenant (registro no banco master) ---
    $tenantId = uniqid('T', true);
    $dbHost = $host;
    $dbDatabase = 'tenant_db_' . md5($tenantId);
    $dbUser = 'dbuser';
    $dbPassword = 'dbpassword';

    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (admin_email, status_assinatura, data_inicio_teste, plano_atual, db_host, db_database, db_user, db_password)
        VALUES (?, 'trial', NOW(), ?, ?, ?, ?, ?)
    ");
    $stmtTenant->bind_param("sssssss", $email, $plano_escolhido, $dbHost, $dbDatabase, $dbUser, $dbPassword);
    $stmtTenant->execute();
    $stmtTenant->close();

    // --- 3. Criar banco do tenant ---
    $dbNameEscaped = $conn->real_escape_string($dbDatabase);
    $sqlCreateDB = "CREATE DATABASE IF NOT EXISTS `{$dbNameEscaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    if (!$conn->query($sqlCreateDB)) {
        $conn->query("DELETE FROM tenants WHERE admin_email = '{$email}'");
        $_SESSION['erro_registro'] = "Erro interno ao criar o banco de dados do tenant.";
        error_log("Erro ao criar DB do tenant: " . $conn->error);
        header("Location: ../pages/registro.php?msg=erro_db_create");
        exit;
    }

    // --- 4. Criar usuário MySQL (dbuser) e conceder privilégios ---
    $grantSQL = "
        CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}';
        GRANT ALL PRIVILEGES ON `{$dbNameEscaped}`.* TO '{$dbUser}'@'localhost';
        FLUSH PRIVILEGES;
    ";
    if (!$conn->multi_query($grantSQL)) {
        error_log("Erro ao criar usuário do tenant: " . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()) { /* limpa */ }

    // --- 5. Conecta-se ao novo banco do tenant ---
    $tenantConn = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
    if ($tenantConn->connect_error) {
        throw new Exception("Falha ao conectar ao banco do tenant: " . $tenantConn->connect_error);
    }

    // --- 6. Criar tabela de usuários no banco do tenant ---
    $sqlCreateTable = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            nivel_acesso ENUM('admin', 'usuario') DEFAULT 'admin',
            status ENUM('ativo', 'inativo') DEFAULT 'ativo',
            tipo_pessoa VARCHAR(50),
            documento VARCHAR(50),
            telefone VARCHAR(50),
            tenant_id VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    $tenantConn->query($sqlCreateTable);

    // --- 7. Inserir usuário principal (admin) ---
    $stmtUser = $tenantConn->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tipo_pessoa, documento, telefone, tenant_id)
        VALUES (?, ?, ?, 'admin', 'ativo', ?, ?, ?, ?)
    ");
    $stmtUser->bind_param("sssssss", $nome, $email, $senha_hash, $tipo_pessoa, $documento, $telefone, $tenantId);
    $stmtUser->execute();
    $stmtUser->close();
    $tenantConn->close();

    // --- Sucesso ---
    $_SESSION['registro_sucesso'] = "Cadastro realizado com sucesso! Você ganhou $dias_teste dias de teste grátis.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (Exception $e) {
    error_log("Erro no registro: " . $e->getMessage());
    $_SESSION['erro_registro'] = "Erro ao registrar usuário. Tente novamente.";
    header("Location: ../pages/registro.php?msg=erro_db");
    exit;
}
?>
