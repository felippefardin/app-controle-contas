<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

if (isset($_GET['id'])) {
    $conn = getTenantConnection();
    if ($conn === null) {
        header('Location: ../pages/controle_estoque.php?error=db_connection');
        exit;
    }

    $id_usuario = $_SESSION['usuario_logado']['id'];
    $id_produto = (int)$_GET['id'];

    // 2. EXCLUI O PRODUTO COM SEGURANÇA
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_produto, $id_usuario);

    if ($stmt->execute()) {
        header('Location: ../pages/controle_estoque.php?success=delete');
    } else {
        header('Location: ../pages/controle_estoque.php?error=delete_failed');
    }
    $stmt->close();
    exit;
} else {
    header('Location: ../pages/controle_estoque.php');
    exit;
}
?>