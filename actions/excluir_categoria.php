<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if (isset($_GET['id'])) {
    $usuarioId = $_SESSION['usuario']['id'];
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id, $usuarioId);
    $stmt->execute();
    $stmt->close();
}

header('Location: ../pages/categorias.php');
exit;
?>