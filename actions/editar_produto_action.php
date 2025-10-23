<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_produto = $_POST['id'];
$nome = $_POST['nome'];
$descricao = $_POST['descricao'];
$preco_compra = !empty($_POST['preco_compra']) ? $_POST['preco_compra'] : null;
$preco_venda = !empty($_POST['preco_venda']) ? $_POST['preco_venda'] : null;
$quantidade = $_POST['quantidade'];
$unidade_medida = $_POST['unidade_medida'];
$quantidade_minima = isset($_POST['quantidade_minima']) ? (int)$_POST['quantidade_minima'] : 0; // Adicionado

$stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco_compra = ?, preco_venda = ?, quantidade = ?, unidade_medida = ?, quantidade_minima = ? WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("sssdisiii", $nome, $descricao, $preco_compra, $preco_venda, $quantidade, $unidade_medida, $quantidade_minima, $id_produto, $id_usuario); // Ajustado

if ($stmt->execute()) {
    header('Location: ../pages/controle_estoque.php');
} else {
    echo "Erro ao editar produto: " . $conn->error;
}
?>