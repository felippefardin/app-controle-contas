<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id'];
    $nome_banco = $_POST['nome_banco'];
    $agencia = $_POST['agencia'];
    $conta = $_POST['conta'];
    $tipo_conta = $_POST['tipo_conta'];
    $chave_pix = $_POST['chave_pix'];

    $stmt = $conn->prepare("INSERT INTO contas_bancarias (id_usuario, nome_banco, agencia, conta, tipo_conta, chave_pix) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $id_usuario, $nome_banco, $agencia, $conta, $tipo_conta, $chave_pix);
    
    if ($stmt->execute()) {
        header('Location: ../pages/banco_cadastro.php?sucesso=1');
    } else {
        header('Location: ../pages/banco_cadastro.php?erro=1');
    }
    $stmt->close();
    $conn->close();
}
?>