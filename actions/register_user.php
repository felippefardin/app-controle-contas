<?php
// actions/register_user.php
session_start();
require_once '../includes/config/config.php';
require_once '../database.php'; // Funções getMasterConnection() ou similar

// --- Captura os dados do formulário com segurança ---
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

// --- Validações básicas ---
if (!$nome || !$email || !$senha) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/registro.php");
    exit;
}

// --- Hash da senha ---
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// --- Conexão com o banco master (mysqli) ---
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$db   = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

try {
    // --- 1. Verificar duplicidade de e-mail ---
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

    // --- 2. Criar tenant (conta) na tabela master 'tenants' ---
    $tenantId = uniqid('T', true);
    $dbHost = 'localhost';
    $dbDatabase = 'tenant_db_' . md5($tenantId);
    $dbUser = 'dbuser';
    $dbPassword = 'dbpassword';

    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (id, admin_email, status_assinatura, data_inicio_teste, plano_atual, db_host, db_database, db_user, db_password)
        VALUES (?, ?, 'trial', NOW(), ?, ?, ?, ?, ?)
    ");
    $stmtTenant->bind_param(
        "ssssssss",
        $tenantId,
        $email,
        $plano_escolhido,
        $dbHost,
        $dbDatabase,
        $dbUser,
        $dbPassword
    );
    $stmtTenant->execute();

    // --- 3. Inserir usuário na tabela 'usuarios' ---
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

    // --- Sucesso ---
    $_SESSION['registro_sucesso'] = "Cadastro realizado com sucesso! Você ganhou $dias_teste dias de teste grátis.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (Exception $e) {
    $_SESSION['erro_registro'] = "Erro ao registrar usuário. Tente novamente.";
    error_log("Erro no registro: " . $e->getMessage());
    header("Location: ../pages/registro.php?msg=erro_db");
    exit;
}

?>
