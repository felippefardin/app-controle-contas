<?php
require_once '../includes/session_init.php';
require_once '../database.php';
include('../includes/header.php');

if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

$conn = getTenantConnection();
$id_usuario = $_SESSION['usuario_id'];
$data_selecionada = $_GET['data'] ?? date('Y-m-d');

$sql = "
    SELECT forma_pagamento, SUM(valor_total) AS total 
    FROM vendas 
    WHERE id_usuario = ? AND DATE(data_venda) = ?
    GROUP BY forma_pagamento
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_usuario, $data_selecionada);
$stmt->execute();
$lancamentos = $stmt->get_result();

$totais = [];
$total_geral = 0;
while ($row = $lancamentos->fetch_assoc()) {
    $forma = $row['forma_pagamento'] ?: 'outros';
    $totais[$forma] = $row['total'];
    $total_geral += $row['total'];
}

$clientes_novos = 0;
$clientes_retorno = 0;
$clientes_dia_ids = [];

$stmt_clientes_dia = $conn->prepare("
    SELECT DISTINCT id_cliente 
    FROM vendas 
    WHERE id_usuario = ? AND DATE(data_venda) = ?
");
$stmt_clientes_dia->bind_param("is", $id_usuario, $data_selecionada);
$stmt_clientes_dia->execute();
$result_clientes_dia = $stmt_clientes_dia->get_result();
while ($row = $result_clientes_dia->fetch_assoc()) {
    $clientes_dia_ids[] = $row['id_cliente'];
}
$stmt_clientes_dia->close();

if (!empty($clientes_dia_ids)) {
    $placeholders = implode(',', array_fill(0, count($clientes_dia_ids), '?'));
    $types = 'i' . str_repeat('i', count($clientes_dia_ids));
    $params = [$id_usuario, ...$clientes_dia_ids];

    $sql_primeira_venda = "
        SELECT id_cliente, MIN(DATE(data_venda)) AS data_primeira_venda
        FROM vendas
        WHERE id_usuario = ? AND id_cliente IN ($placeholders)
        GROUP BY id_cliente
    ";

    $stmt_primeira_venda = $conn->prepare($sql_primeira_venda);
    $stmt_primeira_venda->bind_param($types, ...$params);
    $stmt_primeira_venda->execute();
    $result_primeira_venda = $stmt_primeira_venda->get_result();

    while ($row_primeira = $result_primeira_venda->fetch_assoc()) {
        if ($row_primeira['data_primeira_venda'] == $data_selecionada) {
            $clientes_novos++;
        } else {
            $clientes_retorno++;
        }
    }
    $stmt_primeira_venda->close();
}

$stmt_vendas = $conn->prepare("
    SELECT id, data_venda, valor_total, forma_pagamento 
    FROM vendas 
    WHERE id_usuario = ? AND DATE(data_venda) = ? 
    ORDER BY id DESC
");
$stmt_vendas->bind_param("is", $id_usuario, $data_selecionada);
$stmt_vendas->execute();
$vendas = $stmt_vendas->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #121212; color: #eee; font-family: 'Segoe UI', sans-serif; }
        .container { background-color: #1f1f1f; padding: 25px; border-radius: 10px; margin-top: 30px; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
        h1 { color: #0af; }
        .card-resumo { border-left: 5px solid; padding: 10px 15px; border-radius: 6px; margin-bottom: 10px; font-size: 0.95rem; }
        .card-resumo strong { font-size: 1rem; }
        .forma-dinheiro { border-color: #28a745; background-color: rgba(40,167,69,0.1); }
        .forma-pix { border-color: #20c997; background-color: rgba(32,201,151,0.1); }
        .forma-debito { border-color: #17a2b8; background-color: rgba(23,162,184,0.1); }
        .forma-credito { border-color: #ffc107; background-color: rgba(255,193,7,0.1); }
        .forma-outros { border-color: #6c757d; background-color: rgba(108,117,125,0.1); }
        tr.venda:hover { background-color: #333; cursor: pointer; }
        .total-geral { background-color: #0d6efd; color: #fff; font-weight: bold; text-align: center; border-radius: 6px; padding: 10px; }
        @media print {
            body { background-color: #fff; color: #000; }
            .container { background-color: #fff; box-shadow: none; }
            .no-print { display: none; }
            .total-geral { background-color: #000; color: #fff; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center no-print mb-3">
        <h1><i class="fas fa-cash-register"></i> Fechamento de Caixa</h1>
        <a href="vendas.php" class="btn btn-secondary">Voltar</a>
    </div>

    <div id="alert-container-fechamento"></div>

    <form method="GET" class="form-inline mb-4 no-print">
        <label for="data" class="mr-2">Selecione a Data:</label>
        <input type="date" name="data" id="data" class="form-control mr-2" value="<?= htmlspecialchars($data_selecionada) ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <h4 class="mb-3">Resumo por Forma de Pagamento</h4>
    <?php if (count($totais) > 0): ?>
        <?php foreach ($totais as $forma => $total): ?>
            <?php
                $classe = 'forma-outros';
                $nome = ucfirst(str_replace('_', ' ', $forma));
                if (stripos($forma, 'dinheiro') !== false) $classe = 'forma-dinheiro';
                elseif (stripos($forma, 'pix') !== false) $classe = 'forma-pix';
                elseif (stripos($forma, 'débito') !== false || stripos($forma, 'debito') !== false) $classe = 'forma-debito';
                elseif (stripos($forma, 'crédito') !== false || stripos($forma, 'credito') !== false) $classe = 'forma-credito';
            ?>
            <div class="card-resumo <?= $classe ?>">
                <strong><?= $nome ?>:</strong>
                <span class="float-right">R$ <?= number_format($total, 2, ',', '.') ?></span>
            </div>
        <?php endforeach; ?>
        <div class="total-geral mt-3">Total Geral: R$ <?= number_format($total_geral, 2, ',', '.') ?></div>
    <?php else: ?>
        <p>Nenhuma venda encontrada nesta data.</p>
    <?php endif; ?>

    <h4 class="mt-5 mb-3">Resumo de Clientes</h4>
    <div class="row">
        <div class="col-md-6">
            <div class="card-resumo forma-dinheiro">
                <strong>Clientes Novos:</strong>
                <span class="float-right" style="font-size: 1.2rem;"><?= $clientes_novos ?></span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-resumo forma-debito">
                <strong>Clientes de Retorno:</strong>
                <span class="float-right" style="font-size: 1.2rem;"><?= $clientes_retorno ?></span>
            </div>
        </div>
    </div>

    <h4 class="mt-5 mb-3">Vendas do Dia</h4>
    <table class="table table-dark table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Forma de Pagamento</th>
                <th>Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($venda = $vendas->fetch_assoc()): ?>
            <tr class="venda" data-id="<?= $venda['id'] ?>">
                <td><?= $venda['id'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                <td><?= ucfirst(str_replace('_', ' ', $venda['forma_pagamento'])) ?></td>
                <td>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <button onclick="window.print()" class="btn btn-success no-print mt-3">
        <i class="fas fa-print"></i> Imprimir
    </button>
</div>

<div class="modal fade" id="modalRomaneio" tabindex="-1" role="dialog" aria-labelledby="romaneioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title" id="romaneioLabel">Romaneio da Venda</h5>
                <button type="button" class="close text-light" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="conteudoRomaneio">
                <p>Carregando...</p>
            </div>
        </div>
    </div>
</div>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
    function showAlert(message, type) {
        $('#alert-container-fechamento').html(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        `);
    }

    $(".venda").click(function() {
        const vendaId = $(this).data("id");
        $("#conteudoRomaneio").html("Carregando...");
        $("#modalRomaneio").modal("show");
        $.get("buscar_venda.php", { id: vendaId }, function(data) {
            $("#conteudoRomaneio").html(data);
        });
    });

    $('#modalRomaneio').on('click', '#btn-cancelar-venda', function() {
        const vendaId = $(this).data('id');
        if (!confirm('Tem certeza que deseja cancelar esta venda? Esta ação não pode ser desfeita.')) return;
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cancelando...');

        fetch('cancelar_venda.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ 'venda_id': vendaId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#modalRomaneio').modal('hide');
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('Erro: ' + data.message);
                $(this).prop('disabled', false).html('<i class="fas fa-times"></i> Cancelar Venda');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            alert('Ocorreu um erro de comunicação ao tentar cancelar a venda.');
            $(this).prop('disabled', false).html('<i class="fas fa-times"></i> Cancelar Venda');
        });
    });
});
</script>
</body>
</html>
