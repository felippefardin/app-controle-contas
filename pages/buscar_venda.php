<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

$id_venda = $_GET['id'] ?? null;

if (!$id_venda) {
    echo json_encode(['erro' => 'ID da venda não informado']);
    exit;
}

try {
    // 🔹 Buscar dados da venda + cliente
    $stmt = $conn->prepare("
        SELECT v.*, pf.nome AS cliente_nome 
        FROM vendas v
        LEFT JOIN pessoas_fornecedores pf ON v.id_cliente = pf.id
        WHERE v.id = ?
    ");
    $stmt->bind_param("i", $id_venda);
    $stmt->execute();
    $venda = $stmt->get_result()->fetch_assoc();

    if (!$venda) {
        echo json_encode(['erro' => 'Venda não encontrada']);
        exit;
    }

    // 🔹 Buscar os itens da venda
    $stmtItens = $conn->prepare("
        SELECT vi.*, p.nome AS produto_nome
        FROM venda_items vi
        LEFT JOIN produtos p ON vi.id_produto = p.id
        WHERE vi.id_venda = ?
    ");
    $stmtItens->bind_param("i", $id_venda);
    $stmtItens->execute();
    $itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);

    // 🔹 Montar HTML do romaneio
    ob_start();
?>
    <div class="p-3">
        <h4>Romaneio da Venda #<?= htmlspecialchars($venda['id']) ?></h4>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></p>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($venda['cliente_nome'] ?? 'Não informado') ?></p>
        <p><strong>Forma de Pagamento:</strong> <?= ucfirst(str_replace('_', ' ', $venda['forma_pagamento'])) ?></p>
        <p><strong>Valor Total:</strong> R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></p>

        <h5 class="mt-3">Itens da Venda</h5>
        <table class="table table-sm table-bordered table-dark">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Preço Unitário</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['produto_nome'] ?? 'Produto removido') ?></td>
                    <td><?= htmlspecialchars($item['quantidade']) ?></td>
                    <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($venda['observacao'])): ?>
            <p><strong>Observações:</strong> <?= nl2br(htmlspecialchars($venda['observacao'])) ?></p>
        <?php endif; ?>

        <div class="text-right mt-4">
            <button class="btn btn-danger" onclick="cancelarVenda(<?= $venda['id'] ?>)">
                <i class="fas fa-times"></i> Cancelar Venda
            </button>
        </div>
    </div>

<?php
    echo ob_get_clean();

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao buscar venda: ' . $e->getMessage()]);
}
?>
