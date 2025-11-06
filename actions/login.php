<?php
session_start();
require_once '../database.php';

// --- 1. Verifica se os campos foram preenchidos ---
if (empty($_POST['email']) || empty($_POST['senha'])) {
    $_SESSION['erro_login'] = 'Preencha todos os campos.';
    header("Location: ../pages/login.php?msg=campos_vazios");
    exit;
}

$email = trim($_POST['email']);
$senha = trim($_POST['senha']);

// --- 2. Busca o tenant associado ao e-mail no banco master ---
$master = getMasterConnection();

$stmt = $master->prepare("
    SELECT id, db_host, db_database, db_user, db_password
    FROM tenants
    WHERE admin_email = ? OR id IN (
        SELECT tenant_id FROM usuarios WHERE email = ?
    )
");
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$stmt->close();
$master->close();

if (!$tenant) {
    $_SESSION['erro_login'] = 'Conta não encontrada ou tenant inexistente.';
    header("Location: ../pages/login.php?msg=conta_inexistente");
    exit;
}

// --- 3. Tenta conectar ao banco do tenant ---
try {
    $tenantPdo = new PDO(
        "mysql:host={$tenant['db_host']};dbname={$tenant['db_database']};charset=utf8mb4",
        $tenant['db_user'],
        $tenant['db_password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    $_SESSION['erro_login'] = 'Erro de conexão com o banco do tenant.';
    header("Location: ../pages/login.php?msg=db_tenant");
    exit;
}

// --- 4. Busca o usuário dentro do banco do tenant ---
$stmt = $tenantPdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($senha, $user['senha'])) {
    $_SESSION['erro_login'] = 'E-mail ou senha inválidos.';
    header("Location: ../pages/login.php?msg=login_invalido");
    exit;
}

// --- 5. Cria sessão com dados do usuário e tenant ---
$_SESSION['usuario_logado'] = [
    'id' => $user['id'],
    'nome' => $user['nome'],
    'email' => $user['email'],
    'nivel_acesso' => $user['nivel_acesso'],
    'tenant_id' => $tenant['id']
];

// --- 6. Armazena credenciais do banco do tenant na sessão ---
$_SESSION['tenant_db'] = [
    'db_host' => $tenant['db_host'],
    'db_database' => $tenant['db_database'],
    'db_user' => $tenant['db_user'],
    'db_password' => $tenant['db_password']
];

// --- 7. Redireciona para a tela de seleção de usuário ---
header("Location: ../pages/selecionar_usuario.php");
exit;
