<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Proteção contra SQL Injection

    // Verifica de qual página a solicitação veio para redirecionar corretamente
    $origem = $_GET['origem'] ?? 'pendentes';
    $redirectPage = ($origem === 'baixadas') ? 'contas_pagar_baixadas.php' : 'contas_pagar.php';

    // Usar prepared statements para segurança
    $stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ?");
    if ($stmt === false) {
        // Tratar erro na preparação da query
        header("Location: ../pages/{$redirectPage}?erro=Erro ao preparar a exclusão.");
        exit();
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redireciona para a página de origem com mensagem de sucesso
        header("Location: ../pages/{$redirectPage}?msg=Conta excluída com sucesso!");
        exit();
    } else {
        // Redireciona para a página de origem com mensagem de erro
        header("Location: ../pages/{$redirectPage}?erro=Erro ao executar a exclusão.");
        exit();
    }
} else {
    // Se nenhum ID for fornecido, redireciona para a página principal
    header("Location: ../pages/contas_pagar.php?erro=ID da conta não especificado.");
    exit();
}
?>