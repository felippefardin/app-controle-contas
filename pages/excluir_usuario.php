<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$conn = getConnPrincipal();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID do usuário não especificado.");
}

$id = intval($_GET['id']);

// Proteção simples: você pode adicionar checagem para não excluir a si mesmo ou admin, por exemplo

$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ../pages/usuarios.php'); // ajuste o caminho para onde quer voltar
    exit;
} else {
    die("Erro ao excluir usuário: " . $conn->error);
}
?>
