<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}


// ✅ CORREÇÃO: Define a variável $id_usuario vinda da sessão
$id_usuario = $_SESSION['usuario_id'];

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

    // 4️⃣ Lançamento financeiro UNIFICADO
    // Busca o ID da categoria "Venda de Caixa"
    $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE nome = 'Venda de Caixa' AND id_usuario = ? AND tipo = 'receita'");
    $stmt_cat->bind_param("i", $id_usuario);
    $stmt_cat->execute();
    $categoria_venda_id = $stmt_cat->get_result()->fetch_assoc()['id'] ?? null;

    $descricao = "Referente à Venda #$venda_id";
    $hoje = date('Y-m-d');

    // Se o pagamento NÃO for a prazo, a conta já nasce "baixada"
    if ($forma_pagamento !== 'receber') {
        $status = 'baixada';
        $data_baixa = $hoje;
        $baixado_por = $id_usuario;

        // --- INÍCIO DA ALTERAÇÃO (REQ 1) ---
        // Bloco de lançamento automático no caixa diário foi removido (comentado).
        
        /*
        // Também faz o lançamento no caixa diário para pagamentos à vista
        $stmt_check_caixa = $conn->prepare("SELECT id, valor, descricao FROM caixa_diario WHERE usuario_id = ? AND data = ?");
        $stmt_check_caixa->bind_param("is", $id_usuario, $hoje);
        $stmt_check_caixa->execute();
        $caixa_existente = $stmt_check_caixa->get_result()->fetch_assoc();

        if ($caixa_existente) {
            // Se já existe um registo para hoje, atualiza o valor
            $novo_valor = $caixa_existente['valor'] + $total_venda_liquido;
            $nova_descricao = $caixa_existente['descricao'] . " | " . $descricao;
            
            $stmt_caixa = $conn->prepare("UPDATE caixa_diario SET valor = ?, descricao = ? WHERE id = ?");
            $stmt_caixa->bind_param("dsi", $novo_valor, $nova_descricao, $caixa_existente['id']);
            $stmt_caixa->execute();
        } else {
            // Se não existir, insere um novo registo
            $stmt_caixa = $conn->prepare(
                "INSERT INTO caixa_diario (usuario_id, data, valor, tipo, descricao, id_venda) VALUES (?, ?, ?, 'entrada', ?, ?)"
            );
            $stmt_caixa->bind_param("isdsi", $id_usuario, $hoje, $total_venda_liquido, $descricao, $venda_id);
            $stmt_caixa->execute();
        }
        */
        // --- FIM DA ALTERAÇÃO (REQ 1) ---


    } else { // Se for a prazo, fica pendente
        $status = 'pendente';
        $data_baixa = null;
        $baixado_por = null;
    }

    // Insere em contas_receber para TODOS os tipos de pagamento
    $stmt_fin = $conn->prepare(
        "INSERT INTO contas_receber (usuario_id, id_pessoa_fornecedor, id_categoria, descricao, valor, data_vencimento, id_venda, status, data_baixa, forma_pagamento, baixado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // Para pagamentos à vista, data de vencimento é hoje. Para "a receber", pode ser no futuro.
    $data_venc = ($forma_pagamento !== 'receber') ? $hoje : date('Y-m-d', strtotime('+30 days'));

    $stmt_fin->bind_param(
        "iiisdsisssi",
        $id_usuario,
        $cliente_id,
        $categoria_venda_id,
        $descricao,
        $total_venda_liquido,
        $data_venc,
        $venda_id,
        $status,
        $data_baixa,
        $forma_pagamento,
        $baixado_por
    );
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