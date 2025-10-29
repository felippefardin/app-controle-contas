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

    // Coleta os dados do formulário
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'] ?? '';
    $quantidade_estoque = $_POST['quantidade_estoque'];
    $preco_compra = !empty($_POST['preco_compra']) ? str_replace(',', '.', $_POST['preco_compra']) : 0.00;
    $preco_venda = !empty($_POST['preco_venda']) ? str_replace(',', '.', $_POST['preco_venda']) : 0.00;
    $ncm = $_POST['ncm'] ?? null;
    $cfop = $_POST['cfop'] ?? null;

    // 3. INSERE OS DADOS NO BANCO
    $sql = "INSERT INTO produtos (nome, descricao, quantidade_estoque, preco_compra, preco_venda, ncm, cfop, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        header('Location: ../pages/controle_estoque.php?error=prepare_failed');
        exit;
    }

    $stmt->bind_param("ssiddssi", $nome, $descricao, $quantidade_estoque, $preco_compra, $preco_venda, $ncm, $cfop, $id_usuario);

    if ($stmt->execute()) {
        header('Location: ../pages/controle_estoque.php?success=1');
    } else {
        header('Location: ../pages/controle_estoque.php?error=execute_failed');
    }
    $stmt->close();
    exit;
} else {
    header('Location: ../pages/controle_estoque.php');
    exit;
}
?>