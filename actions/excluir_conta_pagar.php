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
        $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    $id_usuario = $_SESSION['usuario_logado']['id'];
    $id_conta = (int)$_GET['id'];
    
    $origem = $_GET['origem'] ?? 'pendentes';
    $redirectPage = ($origem === 'baixadas') ? 'contas_pagar_baixadas.php' : 'contas_pagar.php';

    // 2. EXCLUI A CONTA COM SEGURANÇA
    // A cláusula `AND usuario_id = ?` é crucial para a segurança
    $stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_conta, $id_usuario);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta excluída com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao excluir a conta.";
    }
    
    $stmt->close();
    header("Location: ../pages/{$redirectPage}");
    exit;
} else {
    $_SESSION['error_message'] = "ID da conta não especificado.";
    header("Location: ../pages/contas_pagar.php");
    exit;
}
?>