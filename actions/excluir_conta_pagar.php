<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $origem = $_GET['origem'] ?? 'pendentes';
    $redirectPage = ($origem === 'baixadas') ? 'contas_pagar_baixadas.php' : 'contas_pagar.php';

    $stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ?");
    if ($stmt === false) {
        // $_SESSION['error_message'] = "Erro ao preparar a exclusão.";
        header("Location: ../pages/{$redirectPage}");
        exit();
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta excluída com sucesso!";
    } else {
        // $_SESSION['error_message'] = "Erro ao executar a exclusão.";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: ../pages/{$redirectPage}");
    exit();
} else {
    // $_SESSION['error_message'] = "ID da conta não especificado.";
    header("Location: ../pages/contas_pagar.php");
    exit();
}
?>