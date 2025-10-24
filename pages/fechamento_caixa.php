<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$data_hoje = date('Y-m-d');
$data_selecionada = $_GET['data'] ?? $data_hoje;

// Totais por forma de pagamento
$stmt = $conn->prepare("SELECT forma_pagamento, SUM(valor_total) as total FROM vendas WHERE id_usuario = ? AND DATE(data_venda) = ? GROUP BY forma_pagamento");
$stmt->bind_param("is", $id_usuario, $data_selecionada);
$stmt->execute();
$result = $stmt->get_result();

$totais = [];
$total_geral = 0;
while($row = $result->fetch_assoc()) {
    $totais[$row['forma_pagamento']] = $row['total'];
    $total_geral += $row['total'];
}

// Listagem de vendas
$stmt_vendas = $conn->prepare("SELECT id, data_venda, valor_total, forma_pagamento FROM vendas WHERE id_usuario = ? AND DATE(data_venda) = ? ORDER BY id DESC");
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
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1 { color: #0af; }
        tr.venda:hover { background-color: #333; cursor: pointer; }
        @media print {
            body { background-color: #fff; color: #000; }
            .container { background-color: #fff; box-shadow: none; margin-top: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center no-print">
        <h1><i class="fas fa-cash-register"></i> Fechamento de Caixa</h1>
        <a href="vendas.php" class="btn btn-secondary">Voltar</a>
    </div>

    <form method="GET" class="form-inline mb-4 no-print">
        <label for="data" class="mr-2">Selecione a Data:</label>
        <input type="date" name="data" id="data" class="form-control mr-2" value="<?= $data_selecionada ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <h4>Resumo por Forma de Pagamento</h4>
    <table class="table table-dark table-striped">
        <thead><tr><th>Forma de Pagamento</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($totais as $forma => $total): ?>
            <tr>
                <td><?= ucfirst(str_replace('_', ' ', $forma)) ?></td>
                <td>R$ <?= number_format($total, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="font-weight-bold">
                <td>Total Geral</td>
                <td>R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <h4 class="mt-5">Vendas do Dia</h4>
    <table class="table table-dark table-hover">
        <thead><tr><th>ID</th><th>Data</th><th>Forma de Pagamento</th><th>Valor Total</th></tr></thead>
        <tbody>
            <?php while($venda = $vendas->fetch_assoc()): ?>
            <tr class="venda" data-id="<?= $venda['id'] ?>">
                <td><?= $venda['id'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                <td><?= ucfirst($venda['forma_pagamento']) ?></td>
                <td>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <button onclick="window.print()" class="btn btn-success no-print mt-3"><i class="fas fa-print"></i> Imprimir</button>
</div>


<!-- Modal para exibir o romaneio -->
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
    let vendaId = null;

    $(".venda").click(function() {
        vendaId = $(this).data("id");
        $("#conteudoRomaneio").html("Carregando...");
        $("#modalRomaneio").modal("show");

        $.get("buscar_venda.php", { id: vendaId }, function(data) {
            $("#conteudoRomaneio").html(data);
        });
    });

    $("#btnCancelarVenda").click(function() {
        if (confirm("Tem certeza que deseja cancelar esta venda?")) {
            $.post("cancelar_venda.php", { id: vendaId }, function(res) {
                alert(res.message);
                if (res.success) location.reload();
            }, "json");
        }
    });
});

</script>
</body>
</html>
