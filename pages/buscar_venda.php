<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica se o ID da venda foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<p>Venda não encontrada.</p>";
    exit;
}

$id_venda = intval($_GET['id']);

// Busca os dados da venda e do cliente (ligando pessoas_fornecedores)
$query = "
    SELECT v.*, p.nome AS cliente_nome, p.cpf_cnpj, p.email, p.contato
    FROM vendas v
    LEFT JOIN pessoas_fornecedores p ON v.id_cliente = p.id
    WHERE v.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_venda);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Venda não encontrada.</p>";
    exit;
}

$venda = $result->fetch_assoc();

// Busca os itens da venda
$queryItens = "
    SELECT vi.*, pr.nome AS produto_nome 
    FROM venda_items vi
    LEFT JOIN produtos pr ON vi.id_produto = pr.id
    WHERE vi.id_venda = ?
";
$stmtItens = $conn->prepare($queryItens);
$stmtItens->bind_param("i", $id_venda);
$stmtItens->execute();
$itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="romaneio-container">
    <div class="romaneio-header">
        <h2>🧾 Romaneio da Venda</h2>
    </div>

    <div class="romaneio-info">
        <p><strong>ID da Venda:</strong> <?= htmlspecialchars($venda['id']) ?></p>
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></p>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($venda['cliente_nome'] ?? 'Não informado') ?></p>
        <p><strong>Forma de Pagamento:</strong> <?= htmlspecialchars($venda['forma_pagamento']) ?></p>
        <p><strong>Total:</strong> <span class="valor">R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></span></p>

        <?php if (!empty($venda['observacao'])): ?>
            <div class="observacao">
                <strong>Observação:</strong> <?= nl2br(htmlspecialchars($venda['observacao'])) ?>
            </div>
        <?php endif; ?>
    </div>

    <h3 class="titulo-itens">Itens da Venda</h3>
    <table class="tabela-itens">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unitário</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['produto_nome'] ?? 'Produto não encontrado') ?></td>
                    <td><?= htmlspecialchars($item['quantidade']) ?></td>
                    <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="acoes">
        <button class="btn-cancelar" onclick="cancelarVenda(<?= $venda['id'] ?>)">❌ Cancelar Venda</button>
    </div>
</div>

<script>
function cancelarVenda(idVenda) {
    if (confirm('Tem certeza que deseja cancelar esta venda?')) {
        fetch('cancelar_venda.php?id=' + idVenda)
            .then(response => response.text())
            .then(data => {
                alert(data);
                location.reload();
            })
            .catch(error => console.error('Erro ao cancelar venda:', error));
    }
}
</script>

<style>
/* ======== Estilo Padrão do Projeto ======== */
.romaneio-container {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    font-family: "Segoe UI", Arial, sans-serif;
    color: #333;
    max-width: 800px;
    margin: 30px auto;
    border: 1px solid #e0e0e0;
}

.romaneio-header {
    text-align: center;
    margin-bottom: 20px;
}

.romaneio-header h2 {
    font-size: 1.5rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    display: inline-block;
    padding-bottom: 5px;
}

.romaneio-info {
    line-height: 1.7;
    margin-bottom: 20px;
}

.romaneio-info .valor {
    font-weight: bold;
    color: #007bff;
}

.observacao {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
    border-left: 4px solid #007bff;
}

.titulo-itens {
    margin-top: 20px;
    color: #444;
    font-size: 1.1rem;
    border-bottom: 1px solid #ccc;
    padding-bottom: 5px;
}

.tabela-itens {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.tabela-itens th {
    background-color: #007bff;
    color: #fff;
    padding: 10px;
    text-align: left;
}

.tabela-itens td {
    padding: 10px;
    border-bottom: 1px solid #e9ecef;
}

.tabela-itens tr:nth-child(even) {
    background-color: #f8f9fa;
}

.acoes {
    text-align: right;
    margin-top: 20px;
}

.btn-cancelar {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-cancelar:hover {
    background: #c82333;
}

/* Responsividade */
@media (max-width: 600px) {
    .romaneio-container {
        padding: 15px;
    }
    .tabela-itens th, .tabela-itens td {
        font-size: 0.9rem;
        padding: 8px;
    }
    .btn-cancelar {
        width: 100%;
    }
}
</style>
