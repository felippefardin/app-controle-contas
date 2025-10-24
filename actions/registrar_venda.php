<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Fornece a variável $conn

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

// Coleta dos dados do formulário
$cliente_id = $_POST['cliente_id'] ?? null;
$forma_pagamento = $_POST['forma_pagamento'] ?? 'dinheiro';
$itens_venda_json = $_POST['itens'] ?? '[]';
$itens_venda = json_decode($itens_venda_json, true);
$desconto = !empty($_POST['desconto']) ? (float)str_replace(',', '.', $_POST['desconto']) : 0.00;
$id_usuario = $_SESSION['usuario']['id'];
$tipo_finalizacao = $_POST['tipo_finalizacao'] ?? 'recibo';

// Validações
if (empty($itens_venda) || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Nenhum item válido foi enviado na venda.']);
    exit;
}
if (empty($cliente_id)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, selecione um cliente.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Calcular totais
    $total_venda_bruto = 0;
    foreach ($itens_venda as $item) {
        $total_venda_bruto += $item['quantidade'] * $item['preco'];
    }
    $total_venda_liquido = $total_venda_bruto - $desconto;
    if ($total_venda_liquido < 0) $total_venda_liquido = 0;

    // 2. Inserir na tabela 'vendas' (compatível com sua estrutura)
    $stmt_venda = $conn->prepare(
        "INSERT INTO vendas (id_usuario, id_cliente, valor_total, desconto, forma_pagamento, data_venda) VALUES (?, ?, ?, ?, ?, NOW())"
    );
    // Ajuste no bind_param para corresponder à ordem da sua tabela
    $stmt_venda->bind_param("iidds", $id_usuario, $cliente_id, $total_venda_liquido, $desconto, $forma_pagamento);
    $stmt_venda->execute();
    $venda_id = $conn->insert_id;

    // 3. Inserir cada item na sua tabela 'venda_items'
    foreach ($itens_venda as $item) {
        $subtotal_item = $item['quantidade'] * $item['preco'];
        $stmt_item = $conn->prepare(
            "INSERT INTO venda_items (id_venda, id_produto, quantidade, preco_unitario, subtotal, forma_pagamento) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt_item->bind_param("iiidds", $venda_id, $item['id'], $item['quantidade'], $item['preco'], $subtotal_item, $forma_pagamento);
        $stmt_item->execute();

        // Atualiza o estoque
        $stmt_estoque = $conn->prepare(
            "UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?"
        );
        $stmt_estoque->bind_param("ii", $item['quantidade'], $item['id']);
        $stmt_estoque->execute();
    }

    // 4. Lançamento Financeiro (CORRIGIDO SEM id_usuario)
    $descricao_lancamento = "Referente à Venda #" . $venda_id;
    if ($forma_pagamento == 'fiado') {
        $stmt_financeiro = $conn->prepare(
            "INSERT INTO contas_receber (id_pessoa_fornecedor, descricao, valor, data_vencimento, id_venda) VALUES (?, ?, ?, ?, ?)"
        );
        $data_vencimento = date('Y-m-d', strtotime('+30 days'));
        $stmt_financeiro->bind_param("isdsi", $cliente_id, $descricao_lancamento, $total_venda_liquido, $data_vencimento, $venda_id);
    } else {
        $data_hoje = date('Y-m-d');
        $tipo_lancamento = 'entrada';
        $stmt_financeiro = $conn->prepare(
            "INSERT INTO caixa_diario (data, valor, tipo, descricao) VALUES (?, ?, ?, ?)"
        );
        $stmt_financeiro->bind_param("sdss", $data_hoje, $total_venda_liquido, $tipo_lancamento, $descricao_lancamento);
    }
    $stmt_financeiro->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Venda registrada com sucesso!',
        'venda_id' => $venda_id,
        'tipo_finalizacao' => $tipo_finalizacao
    ]);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Erro fatal no servidor: ' . $e->getMessage()
    ]);
}
?>