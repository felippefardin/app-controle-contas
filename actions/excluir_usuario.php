<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'admin') {
    echo "Acesso negado";
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Usuário não informado.";
    exit;
}

$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: ../pages/usuarios.php');
