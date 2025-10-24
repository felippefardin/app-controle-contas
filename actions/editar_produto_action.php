<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coleta dos dados do formulário
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'] ?? '';
    $quantidade_estoque = $_POST['quantidade_estoque'];
    $preco_compra = !empty($_POST['preco_compra']) ? str_replace(',', '.', $_POST['preco_compra']) : 0.00;
    $preco_venda = !empty($_POST['preco_venda']) ? str_replace(',', '.', $_POST['preco_venda']) : 0.00;
    $ncm = $_POST['ncm'] ?? null;
    $cfop = $_POST['cfop'] ?? null;
    $id_usuario = $_SESSION['usuario']['id'];

    // SQL para atualizar o produto
    $sql = "UPDATE produtos SET 
                nome = ?, 
                descricao = ?, 
                quantidade_estoque = ?, 
                preco_compra = ?, 
                preco_venda = ?, 
                ncm = ?, 
                cfop = ?
            WHERE id = ? AND id_usuario = ?";

    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die('Erro ao preparar a query: ' . $conn->error);
    }
    
    // Associa os parâmetros
    $stmt->bind_param(
        "ssiddssii",
        $nome,
        $descricao,
        $quantidade_estoque,
        $preco_compra,
        $preco_venda,
        $ncm,
        $cfop,
        $id,
        $id_usuario
    );

    // Executa e redireciona
    if ($stmt->execute()) {
        header('Location: ../pages/controle_estoque.php?success=update');
    } else {
        echo "Erro ao atualizar o produto: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: ../pages/controle_estoque.php');
    exit;
}
?>