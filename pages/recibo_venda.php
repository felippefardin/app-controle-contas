<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario']) || !isset($_GET['id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_venda = $_GET['id'];
$id_usuario = $_SESSION['usuario']['id'];

// Buscar dados da venda
$stmt_venda = $conn->prepare("SELECT v.id, v.data_venda, v.valor_total, v.forma_pagamento, c.nome AS nome_cliente FROM vendas v JOIN pessoas_fornecedores c ON v.id_cliente = c.id WHERE v.id = ? AND v.id_usuario = ?");
$stmt_venda->bind_param("ii", $id_venda, $id_usuario);
$stmt_venda->execute();
$venda = $stmt_venda->get_result()->fetch_assoc();

// Buscar itens da venda
$stmt_items = $conn->prepare("SELECT vi.*, p.nome AS nome_produto FROM venda_items vi JOIN produtos p ON vi.id_produto = p.id WHERE vi.id_venda = ?");
$stmt_items->bind_param("i", $id_venda);
$stmt_items->execute();
$items = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo da Venda</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .receipt-container {
            max-width: 400px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border: 1px dashed #ccc;
            font-family: 'Courier New', Courier, monospace;
            color: #000;
        }
        .receipt-header { text-align: center; margin-bottom: 20px; }
        .receipt-header h2 { margin: 0; font-size: 1.5rem; font-weight: bold; }
        .receipt-info p { margin-bottom: 2px; font-size: 0.9rem; }
        .receipt-body table { width: 100%; font-size: 0.9rem;}
        .receipt-body th, .receipt-body td { padding: 5px 0; }
        .receipt-total p { margin-bottom: 5px; font-size: 0.9rem; }
        .receipt-signature { margin-top: 50px; padding-top: 10px; text-align: center; font-size: 0.9rem; }
        .receipt-footer { text-align: center; margin-top: 20px; font-size: 0.9rem;}
        .no-print { margin: 20px auto; text-align: center; }
        @media print {
            body { background-color: #fff; }
            .no-print { display: none; }
            .receipt-container { margin: 0; border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h2>Comprovante de Venda</h2>
            <p><?= date('d/m/Y H:i:s', strtotime($venda['data_venda'])) ?></p>
        </div>
        <div class="receipt-info">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($venda['nome_cliente']) ?></p>
            <p><strong>Venda ID:</strong> <?= $venda['id'] ?></p>
        </div>
        <hr style="border-style: dashed;">
        <div class="receipt-body">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center">Qtd</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                        <td class="text-center"><?= $item['quantidade'] ?></td>
                        <td class="text-right">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <hr style="border-style: dashed;">
        <div class="receipt-total">
            <p><strong>Total:</strong> <span class="float-right font-weight-bold">R$ <?= number_format($venda['valor_total'] ?? 0, 2, ',', '.') ?></span></p>
            <p>
                <p>
    <strong>Forma de Pagamento:</strong>
    <span class="float-right">
        <?php
            if (!empty($venda['forma_pagamento'])) {
                echo ucfirst(str_replace('_', ' ', $venda['forma_pagamento']));
            } else {
                echo 'Não especificada';
            }
        ?>
    </span>
</p>
        </div>

        <div class="receipt-signature">
            <p>___________________________________</p>
            <p>Assinatura do Cliente</p>
        </div>

        <div class="receipt-footer">
            <p>Obrigado pela sua compra!</p>
        </div>
    </div>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimir Recibo</button>
        <a href="vendas.php" class="btn btn-secondary">Nova Venda</a>
    </div>
</body>
</html>