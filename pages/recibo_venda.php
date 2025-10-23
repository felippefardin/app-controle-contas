<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if (!isset($_GET['id_venda'])) {
    die("Venda não encontrada.");
}

$id_venda = (int)$_GET['id_venda'];
$id_usuario = $_SESSION['usuario']['id'];

// Buscar dados da venda, do cliente e do usuário (vendedor)
$stmt_venda = $conn->prepare("
    SELECT v.*, c.nome AS cliente_nome, c.cpf_cnpj, c.endereco, u.nome AS vendedor_nome
    FROM vendas v
    JOIN pessoas_fornecedores c ON v.id_cliente = c.id
    JOIN usuarios u ON v.id_usuario = u.id
    WHERE v.id = ? AND v.id_usuario = ?
");
$stmt_venda->bind_param("ii", $id_venda, $id_usuario);
$stmt_venda->execute();
$venda = $stmt_venda->get_result()->fetch_assoc();

if (!$venda) {
    die("Venda não encontrada ou não pertence a este usuário.");
}

// Buscar itens da venda
$stmt_items = $conn->prepare("
    SELECT vi.*, p.nome AS produto_nome
    FROM venda_items vi
    JOIN produtos p ON vi.id_produto = p.id
    WHERE vi.id_venda = ?
");
$stmt_items->bind_param("i", $id_venda);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Venda #<?= $id_venda ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
        }
        .container {
            max-width: 800px;
            margin-top: 20px;
        }
        .recibo {
            background-color: #fff;
            padding: 30px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .recibo-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .recibo-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .recibo-info {
            margin-bottom: 20px;
        }
        .table {
            color: #212529;
        }
        .total {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .botoes-acao {
            text-align: center;
            margin-top: 30px;
        }

        /* Estilos para impressão */
        @media print {
            body {
                background-color: #fff;
            }
            .botoes-acao, .navbar {
                display: none !important;
            }
            .container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: none;
            }
            .recibo {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="recibo">
        <div class="recibo-header">
            <h1>RECIBO DE VENDA</h1>
            <p class="lead">Venda #<?= htmlspecialchars($venda['id']) ?></p>
        </div>

        <div class="recibo-info">
            <div class="row">
                <div class="col-md-6">
                    <h5>Dados do Cliente</h5>
                    <p>
                        <strong>Nome:</strong> <?= htmlspecialchars($venda['cliente_nome']) ?><br>
                        <strong>CPF/CNPJ:</strong> <?= htmlspecialchars($venda['cpf_cnpj']) ?><br>
                        <strong>Endereço:</strong> <?= htmlspecialchars($venda['endereco']) ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-right">
                    <h5>Dados da Venda</h5>
                    <p>
                        <strong>Data:</strong> <?= date('d/m/Y H:i:s', strtotime($venda['data_venda'])) ?><br>
                        <strong>Vendedor:</strong> <?= htmlspecialchars($venda['vendedor_nome']) ?>
                    </p>
                </div>
            </div>
        </div>

        <h5>Itens da Venda</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Produto</th>
                        <th class="text-center">Qtd.</th>
                        <th class="text-right">Preço Unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['produto_nome']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($item['quantidade']) ?></td>
                        <td class="text-right">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right total">TOTAL</td>
                        <td class="text-right total">R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($venda['observacao'])): ?>
        <div class="mt-4">
            <h5>Observações</h5>
            <p><?= nl2br(htmlspecialchars($venda['observacao'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="assinatura mt-5 text-center">
            <p>_________________________________________</p>
            <p>Assinatura do Cliente</p>
        </div>

    </div>

    <div class="botoes-acao">
        <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Imprimir Recibo</button>
        <a href="vendas.php" class="btn btn-secondary"><i class="fas fa-plus"></i> Nova Venda</a>
    </div>

</div>
</body>
</html>