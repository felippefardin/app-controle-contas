<?php
// actions/comprar_extra_action.php
session_start();
require_once '../database.php';

// 1. Verifica permissão (apenas o dono da conta pode comprar)
if (!isset($_SESSION['usuario_logado']) || !$_SESSION['tenant_id']) {
    header("Location: ../pages/login.php");
    exit;
}

// 2. Conexão Master
$conn = getMasterConnection();
$tenant_id = $_SESSION['tenant_id'];
$qtd_extra = (int)($_POST['qtd_extra'] ?? 1);

if ($qtd_extra < 1) $qtd_extra = 1;

// Valor unitário ATUALIZADO
$valor_unitario = 4.50; 
$valor_total_adicional = $qtd_extra * $valor_unitario;

try {
    // Atualiza a quantidade de usuários extras no banco
    // Aqui você também poderia registrar esse valor na tabela 'assinaturas' ou 'faturas' para cobrança futura
    $stmt = $conn->prepare("UPDATE tenants SET usuarios_extras = usuarios_extras + ? WHERE tenant_id = ?");
    $stmt->bind_param("is", $qtd_extra, $tenant_id);
    
    if ($stmt->execute()) {
        $_SESSION['sucesso_extra'] = "Sucesso! $qtd_extra vaga(s) adicionada(s). Seu limite aumentou.";
    } else {
        $_SESSION['erro_extra'] = "Erro ao processar solicitação.";
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['erro_extra'] = "Erro interno: " . $e->getMessage();
}

$conn->close();
header("Location: ../pages/minha_assinatura.php");
exit;
?>