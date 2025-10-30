<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Garante que o usuário está logado e que um ID de venda foi fornecido
if (!isset($_SESSION['usuario_logado']) || !isset($_GET['id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acesso negado.</div>';
    exit;
}

// 2. Obtém a conexão correta com o banco de dados do tenant
$conn = getTenantConnection();
if ($conn === null) {
    http_response_code(500);
    echo '<div class="alert alert-danger">Falha ao conectar ao banco de dados.</div>';
    exit;
}

// 3. Valida os dados de entrada
$id_venda = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_usuario = $_SESSION['usuario_logado']['id'];

if (!$id_venda) {
    http_response_code(400);
    echo '<div class="alert alert-danger">ID de venda inválido.</div>';
    exit;
}

// 4. Busca os dados da venda e do cliente
$stmt_venda = $conn->prepare(
    "SELECT v.id, v.data_venda, v.valor_total, v.desconto, v.forma_pagamento, c.nome AS nome_cliente
     FROM vendas v
     JOIN pessoas_fornecedores c ON v.id_cliente = c.id
     WHERE v.id = ? AND v.id_usuario = ?"
);
$stmt_venda->bind_param("ii", $id_venda, $id_usuario);
$stmt_venda->execute();
$venda = $stmt_venda->get_result()->fetch_assoc();

if (!$venda) {
    echo "<div class='alert alert-warning'>Venda não encontrada ou não pertence a este usuário.</div>";
    exit;
}

// 5. Busca os itens da venda
$stmt_items = $conn->prepare(
    "SELECT vi.quantidade, vi.preco_unitario, vi.subtotal, p.nome AS nome_produto
     FROM venda_items vi
     JOIN produtos p ON vi.id_produto = p.id
     WHERE vi.id_venda = ?"
);
$stmt_items->bind_param("i", $id_venda);
$stmt_items->execute();
$items = $stmt_items->get_result();

// 6. Monta e exibe o HTML do romaneio
?>

<div class="romaneio-header" style="border-bottom: 1px solid #555; padding-bottom: 10px; margin-bottom: 15px;">
    <h5>Venda #<?= htmlspecialchars($venda['id']) ?></h5>
    <p class="mb-0"><strong>Cliente:</strong> <?= htmlspecialchars($venda['nome_cliente']) ?></p>
    <p class="mb-0"><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></p>
</div>

<table class="table table-sm table-borderless text-light">
    <thead>
        <tr>
            <th>Produto</th>
            <th class="text-center">Qtd.</th>
            <th class="text-right">Preço Unit.</th>
            <th class="text-right">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $subtotal_bruto = 0;
        while ($item = $items->fetch_assoc()):
            $subtotal_bruto += $item['subtotal'];
        ?>
        <tr>
            <td><?= htmlspecialchars($item['nome_produto']) ?></td>
            <td class="text-center"><?= $item['quantidade'] ?></td>
            <td class="text-right">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
            <td class="text-right">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="romaneio-footer" style="border-top: 1px solid #555; padding-top: 10px; margin-top: 15px; text-align: right;">
    <p class="mb-1"><strong>Subtotal:</strong> R$ <?= number_format($subtotal_bruto, 2, ',', '.') ?></p>
    <?php if ($venda['desconto'] > 0): ?>
        <p class="mb-1"><strong>Desconto:</strong> - R$ <?= number_format($venda['desconto'], 2, ',', '.') ?></p>
    <?php endif; ?>
    <h5 class="mb-1"><strong>Total: R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></strong></h5>
    <p class="mb-0"><strong>Pagamento:</strong> <?= ucfirst(str_replace('_', ' ', $venda['forma_pagamento'])) ?></p>
</div>

<div class="mt-4 text-center">
    <a href="recibo_venda.php?id=<?= $venda['id'] ?>" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-print"></i> Imprimir Recibo Completo
    </a>
    <button id="btn-cancelar-venda" data-id="<?= $venda['id'] ?>" class="btn btn-sm btn-danger">
        <i class="fas fa-times"></i> Cancelar Venda
    </button>
</div>