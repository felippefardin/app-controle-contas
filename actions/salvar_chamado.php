<?php
require_once '../includes/session_init.php';
require_once '../database.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido.");
}

if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

$connMaster = getMasterConnection();
// Recupera o ID do tenant (String UUID)
$tenant_id = $_SESSION['tenant_id']; 

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_email = $_POST['email_fixo'];
$tipo = $_POST['tipo_suporte']; 
$descricao = trim($_POST['descricao']);
$plano = strtolower($_SESSION['plano'] ?? 'basico');
$mes_atual = date('Y-m');

// 1. Regras
$regras = [
    'basico' => ['cota_online' => 0, 'cota_aovivo' => 0, 'preco_online' => 5.99, 'preco_aovivo' => 15.99],
    'plus' => ['cota_online' => 1, 'cota_aovivo' => 1, 'preco_online' => 8.99, 'preco_aovivo' => 15.99],
    'essencial' => ['cota_online' => 2, 'cota_aovivo' => 1, 'preco_online' => 8.99, 'preco_aovivo' => 15.99]
];
$regra_atual = $regras[$plano] ?? $regras['basico'];

// 2. Verifica uso (tenant_id é string 's')
$uso_online = 0;
$uso_aovivo = 0;

$stmt = $connMaster->prepare("SELECT uso_chat_online, uso_chat_aovivo FROM suporte_usage WHERE tenant_id = ? AND mes_ano = ?");
$stmt->bind_param("ss", $tenant_id, $mes_atual); // CORRIGIDO: 'ss' (string, string)
$stmt->execute();
$stmt->bind_result($uso_online, $uso_aovivo);
$stmt->fetch();
$stmt->close();

// 3. Calcula Custo
$custo = 0.00;
if ($tipo === 'chat_online') {
    $custo = ($uso_online < $regra_atual['cota_online']) ? 0.00 : $regra_atual['preco_online'];
    $campo_sql_update = "uso_chat_online";
} else {
    $custo = ($uso_aovivo < $regra_atual['cota_aovivo']) ? 0.00 : $regra_atual['preco_aovivo'];
    $campo_sql_update = "uso_chat_aovivo";
}

// 4. Salva Chamado (tenant_id é string 's')
$stmt = $connMaster->prepare("INSERT INTO chamados_suporte (tenant_id, usuario_id, usuario_nome, usuario_email, tipo, descricao, custo, mes_referencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
// CORRIGIDO: 'sissssds' -> o primeiro 's' é para o tenant_id
$stmt->bind_param("sissssds", $tenant_id, $usuario_id, $usuario_nome, $usuario_email, $tipo, $descricao, $custo, $mes_atual);

if ($stmt->execute()) {
    // 5. Atualiza Uso
    $sql_usage = "INSERT INTO suporte_usage (tenant_id, mes_ano, $campo_sql_update) VALUES (?, ?, 1) 
                  ON DUPLICATE KEY UPDATE $campo_sql_update = $campo_sql_update + 1";
    $stmt_up = $connMaster->prepare($sql_usage);
    $stmt_up->bind_param("ss", $tenant_id, $mes_atual); // CORRIGIDO: 'ss'
    $stmt_up->execute();
    
    $_SESSION['perfil_msg'] = "Chamado aberto com sucesso! Custo: R$ " . number_format($custo, 2, ',', '.');
} else {
    $_SESSION['perfil_erro'] = "Erro ao abrir chamado.";
}

header("Location: ../pages/perfil.php");
exit;