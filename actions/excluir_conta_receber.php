<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado'])) { header('Location: ../pages/login.php'); exit; }

$id_conta = (int)$_GET['id'];
$redirect = $_GET['redirect'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

if ($id_conta > 0) {
    $conn = getTenantConnection();
    $stmt = $conn->prepare("DELETE FROM contas_receber WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_conta, $usuario_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta excluída.";
    } else {
        $_SESSION['error_message'] = "Erro ao excluir.";
    }
    $stmt->close();
}

if ($redirect === 'baixadas') {
    header('Location: ../pages/contas_receber_baixadas.php');
} else {
    header('Location: ../pages/contas_receber.php');
}
exit;
?>