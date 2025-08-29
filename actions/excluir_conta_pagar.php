<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

// Inicializa a conexão
$conn = getConnPrincipal();

// Verifica se veio o ID na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}

$id = intval($_GET['id']);

// Prepara e executa o DELETE
$stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // Redireciona de volta para a tela de contas a pagar
    header('Location: ../pages/contas_pagar_baixadas.php?excluido=1');
    exit;
} else {
    die("Erro ao excluir conta: " . $conn->error);
}
?>
