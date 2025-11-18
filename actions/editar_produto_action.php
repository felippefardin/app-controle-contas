<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. PROCESSA SOMENTE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn = getTenantConnection();
    if (!$conn) {
        header('Location: ../pages/controle_estoque.php?error=db_connection');
        exit;
    }

    // Sessão correta
    $id_usuario = $_SESSION['usuario_id'];

    // Coleta dos dados do formulário
    $id = intval($_POST['id']);
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'] ?? '';
    $quantidade_estoque = intval($_POST['quantidade_estoque']);
    $quantidade_minima = intval($_POST['quantidade_minima'] ?? 0);

    // Ajuste de valores monetários (troca vírgula por ponto)
    $preco_compra = !empty($_POST['preco_compra']) ? floatval(str_replace(',', '.', $_POST['preco_compra'])) : 0.00;
    $preco_venda = !empty($_POST['preco_venda']) ? floatval(str_replace(',', '.', $_POST['preco_venda'])) : 0.00;

    $ncm = $_POST['ncm'] ?? null;
    $cfop = $_POST['cfop'] ?? null;

    // 3. QUERY DE ATUALIZAÇÃO
    $sql = "UPDATE produtos 
            SET nome = ?, descricao = ?, quantidade_estoque = ?, quantidade_minima = ?, 
                preco_compra = ?, preco_venda = ?, ncm = ?, cfop = ?
            WHERE id = ? AND id_usuario = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header('Location: ../pages/controle_estoque.php?error=prepare_failed');
        exit;
    }

    // BINDS CORRETOS:
    // s s i i d d s s i i  
    $stmt->bind_param(
        "ssiiddssii",
        $nome, $descricao,
        $quantidade_estoque, $quantidade_minima,
        $preco_compra, $preco_venda,
        $ncm, $cfop,
        $id, $id_usuario
    );

    // 4. EXECUÇÃO
    if ($stmt->execute()) {
        header('Location: ../pages/controle_estoque.php?success=produto_atualizado');
        exit;
    } else {
        header('Location: ../pages/controle_estoque.php?error=update_failed');
        exit;
    }

} else {
    header('Location: ../pages/controle_estoque.php');
    exit;
}
?>
