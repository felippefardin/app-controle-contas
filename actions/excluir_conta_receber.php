<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

if (isset($_GET['id'])) {
    $conn = getTenantConnection();
    if ($conn === null) {
        header("Location: ../pages/contas_receber.php?erro=db_connection");
        exit;
    }
    
    $id_usuario = $_SESSION['usuario_logado']['id'];
    $id_conta = intval($_GET['id']);
    $origem = $_GET['origem'] ?? 'pendentes';
    $redirectPage = ($origem === 'baixadas') ? 'contas_receber_baixadas.php' : 'contas_receber.php';

    // 2. EXCLUI A CONTA COM SEGURANÇA
    $stmt = $conn->prepare("DELETE FROM contas_receber WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_conta, $id_usuario);

    if ($stmt->execute()) {
        header("Location: ../pages/{$redirectPage}?msg=Conta excluída com sucesso!");
    } else {
        header("Location: ../pages/{$redirectPage}?erro=Erro ao excluir a conta.");
    }
    $stmt->close();
    exit;
} else {
    header("Location: ../pages/contas_receber.php?erro=ID da conta não especificado.");
    exit;
}
?>