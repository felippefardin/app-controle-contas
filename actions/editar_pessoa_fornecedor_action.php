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
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $endereco = $_POST['endereco'];
    $contato = $_POST['contato'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];

    // A cláusula WHERE id_usuario garante que um usuário não pode editar o registro de outro
    $stmt = $conn->prepare("UPDATE pessoas_fornecedores SET nome=?, cpf_cnpj=?, endereco=?, contato=?, email=?, tipo=? WHERE id=? AND id_usuario=?");
    $stmt->bind_param("ssssssii", $nome, $cpf_cnpj, $endereco, $contato, $email, $tipo, $id, $id_usuario);
    
    if ($stmt->execute()) {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?sucesso_edicao=1');
    } else {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?erro_edicao=1');
    }
    $stmt->close();
    $conn->close();
}
?>