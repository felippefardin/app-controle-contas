<?php
require_once '../includes/session_init.php';
// AJUSTE: O header normalmente já inclui o database.php, se não, mantenha os dois.
include('../includes/header.php'); 
include('../database.php');

if (!isset($_SESSION['usuario_logado']) || !isset($_GET['id'])) {
    header('Location: ../pages/login.php');
    exit;
}

// AJUSTE: Validar que o ID é um número inteiro
$id_venda = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_usuario = $_SESSION['usuario_logado']['id'];

if (!$id_venda) {
    // Redireciona ou mostra erro se o ID não for um número válido
    header('Location: vendas.php');
    exit;
}


// Buscar dados da venda
$stmt_venda = $conn->prepare("SELECT v.id, v.data_venda, v.valor_total, v.desconto, v.forma_pagamento, c.nome AS nome_cliente FROM vendas v JOIN pessoas_fornecedores c ON v.id_cliente = c.id WHERE v.id = ? AND v.id_usuario = ?");
$stmt_venda->bind_param("ii", $id_venda, $id_usuario);
$stmt_venda->execute();
$venda = $stmt_venda->get_result()->fetch_assoc();

// AJUSTE: Se a venda não for encontrada (ID inválido ou pertence a outro usuário), não prosseguir
if (!$venda) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Venda não encontrada ou você não tem permissão para visualizá-la.</div> <a href='vendas.php' class='btn btn-secondary'>Voltar</a></div>";
    // Pode incluir o footer aqui se desejar
    exit; // Impede a execução do resto da página
}

// Buscar itens da venda
$stmt_items = $conn->prepare("SELECT vi.*, p.nome AS nome_produto FROM venda_items vi JOIN produtos p ON vi.id_produto = p.id WHERE vi.id_venda = ?");
$stmt_items->bind_param("i", $id_venda);
$stmt_items->execute();
$items = $stmt_items->get_result();

// AJUSTE: Calcular o subtotal (soma dos itens antes do desconto)
$subtotal_bruto = 0;
// É preciso clonar o resultado para percorrê-lo duas vezes
$items_clone = [];
while($item = $items->fetch_assoc()) {
    $subtotal_bruto += $item['subtotal'];
    $items_clone[] = $item;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo da Venda #<?= $venda['id'] ?></title>
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
            .receipt-container { margin: 0; border: none; box-shadow: none; width: 100%; max-width: 100%;}
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
                    <?php foreach($items_clone as $item): // AJUSTE: Usando o array clonado ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                        <td class="text-center"><?= $item['quantidade'] ?></td>
                        <td class="text-right">R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <hr style="border-style: dashed;">
        <div class="receipt-total">
            <p>Subtotal: <span class="float-right">R$ <?= number_format($subtotal_bruto, 2, ',', '.') ?></span></p>
            <?php if ($venda['desconto'] > 0): ?>
                <p>Desconto: <span class="float-right">- R$ <?= number_format($venda['desconto'], 2, ',', '.') ?></span></p>
            <?php endif; ?>
            <p><strong>Total:</strong> <span class="float-right font-weight-bold">R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></span></p>
            <hr style="border-style: dashed;">
            <p>
                <strong>Forma de Pagamento:</strong>
                <span class="float-right">
                    <?= !empty($venda['forma_pagamento']) ? ucfirst(str_replace('_', ' ', $venda['forma_pagamento'])) : 'Não especificada' ?>
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