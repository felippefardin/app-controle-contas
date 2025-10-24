<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$preco_compra = $_POST['preco_compra'];
$preco_venda = $_POST['preco_venda'];
$quantidade = $_POST['quantidade'];
$unidade_medida = $_POST['unidade_medida'];
$quantidade_minima = $_POST['quantidade_minima'];

$stmt = $conn->prepare("INSERT INTO produtos (id_usuario, nome, descricao, preco_compra, preco_venda, quantidade, unidade_medida, quantidade_minima) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssdisi", $id_usuario, $nome, $descricao, $preco_compra, $preco_venda, $quantidade, $unidade_medida, $quantidade_minima);

if ($stmt->execute()) {
    header('Location: ../pages/controle_estoque.php');
} else {
    echo "Erro ao cadastrar produto: " . $conn->error;
}
?>