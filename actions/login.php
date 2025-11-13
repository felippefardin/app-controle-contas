<?php
session_start();
require_once '../database.php';
require_once '../includes/session_init.php';
require_once '../includes/tenant_utils.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método inválido.');
}

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if ($email === '' || $senha === '') {
    die('Preencha todos os campos.');
}

// Conexão com banco master
$conn = getMasterConnection();

// Consulta usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die('Usuário não encontrado.');
}

if (!password_verify($senha, $user['senha'])) {
    die('Senha incorreta.');
}

// Busca tenant vinculado ao usuário
$stmt = $conn->prepare("SELECT * FROM tenants WHERE usuario_id = ? LIMIT 1");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();

if (!$tenant) {
    die('Nenhum tenant associado ao usuário.');
}

// Atualiza data_atualizacao do tenant
$stmt = $conn->prepare("UPDATE tenants SET data_atualizacao = NOW() WHERE id = ?");
$stmt->bind_param('i', $tenant['id']);
$stmt->execute();

// Carrega tenant na sessão
carregarTenantNaSessao($tenant);

// Garante existência do banco do tenant
ensureTenantDatabaseExists(
    $tenant['db_host'],
    $tenant['db_user'],
    $tenant['db_password'],
    $tenant['db_database']
);

// Teste de conexão para confirmar criação
$tenantConn = getTenantConnection();
if (!$tenantConn) {
    die('Erro ao conectar ao banco do tenant.');
}

// Verifica status da assinatura
if ($tenant['status_assinatura'] === 'inativo') {
    die('Sua assinatura está inativa. Entre em contato com o suporte.');
}

// Login OK
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_id'] = $user['id'];
$_SESSION['email'] = $user['email'];

header('Location: ../pages/home.php');
exit();
