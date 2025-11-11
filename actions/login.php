<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// --- Função para calcular o fim do período de teste ---
function calculateTrialEnd($startDate, $plano) {
    $plano = in_array($plano, ['mensal', 'trimestral']) ? $plano : 'mensal';
    $days = ($plano === 'trimestral') ? 30 : 15;
    return date('Y-m-d', strtotime("{$startDate} +{$days} days"));
}

// --- 1. Verifica se os campos foram preenchidos ---
if (empty($_POST['email']) || empty($_POST['senha'])) {
    $_SESSION['erro_login'] = 'Preencha todos os campos.';
    header("Location: ../pages/login.php?msg=campos_vazios");
    exit;
}

// --- 2. Captura e normaliza os dados ---
$email = strtolower(trim($_POST['email']));
$senha = trim($_POST['senha']);

$master = getMasterConnection(); // Banco Master

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

// --- 3. Busca o tenant associado ---
$stmt = $master->prepare("
    SELECT t.id, t.db_host, t.db_database, t.db_user, t.db_password, t.status_assinatura, t.data_inicio_teste, t.plano_atual
    FROM tenants t
    WHERE LOWER(t.admin_email) = ? OR t.id IN (
        SELECT tenant_id FROM usuarios WHERE LOWER(email) = ?
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

// --- 4. Checa trial/assinatura ---
$status_assinatura = $tenant['status_assinatura'];

if ($status_assinatura === 'trial') {
    $data_inicio_teste = $tenant['data_inicio_teste'];
    $plano_atual = $tenant['plano_atual'];

    if (!empty($data_inicio_teste)) {
        $data_fim_teste = calculateTrialEnd($data_inicio_teste, $plano_atual);
        $hoje = date('Y-m-d');

        if ($hoje > $data_fim_teste) {
            $stmtUpdate = $master->prepare("UPDATE tenants SET status_assinatura = 'expired' WHERE id = ?");
            $stmtUpdate->bind_param("s", $tenant['id']);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            $status_assinatura = 'expired';
        } else {
            $dias_restantes = floor((strtotime($data_fim_teste) - strtotime($hoje)) / (60 * 60 * 24));
            $_SESSION['aviso_trial'] = "Seu teste grátis expira em " . date('d/m/Y', strtotime($data_fim_teste)) . " ({$dias_restantes} dias restantes).";
        }
    }
}

if ($status_assinatura !== 'authorized' && $status_assinatura !== 'trial') {
    $mensagem_erro = ($status_assinatura === 'expired') 
        ? '🛑 Seu período de teste expirou. Faça uma assinatura para continuar a usar o sistema.'
        : '⚠️ Sua assinatura não está ativa. Complete o pagamento para acessar o sistema.';
    $_SESSION['erro_assinatura'] = $mensagem_erro;
    header("Location: ../pages/assinar.php");
    exit;
}

// --- 5. Conecta ao banco do tenant (verificando existência antes) ---
$tenantDb = $tenant['db_database'];

try {
    $checkConn = new mysqli($tenant['db_host'], $tenant['db_user'], $tenant['db_password']);
    $checkConn->set_charset("utf8mb4");

    $check = $checkConn->query("SHOW DATABASES LIKE '{$tenantDb}'");
    if ($check->num_rows === 0) {
        error_log("❌ Banco do tenant não encontrado: {$tenantDb}");
        $_SESSION['erro_login'] = 'Banco de dados do cliente não foi encontrado. Entre em contato com o suporte.';
        header("Location: ../pages/login.php?msg=db_inexistente");
        exit;
    }
    $checkConn->close();

    // Conecta ao banco do tenant
    $tenant_conn = new mysqli(
        $tenant['db_host'],
        $tenant['db_user'],
        $tenant['db_password'],
        $tenant['db_database']
    );
    $tenant_conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log("Erro MySQL ao conectar tenant: " . $e->getMessage());
    $_SESSION['erro_login'] = 'Erro interno ao conectar ao banco do cliente.';
    header("Location: ../pages/login.php?msg=db_tenant");
    exit;
}

// --- 6. Busca usuário dentro do tenant ---
$stmt = $tenant_conn->prepare("SELECT * FROM usuarios WHERE LOWER(email) = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$tenant_conn->close();

if (!$user || !password_verify($senha, $user['senha'])) {
    $_SESSION['erro_login'] = 'E-mail ou senha inválidos.';
    header("Location: ../pages/login.php?msg=login_invalido");
    exit;
}

// --- 7. Cria sessão ---
$_SESSION['usuario_logado'] = [
    'id' => $user['id'],
    'nome' => $user['nome'],
    'email' => $user['email'],
    'nivel_acesso' => $user['nivel_acesso'],
    'tenant_id' => $tenant['id']
];

// --- 8. Armazena credenciais do banco ---
$_SESSION['tenant_db'] = [
    'db_host' => $tenant['db_host'],
    'db_database' => $tenant['db_database'],
    'db_user' => $tenant['db_user'],
    'db_password' => $tenant['db_password']
];

// --- 9. Redireciona ---
header("Location: ../pages/selecionar_usuario.php");
exit;
