<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        header('Location: ../pages/controle_estoque.php?error=db_connection');
        exit;
    }

    // Pega o ID do usuário da sessão correta
    $id_usuario = $_SESSION['usuario_logado']['id'];

    // Coleta dos dados do formulário
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'] ?? '';
    $quantidade_estoque = $_POST['quantidade_estoque'];
    $quantidade_minima = $_POST['quantidade_minima'] ?? 0;
    $preco_compra = !empty($_POST['preco_compra']) ? str_replace(',', '.', $_POST['preco_compra']) : 0.00;
    $preco_venda = !empty($_POST['preco_venda']) ? str_replace(',', '.', $_POST['preco_venda']) : 0.00;
    $ncm = $_POST['ncm'] ?? null;
    $cfop = $_POST['cfop'] ?? null;

    // 3. ATUALIZA O PRODUTO COM SEGURANÇA
    $sql = "UPDATE produtos SET nome = ?, descricao = ?, quantidade_estoque = ?, quantidade_minima = ?, preco_compra = ?, preco_venda = ?, ncm = ?, cfop = ? WHERE id = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        header('Location: ../pages/controle_estoque.php?error=prepare_failed');
        exit;
    }
    
    $stmt->bind_param("ssiiddssii", $nome, $descricao, $quantidade_estoque, $quantidade_minima, $preco_compra, $preco_venda, $ncm, $cfop, $id, $id_usuario);

    if ($stmt->execute()) {
        header('Location: ../pages/controle_estoque.php?success=update');
    } else {
        header('Location: ../pages/controle_estoque.php?error=update_failed');
    }
    $stmt->close();
    exit;
} else {
    header('Location: ../pages/controle_estoque.php');
    exit;
}
?>