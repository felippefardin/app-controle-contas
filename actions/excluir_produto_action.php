<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_produto = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM produtos WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_produto, $id_usuario);

if ($stmt->execute()) {
    header('Location: ../pages/controle_estoque.php');
} else {
    echo "Erro ao excluir produto: " . $conn->error;
}
?>