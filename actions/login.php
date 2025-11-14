<?php
// actions/login_action.php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/tenant_utils.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método inválido.');

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');
if ($email === '' || $senha === '') die('Preencha todos os campos.');

try {
    $master = getMasterConnection();
    $stmt = $master->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $masterUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$masterUser) { log_debug("master user not found: $email"); die('Usuário não encontrado.'); }
    if (!password_verify($senha, $masterUser['senha'])) { log_debug("senha incorreta: $email"); die('Senha incorreta.'); }

    $tenant = getTenantByUserId((int)$masterUser['id']);
    if (!$tenant) { log_debug("tenant não encontrado para usuario_id={$masterUser['id']}"); die('Nenhum tenant associado ao usuário.'); }

    $status = validarStatusAssinatura($tenant);
    if ($status !== "ok") { die("Assinatura inválida: {$status}"); }

    carregarTenantNaSessao($tenant);
    ensureTenantDatabaseExists($tenant['db_host'], $tenant['db_user'], $tenant['db_password'], $tenant['db_database']);

    $tenantConn = getTenantConnection();
    if (!$tenantConn) { log_debug("falha ao conectar tenant"); die('Erro ao conectar ao banco do tenant.'); }

    $stmtT = $tenantConn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmtT->bind_param("s", $email);
    $stmtT->execute();
    $tenantUser = $stmtT->get_result()->fetch_assoc();
    $stmtT->close();

    if (!$tenantUser) {
        // cria usuario no tenant automaticamente
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmtC = $tenantConn->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tipo_pessoa, documento, telefone, tenant_id) VALUES (?, ?, ?, 'admin', 'ativo', '', '', '', ?)");
        $stmtC->bind_param("ssss", $masterUser['nome'], $masterUser['email'], $senha_hash, $tenant['tenant_id']);
        $stmtC->execute();
        $insertId = $tenantConn->insert_id;
        $stmtC->close();

        $stmtG = $tenantConn->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        $stmtG->bind_param("i", $insertId);
        $stmtG->execute();
        $tenantUser = $stmtG->get_result()->fetch_assoc();
        $stmtG->close();
        if (!$tenantUser) { die("Erro interno ao sincronizar usuário no tenant."); }
    }

    // grava sessão
    $_SESSION['usuario_logado'] = true;
    $_SESSION['usuario_id'] = (int)$tenantUser['id'];
    $_SESSION['usuario_master_id'] = (int)$masterUser['id'];
    $_SESSION['email'] = $tenantUser['email'];
    $_SESSION['nome'] = $tenantUser['nome'];
    $_SESSION['nivel_acesso'] = $tenantUser['nivel_acesso'] ?? 'padrao';
    $_SESSION['tenant_id'] = $tenant['tenant_id'];

    header("Location: ../pages/home.php");
    exit;

} catch (Exception $e) {
    error_log("[LOGIN ERROR] " . $e->getMessage());
    die("Erro no login. Tente novamente.");
}
