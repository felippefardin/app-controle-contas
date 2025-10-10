<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Verifica a página de origem para o redirecionamento
    $origem = $_GET['origem'] ?? 'pendentes';
    $redirectPage = ($origem === 'baixadas') ? 'contas_receber_baixadas.php' : 'contas_receber.php';

    // Prepara e executa a exclusão
    $stmt = $conn->prepare("DELETE FROM contas_receber WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redireciona para a página de origem com uma mensagem de sucesso
        header("Location: ../pages/{$redirectPage}?msg=Conta excluída com sucesso!");
        exit();
    } else {
        // Em caso de erro, redireciona com uma mensagem de erro
        header("Location: ../pages/{$redirectPage}?erro=Erro ao excluir a conta.");
        exit();
    }
} else {
    // Se nenhum ID for fornecido, volta para a página principal de contas a receber
    header("Location: ../pages/contas_receber.php?erro=ID da conta não especificado.");
    exit();
}
?>