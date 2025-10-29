<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Fornece getTenantConnection()

header('Content-Type: application/json');

// ✅ Verifica sessão correta
if (!isset($_SESSION['usuario_logado']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida. Faça login novamente.']);
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Falha ao obter a conexão com o banco de dados.']);
    exit;
}

$id_usuario = $_SESSION['usuario_logado']['id'];

// Coleta e validação dos dados
$cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
$forma_pagamento = $_POST['forma_pagamento'] ?? 'dinheiro';
$itens_venda_json = $_POST['itens'] ?? '[]';
$itens_venda = json_decode($itens_venda_json, true);
$desconto = !empty($_POST['desconto']) ? (float)str_replace(',', '.', $_POST['desconto']) : 0.00;
$tipo_finalizacao = $_POST['tipo_finalizacao'] ?? 'recibo';

// Validações iniciais
if (empty($itens_venda) || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Nenhum item válido foi enviado na venda.']);
    exit;
}
if (empty($cliente_id)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, selecione um cliente.']);
    exit;
}
if ($desconto < 0) {
    echo json_encode(['success' => false, 'message' => 'O desconto não pode ser negativo.']);
    exit;
}

$conn->begin_transaction();

try {
    // ✅ Verifica estoque antes de qualquer inserção
    foreach ($itens_venda as $item) {
        $id_produto = $item['id'];
        $quantidade_pedida = $item['quantidade'];

        $stmt_check = $conn->prepare("SELECT nome, quantidade_estoque FROM produtos WHERE id = ? AND id_usuario = ?");
        $stmt_check->bind_param("ii", $id_produto, $id_usuario);
        $stmt_check->execute();
        $produto = $stmt_check->get_result()->fetch_assoc();

        if (!$produto) throw new Exception("Produto com ID {$id_produto} não encontrado.");
        if ($produto['quantidade_estoque'] < $quantidade_pedida) {
            throw new Exception("Estoque insuficiente para '{$produto['nome']}'. Disponível: {$produto['quantidade_estoque']}.");
        }
    }

    // 1️⃣ Calcular totais
    $total_venda_bruto = 0;
    foreach ($itens_venda as $item) {
        if (!is_numeric($item['quantidade']) || $item['quantidade'] <= 0 || !is_numeric($item['preco']) || $item['preco'] < 0) {
            throw new Exception("Quantidade ou preço inválido para um dos itens.");
        }
        $total_venda_bruto += $item['quantidade'] * $item['preco'];
    }

    if ($desconto > $total_venda_bruto) throw new Exception("O desconto não pode ser maior que o valor total da venda.");
    $total_venda_liquido = $total_venda_bruto - $desconto;

    // 2️⃣ Inserir na tabela 'vendas'
    $stmt_venda = $conn->prepare(
        "INSERT INTO vendas (id_usuario, id_cliente, valor_total, desconto, forma_pagamento, data_venda) VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt_venda->bind_param("iidds", $id_usuario, $cliente_id, $total_venda_liquido, $desconto, $forma_pagamento);
    $stmt_venda->execute();
    $venda_id = $conn->insert_id;

    // 3️⃣ Inserir itens da venda e atualizar estoque
    foreach ($itens_venda as $item) {
        $subtotal_item = $item['quantidade'] * $item['preco'];

       $stmt_item = $conn->prepare(
    "INSERT INTO venda_items (id_venda, id_produto, quantidade, preco_unitario, subtotal, forma_pagamento) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt_item->bind_param("iiidds", $venda_id, $item['id'], $item['quantidade'], $item['preco'], $subtotal_item, $forma_pagamento);
        $stmt_item->execute();

        $stmt_estoque = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ? AND id_usuario = ?");
        $stmt_estoque->bind_param("iii", $item['quantidade'], $item['id'], $id_usuario);
        $stmt_estoque->execute();
    }

    // 4️⃣ Lançamento financeiro
    $descricao = "Referente à Venda #$venda_id";
if ($forma_pagamento === 'receber') {
    $stmt_fin = $conn->prepare(
        "INSERT INTO contas_receber (id_pessoa_fornecedor, descricao, valor, data_vencimento, id_venda) VALUES (?, ?, ?, ?, ?)"
    );
        $data_venc = date('Y-m-d', strtotime('+30 days'));
        $stmt_fin->bind_param("isdsi", $cliente_id, $descricao, $total_venda_liquido, $data_venc, $venda_id);
    } else {
    $stmt_fin = $conn->prepare(
        // Adicionamos a coluna usuario_id na inserção
        "INSERT INTO caixa_diario (usuario_id, data, valor, tipo, descricao) VALUES (?, ?, ?, 'entrada', ?)"
    );
    $hoje = date('Y-m-d');
    // Adicionamos o id_usuario ao bind_param e ajustamos os tipos para "isds"
    $stmt_fin->bind_param("isds", $id_usuario, $hoje, $total_venda_liquido, $descricao);
}
    $stmt_fin->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Venda registrada com sucesso!',
        'venda_id' => $venda_id,
        'tipo_finalizacao' => $tipo_finalizacao
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>