<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_cliente = $_POST['id_cliente'];
$valor_total = $_POST['valor_total'];
$forma_pagamento = $_POST['forma_pagamento'];
$numero_parcelas = isset($_POST['numero_parcelas']) ? $_POST['numero_parcelas'] : 1;
$observacao = $_POST['observacao'];
$produtos = $_POST['produtos'];

$conn->begin_transaction();

try {
    // 🔹 1. Inserir a venda principal
    $stmt = $conn->prepare("
        INSERT INTO vendas 
        (id_usuario, id_cliente, valor_total, forma_pagamento, numero_parcelas, observacao) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    // Tipos: i (int), i (int), d (decimal), s (string), i (int), s (string)
    $stmt->bind_param("iidsis", $id_usuario, $id_cliente, $valor_total, $forma_pagamento, $numero_parcelas, $observacao);
    $stmt->execute();
    $id_venda = $conn->insert_id;

    // 🔹 2. Inserir itens da venda e atualizar o estoque
    foreach ($produtos as $produto) {
        $id_produto = $produto['id'];
        $quantidade = $produto['quantidade'];
        $preco_unitario = str_replace(',', '.', $produto['preco']);
        $subtotal = $quantidade * $preco_unitario;

        // Inserir item
        $stmt_item = $conn->prepare("
            INSERT INTO venda_items (id_venda, id_produto, quantidade, preco_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_item->bind_param("iiidd", $id_venda, $id_produto, $quantidade, $preco_unitario, $subtotal);
        $stmt_item->execute();

        // Atualizar estoque
        $stmt_estoque = $conn->prepare("
            UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?
        ");
        $stmt_estoque->bind_param("ii", $quantidade, $id_produto);
        $stmt_estoque->execute();
    }

    // 🔹 3. Criar conta a receber vinculada à venda
    $data_vencimento = date('Y-m-d'); // Vencimento no mesmo dia da venda
    $numero_venda = "Venda #" . $id_venda;

    $stmt_receber = $conn->prepare("
        INSERT INTO contas_receber (usuario_id, id_pessoa_fornecedor, numero, valor, data_vencimento, status) 
        VALUES (?, ?, ?, ?, ?, 'pendente')
    ");
    $stmt_receber->bind_param("iisds", $id_usuario, $id_cliente, $numero_venda, $valor_total, $data_vencimento);
    $stmt_receber->execute();

    // 🔹 Finaliza transação
    $conn->commit();

    $_SESSION['success_message'] = "Venda registrada com sucesso!";
    header('Location: ../pages/recibo_venda.php?id=' . $id_venda);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Erro ao registrar a venda: " . $e->getMessage();
    header('Location: ../pages/vendas.php');
    exit;
}
