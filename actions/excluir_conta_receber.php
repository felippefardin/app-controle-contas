<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');


if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Garante que o ID é inteiro para segurança

    // Preparar e executar exclusão segura
    $stmt = $conn->prepare("DELETE FROM contas_receber WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redireciona para a listagem após excluir
        header("Location: ../pages/contas_receber_baixadas.php?msg=Conta excluída com sucesso");
        exit();
    } else {
        echo "Erro ao excluir a conta: " . $conn->error;
    }
} else {
    echo "ID da conta não especificado.";
}
?>
