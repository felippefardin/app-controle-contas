<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

// Verifica se veio o ID na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido.";
    exit;
}
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $conn->query("DELETE FROM contas_pagar WHERE id = $id");
}

// Redireciona de volta com parâmetro de sucesso
header("Location: ../pages/contas_pagar_baixadas.php?excluido=1");
exit;

$id = intval($_GET['id']);

// Executa o delete
$stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redireciona de volta para a tela de contas a pagar
    header('Location: ../pages/contas_pagar.php');
    exit;
} else {
    echo "Erro ao excluir conta: " . $conn->error;
}
