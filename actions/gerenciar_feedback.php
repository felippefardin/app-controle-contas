<?php
require_once '../includes/session_init.php';
include('../database.php');

// Verificação de permissão admin necessária aqui

if (isset($_POST['acao']) && isset($_POST['id'])) {
    $conn = getMasterConnection();
    $id = intval($_POST['id']);
    $acao = $_POST['acao'];

    if ($acao === 'aprovar') {
        $conn->query("UPDATE feedbacks SET aprovado = 1, lido = 1 WHERE id = $id");
    } elseif ($acao === 'reprovar') {
        $conn->query("UPDATE feedbacks SET aprovado = 0, lido = 1 WHERE id = $id"); // Apenas marca como lido e oculta
    } elseif ($acao === 'excluir') {
        $conn->query("DELETE FROM feedbacks WHERE id = $id");
    }
    
    header("Location: ../pages/admin/feedback.php");
}
?>