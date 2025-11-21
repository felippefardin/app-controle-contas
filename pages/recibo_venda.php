<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit;
}

$conn = getTenantConnection();
if ($conn === null) die("Erro de conexão.");

// ✅ CORREÇÃO DO ERRO (Linha 20 original)
// Pega o ID direto da variável correta, não do array booleano
$id_usuario = $_SESSION['usuario_id']; 

$id_venda = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. BUSCA DADOS DA VENDA, CLIENTE E PROPRIETÁRIO
$sql_venda = "SELECT v.*, 
                     pf.nome AS nome_cliente, 
                     pf.cpf_cnpj AS doc_cliente, 
                     pf.endereco AS end_cliente,
                     u.nome AS nome_vendedor,
                     owner.nome AS nome_proprietario,
                     owner.documento AS doc_proprietario
              FROM vendas v
              LEFT JOIN pessoas_fornecedores pf ON v.id_cliente = pf.id
              LEFT JOIN usuarios u ON v.id_usuario = u.id
              LEFT JOIN usuarios owner ON owner.id = COALESCE(u.owner_id, u.id)
              WHERE v.id = ? AND v.id_usuario = ?";

$stmt = $conn->prepare($sql_venda);
$stmt->bind_param("ii", $id_venda, $id_usuario);
$stmt->execute();
$result_venda = $stmt->get_result();
$venda = $result_venda->fetch_assoc();

if (!$venda) {
    include('../includes/header.php');
    echo "<div class='container mt-5'><div class='alert alert-danger'>Venda não encontrada ou permissão negada.</div><a href='vendas.php' class='btn btn-secondary'>Voltar</a></div>";
    include('../includes/footer.php');
    exit;
}

// 3. BUSCA ITENS DA VENDA
$sql_itens = "SELECT vi.*, p.nome AS nome_produto 
              FROM venda_items vi
              LEFT JOIN produtos p ON vi.id_produto = p.id
              WHERE vi.id_venda = ?";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $id_venda);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Venda #<?= $id_venda ?></title>
    <style>
      body { 
    font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
    background: #f2f4f7; 
    padding: 20px;
    margin: 0;
}

.recibo-container {
    background: #ffffff;
    width: 100%;
    max-width: 420px; /* Largura estilo cupom */
    margin: 0 auto;
    padding: 25px 20px;
    border-radius: 10px;
    border: 1px solid #ddd;
    box-shadow: 0 3px 12px rgba(0,0,0,0.07);
}

.header { 
    text-align: center; 
    border-bottom: 1px dashed #000; 
    padding-bottom: 12px; 
    margin-bottom: 15px; 
}

.header h2 { 
    margin: 0; 
    font-size: 18px; 
    letter-spacing: 1px;
    font-weight: 700;
}

.info { 
    font-size: 13px; 
    margin-bottom: 12px; 
}

.info p { 
    margin: 3px 0; 
    color: #333;
}

table { 
    width: 100%; 
    font-size: 12px; 
    border-collapse: collapse; 
    margin-bottom: 10px; 
}

th { 
    text-align: left; 
    border-bottom: 1px solid #000; 
    padding-bottom: 4px;
    font-weight: 600;
}

td { 
    padding: 4px 0; 
}

.text-right { 
    text-align: right; 
}

.totais { 
    border-top: 1px dashed #000; 
    padding-top: 12px; 
    text-align: right; 
    font-size: 14px; 
}

.totais strong { 
    font-weight: 700;
}

.footer { 
    text-align: center; 
    margin-top: 20px; 
    font-size: 11px; 
    color: #444;
    border-top: 1px solid #eee; 
    padding-top: 10px; 
}

.btn-print { 
    display: block; 
    width: 100%; 
    /* padding: 11px;  */
    background: #007bff; 
    color: white; 
    font-size: 14px;
    font-weight: 600;
    text-align: center; 
    border-radius: 6px;
    border: none; 
    cursor: pointer; 
    margin-top: 18px; 
    text-decoration: none;
    transition: 0.2s;
}

.btn-print:hover { 
    opacity: 0.85; 
}

.btn-back { 
    background-color: #6c757d !important; 
}

@media print {
    body { 
        background: #fff; 
        padding: 0; 
        margin: 0;
    }
    .recibo-container { 
        box-shadow: none; 
        padding: 0; 
        width: 100%; 
        max-width: 100%; 
        border: none;
        border-radius: 0;
    }
    .btn-print, 
    .btn-back { 
        display: none !important; 
    }
}
    </style>
</head>
<body>

<div class="recibo-container">
    <div class="header">
        <h2>RECIBO DE VENDA</h2>
        <p>Venda #<?= str_pad($venda['id'], 6, '0', STR_PAD_LEFT) ?></p>
    </div>

    <div class="info">
        <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></p>
        <p><strong>Vendedor:</strong> <?= htmlspecialchars($venda['nome_vendedor']) ?></p>
        <hr style="border:0; border-top:1px dashed #ccc;">
        <p><strong>Cliente:</strong> <?= htmlspecialchars($venda['nome_cliente']) ?></p>
        <?php if(!empty($venda['doc_cliente'])): ?>
            <p><strong>CPF/CNPJ:</strong> <?= htmlspecialchars($venda['doc_cliente']) ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">R$ Unit</th>
                <th class="text-right">R$ Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $result_itens->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                <td class="text-right"><?= $item['quantidade'] ?></td>
                <td class="text-right"><?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($item['subtotal'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="totais">
        <p><strong>Subtotal:</strong> R$ <?= number_format($venda['valor_total'] + $venda['desconto'], 2, ',', '.') ?></p>
        <?php if($venda['desconto'] > 0): ?>
            <p><strong>Desconto:</strong> - R$ <?= number_format($venda['desconto'], 2, ',', '.') ?></p>
        <?php endif; ?>
        <p style="font-size: 16px;"><strong>TOTAL: R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></strong></p>
        <p style="font-size: 11px; margin-top:5px;">Forma Pagto: <?= ucfirst($venda['forma_pagamento']) ?></p>
    </div>

   <div class="footer">
        <p>Obrigado pela preferência!</p>
        <p>Emitido por: <?= htmlspecialchars($venda['nome_proprietario']) ?></p>
        <?php if(!empty($venda['doc_proprietario'])): ?>
            <p>Dados: <?= htmlspecialchars($venda['doc_proprietario']) ?></p>
        <?php endif; ?>
    </div>

    <button onclick="window.print()" class="btn-print">🖨️ Imprimir Recibo</button>
    <a href="vendas.php" class="btn-print" style="background-color: #6c757d; margin-top: 10px;">⬅️ Voltar</a>
</div>

</body>
</html>