<?php
// actions/comprar_extra_action.php
session_start();
require_once '../database.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['usuario_logado']) || !$_SESSION['tenant_id']) {
    header("Location: ../pages/login.php");
    exit;
}

$conn = getMasterConnection();
$tenant_id = $_SESSION['tenant_id'];
$qtd_extra = (int)($_POST['qtd_extra'] ?? 1);

if ($qtd_extra < 1) $qtd_extra = 1;

try {
    $stmt = $conn->prepare("UPDATE tenants SET usuarios_extras = usuarios_extras + ? WHERE tenant_id = ?");
    $stmt->bind_param("is", $qtd_extra, $tenant_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', "Sucesso! $qtd_extra vaga(s) adicionada(s).");
    } else {
        set_flash_message('danger', "Erro ao processar solicitação.");
    }
    $stmt->close();

} catch (Exception $e) {
    set_flash_message('danger', "Erro interno: " . $e->getMessage());
}

$conn->close();
header("Location: ../pages/minha_assinatura.php");
exit;
?>