<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id'];
    $id = $_POST['id'];
    $nome_banco = $_POST['nome_banco'];
    $agencia = $_POST['agencia'];
    $conta = $_POST['conta'];
    $tipo_conta = $_POST['tipo_conta'];
    $chave_pix = $_POST['chave_pix'];

    // A cláusula WHERE id_usuario garante que um usuário não pode editar a conta de outro
    $stmt = $conn->prepare("UPDATE contas_bancarias SET nome_banco=?, agencia=?, conta=?, tipo_conta=?, chave_pix=? WHERE id=? AND id_usuario=?");
    $stmt->bind_param("sssssii", $nome_banco, $agencia, $conta, $tipo_conta, $chave_pix, $id, $id_usuario);
    
    if ($stmt->execute()) {
        header('Location: ../pages/banco_cadastro.php?sucesso_edicao=1');
    } else {
        header('Location: ../pages/banco_cadastro.php?erro_edicao=1');
    }
    $stmt->close();
    $conn->close();
}
?>