<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O ID FOI INFORMADO
if (!isset($_GET['id'])) {
    $_SESSION['msg_erro_produto'] = "Produto não especificado.";
    header('Location: ../pages/controle_estoque.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    $_SESSION['msg_erro_produto'] = "Falha na conexão com o banco de dados.";
    header('Location: ../pages/controle_estoque.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$id_produto = (int)$_GET['id'];

// 3. EXCLUI O PRODUTO COM SEGURANÇA
$stmt = $conn->prepare("DELETE FROM produtos WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_produto, $id_usuario);

if ($stmt->execute()) {
    $_SESSION['msg_produto'] = "Produto excluído com sucesso.";
} else {
    $_SESSION['msg_erro_produto'] = "Falha ao excluir o produto.";
}

$stmt->close();
header('Location: ../pages/controle_estoque.php');
exit;
?>
