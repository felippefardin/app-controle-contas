<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}
$conn = getTenantConnection();

if (isset($_GET['id'])) {
    $id_usuario = $_SESSION['usuario_logado']['id'];
    $id_registro = (int)$_GET['id'];

    if ($conn && $id_registro > 0) {
        // 2. A CLÁUSULA `id_usuario = ?` GARANTE A SEGURANÇA
        $stmt = $conn->prepare("DELETE FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_registro, $id_usuario);
        
        if ($stmt->execute()) {
            header('Location: ../pages/cadastrar_pessoa_fornecedor.php?sucesso_exclusao=1');
        } else {
            header('Location: ../pages/cadastrar_pessoa_fornecedor.php?erro_exclusao=1');
        }
        $stmt->close();
    } else {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?erro=db_or_id');
    }
} else {
    header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
}
exit;
?>