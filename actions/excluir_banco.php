<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN
// Verifica se a sessão está ativa (true)
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

if (isset($_GET['id'])) {
    $conn = getTenantConnection();
    if ($conn === null) {
        header('Location: ../pages/banco_cadastro.php?erro_exclusao=db_connection');
        exit;
    }

    // --- CORREÇÃO AQUI ---
    // O ID do usuário agora está em $_SESSION['usuario_id']
    $id_usuario = $_SESSION['usuario_id'];
    $id_registro = (int)$_GET['id'];

    // Validação extra
    if (empty($id_usuario)) {
         header('Location: ../pages/login.php?error=session_error');
         exit;
    }

    // 2. EXCLUI O REGISTRO COM SEGURANÇA
    $stmt = $conn->prepare("DELETE FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_registro, $id_usuario);
    
    if ($stmt->execute()) {
        // Verifica se alguma linha foi realmente afetada (excluída)
        if ($stmt->affected_rows > 0) {
            header('Location: ../pages/banco_cadastro.php?sucesso_exclusao=1');
        } else {
            // Se não afetou linhas, o ID não existe ou não pertence ao usuário
            header('Location: ../pages/banco_cadastro.php?erro_exclusao=not_found');
        }
    } else {
        header('Location: ../pages/banco_cadastro.php?erro_exclusao=1');
    }
    $stmt->close();
    $conn->close();
    exit;
} else {
    header('Location: ../pages/banco_cadastro.php');
    exit;
}
?>