<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_GET['id'])) {
    header('Location: ../pages/lancamento_caixa.php');
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM caixa_diario WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: ../pages/lancamento_caixa.php?success=excluido');
} else {
    header('Location: ../pages/lancamento_caixa.php?error=erro');
}
$stmt->close();
?>
