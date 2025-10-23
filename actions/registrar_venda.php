<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id'];
    $id_cliente = (int)$_POST['id_cliente'];
    $valor_total = (float)$_POST['valor_total'];
    $observacao = $_POST['observacao'];
    $produtos = $_POST['produtos'];

    if (empty($id_cliente) || empty($produtos) || $valor_total <= 0) {
        $_SESSION['error_message'] = "Cliente ou produtos inválidos.";
        header('Location: ../pages/vendas.php');
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Inserir a venda na tabela 'vendas'
        $stmt_venda = $conn->prepare("INSERT INTO vendas (id_usuario, id_cliente, valor_total, observacao) VALUES (?, ?, ?, ?)");
        $stmt_venda->bind_param("iids", $id_usuario, $id_cliente, $valor_total, $observacao);
        $stmt_venda->execute();
        $id_venda = $conn->insert_id;

        $descricao_conta = "Venda #" . $id_venda;

        // 2. Loop para processar cada produto
        foreach ($produtos as $produto) {
            $id_produto = (int)$produto['id'];
            $quantidade = (int)$produto['quantidade'];
            $preco = (float)$produto['preco'];

            // 2a. Inserir item na 'venda_items'
            $stmt_item = $conn->prepare("INSERT INTO venda_items (id_venda, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmt_item->bind_param("iiid", $id_venda, $id_produto, $quantidade, $preco);
            $stmt_item->execute();

            // 2b. Atualizar a quantidade no estoque
            $stmt_update_prod = $conn->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ? AND id_usuario = ?");
            $stmt_update_prod->bind_param("iii", $quantidade, $id_produto, $id_usuario);
            $stmt_update_prod->execute();

            // 2c. Registrar movimento de estoque
            $stmt_mov = $conn->prepare("INSERT INTO movimento_estoque (id_produto, id_usuario, id_pessoa_fornecedor, tipo, quantidade, observacao) VALUES (?, ?, ?, 'saida', ?, ?)");
            $obs_mov = "Venda #" . $id_venda;
            $stmt_mov->bind_param("iiiis", $id_produto, $id_usuario, $id_cliente, $quantidade, $obs_mov);
            $stmt_mov->execute();
        }

        // 3. Gerar a Conta a Receber
        $data_vencimento = date('Y-m-d'); 
        $stmt_receber = $conn->prepare("INSERT INTO contas_receber (usuario_id, id_pessoa_fornecedor, numero, valor, data_vencimento, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
        $stmt_receber->bind_param("iisds", $id_usuario, $id_cliente, $descricao_conta, $valor_total, $data_vencimento);
        $stmt_receber->execute();
        
        // Se tudo deu certo, confirma as alterações
        $conn->commit();
        
        // MUDANÇA PRINCIPAL: Redirecionar para o recibo
        header('Location: ../pages/recibo_venda.php?id_venda=' . $id_venda);

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Erro ao registrar a venda: " . $exception->getMessage();
        header('Location: ../pages/vendas.php');
    }

    exit;
}