<?php
require_once '../includes/session_init.php';

// --- BLOCO DE PROCESSAMENTO AJAX ---
// Este bloco é executado PRIMEIRO se for uma requisição de busca
if (isset($_GET['action'])) {
    include('../database.php'); // Conecta ao banco SÓ para a busca
    
    if (!isset($_SESSION['usuario']['id'])) {
        header('Content-Type: application/json');
        echo json_encode(['results' => []]);
        exit;
    }

    $id_usuario = $_SESSION['usuario']['id'];
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $response = [];

    // Busca de Clientes
    if ($_GET['action'] === 'search_clientes') {
        $stmt = $conn->prepare("SELECT id, nome FROM pessoas_fornecedores WHERE id_usuario = ? AND tipo = 'pessoa' AND nome LIKE ? ORDER BY nome ASC LIMIT 10");
        $stmt->bind_param("is", $id_usuario, $term);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = ['id' => $row['id'], 'text' => $row['nome']];
        }
    }
    
    // Busca de Produtos
    if ($_GET['action'] === 'search_produtos') {
        $stmt = $conn->prepare("SELECT id, nome, preco_venda, quantidade FROM produtos WHERE id_usuario = ? AND nome LIKE ? ORDER BY nome ASC LIMIT 10");
        $stmt->bind_param("is", $id_usuario, $term);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                'id' => $row['id'],
                'text' => $row['nome'] . " (Estoque: " . $row['quantidade'] . ")",
                'preco_venda' => $row['preco_venda'],
                'estoque' => $row['quantidade']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['results' => $response]);
    exit; // Finaliza o script para não carregar o HTML abaixo
}

// --- CARREGAMENTO NORMAL DA PÁGINA ---
// O código abaixo só é executado se NÃO for uma busca AJAX
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registro de Venda</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1, h2 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; }
        .form-control, .select2-container .select2-selection--single { background-color: #333; color: #eee; border-color: #444; height: calc(1.5em + .75rem + 2px); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: #eee; line-height: 38px; }
        .select2-dropdown { background-color: #333; border-color: #444; }
        .select2-results__option { color: #eee; }
        .select2-results__option--highlighted { background-color: #0af !important; }
        .table { color: #eee; }
        .total-venda { font-size: 1.5rem; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-cash-register"></i> Registrar Venda</h1>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form action="../actions/registrar_venda.php" method="POST" id="form-venda">
        <div class="form-group">
            <label for="cliente_id">Cliente</label>
            <select id="cliente_id" name="id_cliente" class="form-control" required></select>
        </div>
        
        <div class="card bg-dark text-white mb-4">
            <div class="card-header">
                <h2>Adicionar Produtos</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Produto</label>
                        <select id="produto_select" class="form-control"></select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>&nbsp;</label>
                        <button type="button" id="add-produto" class="btn btn-success btn-block">Adicionar à Venda</button>
                    </div>
                </div>
            </div>
        </div>

        <h2><i class="fas fa-shopping-cart"></i> Itens da Venda</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Subtotal</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody id="venda-items">
                </tbody>
            </table>
        </div>
        <div class="text-right mt-3">
            <h3 class="total-venda">Total: R$ <span id="total-geral">0.00</span></h3>
            <input type="hidden" name="valor_total" id="valor_total_hidden" value="0">
        </div>

        <div class="form-group mt-4">
            <label for="observacao">Observações</label>
            <textarea name="observacao" class="form-control"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary btn-lg mt-3">Finalizar Venda</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2 para Clientes
    $('#cliente_id').select2({
        placeholder: 'Selecione um cliente',
        ajax: {
            url: 'vendas.php?action=search_clientes',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    // Inicializar Select2 para Produtos
    $('#produto_select').select2({
        placeholder: 'Pesquisar produto...',
        ajax: {
            url: 'vendas.php?action=search_produtos',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    // Adicionar produto à tabela
    $('#add-produto').on('click', function() {
        var produtoData = $('#produto_select').select2('data')[0];
        if (!produtoData || !produtoData.id) {
            alert('Selecione um produto.');
            return;
        }

        var produtoId = produtoData.id;
        var produtoNome = produtoData.text.split(' (Estoque:')[0];
        var precoVenda = parseFloat(produtoData.preco_venda || 0).toFixed(2);
        var estoque = parseInt(produtoData.estoque);

        if ($('#venda-items').find(`tr[data-id="${produtoId}"]`).length > 0) {
            alert('Este produto já foi adicionado.');
            return;
        }
        
        var row = `
            <tr data-id="${produtoId}">
                <td>${produtoNome}<input type="hidden" name="produtos[${produtoId}][id]" value="${produtoId}"></td>
                <td><input type="number" name="produtos[${produtoId}][quantidade]" class="form-control quantidade" value="1" min="1" max="${estoque}" required></td>
                <td><input type="text" name="produtos[${produtoId}][preco]" class="form-control preco" value="${precoVenda}" required></td>
                <td class="subtotal">${precoVenda}</td>
                <td><button type="button" class="btn btn-danger btn-sm remover-item">Remover</button></td>
            </tr>`;
        $('#venda-items').append(row);
        atualizarTotal();
    });

    // Remover item da tabela
    $('#venda-items').on('click', '.remover-item', function() {
        $(this).closest('tr').remove();
        atualizarTotal();
    });

    // Atualizar subtotal e total geral ao mudar quantidade ou preço
    $('#venda-items').on('input', '.quantidade, .preco', function() {
        var tr = $(this).closest('tr');
        var quantidade = parseInt(tr.find('.quantidade').val());
        var estoque = parseInt(tr.find('.quantidade').attr('max'));
        var preco = parseFloat(tr.find('.preco').val().replace(',', '.'));

        if (quantidade > estoque) {
            alert('Quantidade não pode ser maior que o estoque disponível (' + estoque + ').');
            tr.find('.quantidade').val(estoque);
            quantidade = estoque;
        }

        if (isNaN(quantidade) || isNaN(preco)) return;

        var subtotal = (quantidade * preco).toFixed(2);
        tr.find('.subtotal').text(subtotal);
        atualizarTotal();
    });

    function atualizarTotal() {
        var total = 0;
        $('#venda-items tr').each(function() {
            var subtotal = parseFloat($(this).find('.subtotal').text());
            if (!isNaN(subtotal)) {
                total += subtotal;
            }
        });
        $('#total-geral').text(total.toFixed(2));
        $('#valor_total_hidden').val(total.toFixed(2));
    }

    $('#form-venda').on('submit', function(e){
        if ($('#venda-items tr').length === 0) {
            e.preventDefault();
            alert('Adicione pelo menos um produto à venda.');
        }
    });
});
</script>
</body>
</html>