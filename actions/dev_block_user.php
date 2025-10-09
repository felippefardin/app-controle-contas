<?php
session_start();
include('../database.php');

// Segurança: Apenas continua se o acesso de desenvolvedor for válido
if (!isset($_SESSION['developer_access']) || $_SESSION['developer_access'] !== true) {
    die('Acesso negado.');
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $userId = intval($_GET['id']);
    $status = $_GET['status'] === 'bloqueado' ? 'bloqueado' : 'ativo';

    $stmt = $conn->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $userId);
    $stmt->execute();
    $stmt->close();
}

header('Location: ../pages/tela_desenvolvedor.php');
exit;