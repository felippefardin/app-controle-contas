<?php
// actions/register_user.php
session_start();

require_once '../includes/config/config.php';
require_once '../database.php'; // getMasterConnection()
require_once '../includes/create_tenant_db.php';

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

// --- Validação ---
if (!$nome || !$email || !$senha) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/registro.php");
    exit;
}

// --- Criptografa senha ---
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// --- Conecta ao banco master ---
$conn = getMasterConnection();

// --- Verifica duplicidade ---
$stmtCheck = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE email = ?");
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$row = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if ($row['total'] > 0) {
    $_SESSION['erro_registro'] = "Este e-mail já está cadastrado.";
    header("Location: ../pages/registro.php?msg=email_duplicado");
    exit;
}

try {
    // --- Gera identificador e credenciais do tenant ---
    $tenantId = uniqid('T', true);
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbUser = $_ENV['DB_USER'] ?? 'root';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $dbName = 'tenant_db_' . md5($tenantId);
    $tenantUser = 'dbuser';
    $tenantPass = 'dbpassword';

    // --- Registra tenant no banco master ---
    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (nome, nome_empresa, admin_email, db_host, db_database, db_user, db_password, status_assinatura, data_inicio_teste, plano_atual)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'trial', NOW(), ?)
    ");
    $nome_empresa = $nome; // o nome do tenant é o nome do usuário
    $stmtTenant->bind_param("ssssssss", $nome, $nome_empresa, $email, $dbHost, $dbName, $tenantUser, $tenantPass, $plano_escolhido);
    $stmtTenant->execute();
    $stmtTenant->close();

    // --- Cria o banco do tenant ---
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- Cria usuário MySQL e dá acesso ---
    $grantSQL = "
        CREATE USER IF NOT EXISTS '$tenantUser'@'localhost' IDENTIFIED BY '$tenantPass';
        GRANT ALL PRIVILEGES ON `$dbName`.* TO '$tenantUser'@'localhost';
        FLUSH PRIVILEGES;
    ";
    $conn->multi_query($grantSQL);
    while ($conn->more_results() && $conn->next_result()) { /* limpa */ }

    // --- Conecta ao novo banco ---
    $tenantConn = new mysqli($dbHost, $tenantUser, $tenantPass, $dbName);
    if ($tenantConn->connect_error) {
        throw new Exception("Erro ao conectar ao banco do tenant: " . $tenantConn->connect_error);
    }

    // --- Executa schema base do tenant ---
    $schemaPath = __DIR__ . '/../includes/tenant_schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("Arquivo tenant_schema.sql não encontrado.");
    }

    $schemaSQL = file_get_contents($schemaPath);
    if (!$tenantConn->multi_query($schemaSQL)) {
        throw new Exception("Erro ao executar schema: " . $tenantConn->error);
    }
    while ($tenantConn->more_results() && $tenantConn->next_result()) { /* limpa resultados */ }

    // --- Insere usuário do registro (como admin ativo) ---
    $stmtUser = $tenantConn->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tipo_pessoa, documento, telefone, tenant_id)
        VALUES (?, ?, ?, 'admin', 'ativo', ?, ?, ?, ?)
    ");
    $stmtUser->bind_param("sssssss", $nome, $email, $senha_hash, $tipo_pessoa, $documento, $telefone, $tenantId);
    $stmtUser->execute();
    $stmtUser->close();
    $tenantConn->close();

    // --- Sucesso ---
    $_SESSION['registro_sucesso'] = "🎉 Cadastro realizado com sucesso! Você ganhou $dias_teste dias de teste grátis.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (Exception $e) {
    error_log("❌ Erro no registro automático: " . $e->getMessage());
    $_SESSION['erro_registro'] = "Erro ao criar conta. Tente novamente.";
    header("Location: ../pages/registro.php?msg=erro_db");
    exit;
}
?>
