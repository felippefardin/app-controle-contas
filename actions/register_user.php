<?php
// actions/register_user.php
session_start();

require_once '../includes/config/config.php';
require_once '../database.php'; // getMasterConnection()
require_once '../includes/tenant_utils.php';

// --- Captura segura dos dados enviados ---
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

// --- Conecta no banco master ---
$conn = getMasterConnection();

// --- Verifica duplicidade ---
$stmtCheck = $conn->prepare("SELECT COUNT(*) AS total FROM tenants WHERE admin_email = ?");
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
    // --- 1) Cria usuário no master (tabela usuarios) ---
    $stmtUserMaster = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel_acesso, status)
        VALUES (?, ?, ?, 'admin', 'ativo')
    ");
    $stmtUserMaster->bind_param("sss", $nome, $email, $senha_hash);
    $stmtUserMaster->execute();
    $usuarioMasterId = $conn->insert_id;
    $stmtUserMaster->close();

    // --- 2) Gera tenant_id + banco + usuário MySQL ---
    $tenantId = md5(uniqid('', true));
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbName = 'tenant_db_' . $tenantId;

    // usuário mysql do tenant
    $tenantUser = 'dbu_' . substr($tenantId, 0, 12);
    $tenantPass = bin2hex(random_bytes(16)); // 32 chars


    // --- 3) Registra tenant no master (TABELA ATUAL) ---
    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (
            tenant_id, usuario_id, nome, nome_empresa, admin_email,
            subdominio, db_host, db_database, db_user, db_password,
            status_assinatura, role, data_inicio_teste, plano_atual, senha
        ) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 'trial', 'usuario', CURDATE(), ?, ?)
    ");

    $nome_empresa = $nome;
    $stmtTenant->bind_param(
        "sisssssssss",
        $tenantId,
        $usuarioMasterId,
        $nome,
        $nome_empresa,
        $email,
        $dbHost,
        $dbName,
        $tenantUser,
        $tenantPass,
        $plano_escolhido,
        $senha_hash
    );

    $stmtTenant->execute();
    $stmtTenant->close();

    // --- 4) Cria banco do tenant ---
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- 5) Cria usuário MySQL do tenant ---
    $conn->query("DROP USER IF EXISTS '$tenantUser'@'localhost'");
    $conn->query("CREATE USER '$tenantUser'@'localhost' IDENTIFIED BY '$tenantPass'");
    $conn->query("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$tenantUser'@'localhost'");
    $conn->query("FLUSH PRIVILEGES");

    // --- 6) Conecta no banco do tenant ---
    $tenantConn = new mysqli($dbHost, $tenantUser, $tenantPass, $dbName);
    if ($tenantConn->connect_error) {
        throw new Exception("Erro ao conectar ao banco do tenant: " . $tenantConn->connect_error);
    }
    $tenantConn->set_charset("utf8mb4");

    // --- 7) Executa schema base ---
    $schemaPath = __DIR__ . '/../includes/tenant_schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("Arquivo tenant_schema.sql não encontrado.");
    }

    $schemaSQL = file_get_contents($schemaPath);
    if (!$tenantConn->multi_query($schemaSQL)) {
        throw new Exception("Erro ao aplicar schema: " . $tenantConn->error);
    }
    while ($tenantConn->more_results() && $tenantConn->next_result()) {}

    // --- 8) Cria admin dentro do tenant ---
    $stmtUserTenant = $tenantConn->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tipo_pessoa, documento, telefone, tenant_id)
        VALUES (?, ?, ?, 'admin', 'ativo', ?, ?, ?, ?)
    ");
    $stmtUserTenant->bind_param("sssssss", 
        $nome,
        $email,
        $senha_hash,
        $tipo_pessoa,
        $documento,
        $telefone,
        $tenantId
    );
    $stmtUserTenant->execute();
    $stmtUserTenant->close();

    $tenantConn->close();

    // --- Sucesso ---
    $_SESSION['registro_sucesso'] = "Cadastro concluído! Seu período de teste: $dias_teste dias.";

    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit();

} catch (Exception $e) {

    error_log("⛔ ERRO REGISTER_USER: " . $e->getMessage());
    $_SESSION['erro_registro'] = "Erro ao criar conta. Tente novamente.";

    // rollback
    if (isset($dbName)) $conn->query("DROP DATABASE IF EXISTS `$dbName`");
    if (isset($tenantUser)) $conn->query("DROP USER IF EXISTS '$tenantUser'@'localhost'");
    if (isset($email)) $conn->query("DELETE FROM tenants WHERE admin_email = '$email'");
    if (isset($usuarioMasterId)) $conn->query("DELETE FROM usuarios WHERE id = $usuarioMasterId");

    header("Location: ../pages/registro.php?msg=erro");
    exit();
}
?>
