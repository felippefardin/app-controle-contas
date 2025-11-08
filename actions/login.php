<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// --- 1. Verifica se os campos foram preenchidos ---
if (empty($_POST['email']) || empty($_POST['senha'])) {
    $_SESSION['erro_login'] = 'Preencha todos os campos.';
    header("Location: ../pages/login.php?msg=campos_vazios");
    exit;
}

$email = trim($_POST['email']);
$senha = trim($_POST['senha']);

$master = getMasterConnection();

// --- LOGIN DIRETO DO ADMIN MASTER ---
if ($email === 'contatotech.tecnologia@gmail.com') {
    $stmtAdmin = $master->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmtAdmin->bind_param("s", $email);
    $stmtAdmin->execute();
    $adminUser = $stmtAdmin->get_result()->fetch_assoc();
    $stmtAdmin->close();

    if ($adminUser && password_verify($senha, $adminUser['senha'])) {
        $_SESSION['super_admin'] = [
            'nome' => $adminUser['nome'] ?? 'Administrador',
            'email' => $adminUser['email']
        ];
        header("Location: ../pages/admin/dashboard.php");
        exit;
    } else {
        $_SESSION['erro_login'] = 'E-mail ou senha inválidos.';
        header("Location: ../pages/login.php?msg=login_invalido");
        exit;
    }
}

// --- 2. Busca o tenant associado ---
$stmt = $master->prepare("
    SELECT id, db_host, db_database, db_user, db_password, status_assinatura
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

if (!$tenant) {
    $_SESSION['erro_login'] = 'Conta não encontrada.';
    header("Location: ../pages/login.php?msg=conta_inexistente");
    exit;
}

// --- 3. Verifica se a assinatura está ativa ---
if ($tenant['status_assinatura'] !== 'authorized') {
    $_SESSION['erro_login'] = '⚠️ Sua assinatura não está ativa. Complete o pagamento para acessar o sistema.';

    // ✅ Caminho absoluto para o login e redirecionamento correto
    echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 1) . "/../pages/assinar.php'>";
    include __DIR__ . '/../pages/login.php';
    exit;
}

// --- 4. Conecta ao banco do tenant ---
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
    $_SESSION['erro_login'] = 'Erro ao conectar ao banco do tenant.';
    header("Location: ../pages/login.php?msg=db_tenant");
    exit;
}

// --- 5. Busca usuário dentro do tenant ---
$stmt = $tenantPdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($senha, $user['senha'])) {
    $_SESSION['erro_login'] = 'E-mail ou senha inválidos.';
    header("Location: ../pages/login.php?msg=login_invalido");
    exit;
}

// --- 6. Cria sessão ---
$_SESSION['usuario_logado'] = [
    'id' => $user['id'],
    'nome' => $user['nome'],
    'email' => $user['email'],
    'nivel_acesso' => $user['nivel_acesso'],
    'tenant_id' => $tenant['id']
];

// --- 7. Armazena credenciais do banco ---
$_SESSION['tenant_db'] = [
    'db_host' => $tenant['db_host'],
    'db_database' => $tenant['db_database'],
    'db_user' => $tenant['db_user'],
    'db_password' => $tenant['db_password']
];

// --- 8. Redireciona ---
header("Location: ../pages/selecionar_usuario.php");
exit;
