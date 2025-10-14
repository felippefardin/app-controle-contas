<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id_usuario = $_SESSION['usuario']['id'];
    $id_registro = $_GET['id'];

    // A cláusula WHERE id_usuario garante que um usuário não pode excluir o registro de outro
    $stmt = $conn->prepare("DELETE FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_registro, $id_usuario);
    
    if ($stmt->execute()) {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?sucesso_exclusao=1');
    } else {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?erro_exclusao=1');
    }
    $stmt->close();
    $conn->close();
} else {
    header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
}
?>