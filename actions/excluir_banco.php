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
        header('Location: ../pages/banco_cadastro.php?erro_exclusao=db_connection');
        exit;
    }

    $id_usuario = $_SESSION['usuario_logado']['id'];
    $id_registro = (int)$_GET['id'];

    // 2. EXCLUI O REGISTRO COM SEGURANÇA
    $stmt = $conn->prepare("DELETE FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_registro, $id_usuario);
    
    if ($stmt->execute()) {
        header('Location: ../pages/banco_cadastro.php?sucesso_exclusao=1');
    } else {
        header('Location: ../pages/banco_cadastro.php?erro_exclusao=1');
    }
    $stmt->close();
    exit;
} else {
    header('Location: ../pages/banco_cadastro.php');
    exit;
}
?>