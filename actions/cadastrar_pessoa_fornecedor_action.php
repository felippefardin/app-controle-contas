<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id'];
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $endereco = $_POST['endereco'];
    $contato = $_POST['contato'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];

    $stmt = $conn->prepare("INSERT INTO pessoas_fornecedores (id_usuario, nome, cpf_cnpj, endereco, contato, email, tipo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $id_usuario, $nome, $cpf_cnpj, $endereco, $contato, $email, $tipo);
    
    if ($stmt->execute()) {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?sucesso=1');
    } else {
        header('Location: ../pagescadastrar_pessoa_fornecedor.php?erro=1');
    }
    $stmt->close();
    $conn->close();
}
?>