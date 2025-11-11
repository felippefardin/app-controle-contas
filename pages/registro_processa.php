<?php
// pages/registro_processa.php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database.php';

// 🔹 Carregar variáveis do .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$db   = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

// 🔹 Conexão com mysqli
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

// 🔹 Captura dados do formulário
$nome        = trim($_POST['nome'] ?? '');
$email       = trim($_POST['email'] ?? '');
$senha       = trim($_POST['senha'] ?? '');
$tipo_pessoa = trim($_POST['tipo_pessoa'] ?? '');
$documento   = trim($_POST['documento'] ?? '');
$telefone    = trim($_POST['telefone'] ?? '');
$plano_escolhido = trim($_POST['plano_escolhido'] ?? ($_GET['plano'] ?? 'mensal'));
$plano_escolhido = in_array($plano_escolhido, ['mensal', 'trimestral']) ? $plano_escolhido : 'mensal';
$dias_teste = ($plano_escolhido === 'trimestral') ? 30 : 15;

// 🔹 Validação mínima
if (!$nome || !$email || !$senha) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/registro.php");
    exit;
}

// 🔹 Hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    // 🔹 1. Verificar se e-mail já existe
    $stmtCheck = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        $_SESSION['erro_registro'] = "Este e-mail já está cadastrado.";
        header("Location: ../pages/registro.php?msg=email_duplicado");
        exit;
    }

    // 🔹 2. Criar tenant (conta)
    $tenantId = uniqid('T', true);
    $dbHost = 'localhost';
    $dbDatabase = 'tenant_db_' . md5($tenantId);
    $dbUser = 'dbuser';
    $dbPassword = 'dbpassword';

    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (id, admin_email, status_assinatura, data_inicio_teste, plano_atual, db_host, db_database, db_user, db_password)
        VALUES (?, ?, 'trial', NOW(), ?, ?, ?, ?, ?)
    ");
    // 🔹 CORREÇÃO: 7 variáveis correspondendo aos placeholders (status e data estão fixos)
    $stmtTenant->bind_param(
        "sssssss",
        $tenantId,
        $email,
        $plano_escolhido,
        $dbHost,
        $dbDatabase,
        $dbUser,
        $dbPassword
    );
    $stmtTenant->execute();

    // 🔹 3. Inserir usuário
    $nivel_acesso = 'usuario';
    $status = 'ativo';
    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tipo_pessoa, documento, telefone, tenant_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtUser->bind_param(
        "sssssssss",
        $nome,
        $email,
        $senha_hash,
        $nivel_acesso,
        $status,
        $tipo_pessoa,
        $documento,
        $telefone,
        $tenantId
    );
    $stmtUser->execute();

    // 🔹 Sucesso
    $_SESSION['registro_sucesso'] = "Cadastro realizado com sucesso! Você ganhou $dias_teste dias de teste grátis.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (mysqli_sql_exception $e) {
    $_SESSION['erro_registro'] = "Erro ao registrar usuário. Tente novamente.";
    error_log("Erro no registro: " . $e->getMessage());
    header("Location: ../pages/registro.php?msg=erro_db");
    exit;
}
?>
