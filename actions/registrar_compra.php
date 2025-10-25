<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id'];
    $id_fornecedor = (int)$_POST['id_fornecedor'];
    $valor_total = (float)$_POST['valor_total'];
    $observacao = $_POST['observacao'];
    $produtos = $_POST['produtos'];

    if (empty($id_fornecedor) || empty($produtos) || $valor_total <= 0) {
        $_SESSION['error_message'] = "Fornecedor ou produtos inválidos.";
        header('Location: ../pages/compras.php');
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. Inserir a compra na tabela 'compras'
        $stmt_compra = $conn->prepare("INSERT INTO compras (id_usuario, id_fornecedor, valor_total, observacao) VALUES (?, ?, ?, ?)");
        $stmt_compra->bind_param("iids", $id_usuario, $id_fornecedor, $valor_total, $observacao);
        $stmt_compra->execute();
        $id_compra = $conn->insert_id;

        $descricao_conta = "Compra #" . $id_compra;
        
        // 2. Loop para processar cada produto
        foreach ($produtos as $produto) {
            $id_produto = (int)$produto['id'];
            $quantidade = (int)$produto['quantidade'];
            $preco = (float)$produto['preco'];

            // 2a. Inserir item na 'compra_items'
            $stmt_item = $conn->prepare("INSERT INTO compra_items (id_compra, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmt_item->bind_param("iiid", $id_compra, $id_produto, $quantidade, $preco);
            $stmt_item->execute();

            // 2b. CORREÇÃO: Atualizar a coluna correta 'quantidade_estoque'
            $stmt_update_prod = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id = ? AND id_usuario = ?");
            $stmt_update_prod->bind_param("iii", $quantidade, $id_produto, $id_usuario);
            $stmt_update_prod->execute();

            // 2c. Registrar movimento de estoque
            $stmt_mov = $conn->prepare("INSERT INTO movimento_estoque (id_produto, id_usuario, id_pessoa_fornecedor, tipo, quantidade, observacao) VALUES (?, ?, ?, 'entrada', ?, ?)");
            $obs_mov = "Compra #" . $id_compra;
            $stmt_mov->bind_param("iiiis", $id_produto, $id_usuario, $id_fornecedor, $quantidade, $obs_mov);
            $stmt_mov->execute();
        }

        // 3. Gerar a Conta a Pagar
        $data_vencimento = date('Y-m-d'); // Vencimento no mesmo dia, ajuste se necessário
        $stmt_pagar = $conn->prepare("INSERT INTO contas_pagar (usuario_id, id_pessoa_fornecedor, numero, valor, data_vencimento, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
        $stmt_pagar->bind_param("iisds", $id_usuario, $id_fornecedor, $descricao_conta, $valor_total, $data_vencimento);
        $stmt_pagar->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Compra #" . $id_compra . " registrada com sucesso!";
        header('Location: ../pages/compras.php');

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['error_message'] = "Erro ao registrar a compra: " . $exception->getMessage();
        header('Location: ../pages/compras.php');
    }

    exit;
}