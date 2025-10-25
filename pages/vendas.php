<?php
require_once '../includes/session_init.php';

// --- BLOCO DE PROCESSAMENTO AJAX PARA BUSCAS ---
if (isset($_GET['action'])) {
    include('../database.php');

    // 2. VERIFICAÇÃO DE LOGIN
    if (!isset($_SESSION['usuario_principal']) || !isset($_SESSION['usuario'])) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    if (!isset($_SESSION['usuario']['id'])) {
        header('Content-Type: application/json');
        echo json_encode(['results' => []]);
        exit;
    }

    $usuarioId = $_SESSION['usuario']['id'];
    $perfil = $_SESSION['usuario']['perfil'];
    $id_criador = $_SESSION['usuario']['id_criador'] ?? 0;
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $response = [];

    $subUsersQuery = '';
    if ($perfil !== 'admin') {
        $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
        $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId} OR id = {$mainUserId}";
    }

    // Busca de Clientes
    if ($_GET['action'] === 'search_clientes') {
        $sql = "SELECT id, nome FROM pessoas_fornecedores WHERE tipo = 'pessoa' AND nome LIKE ?";
        if (!empty($subUsersQuery)) {
            $sql .= " AND id_usuario IN ({$subUsersQuery})";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = ['id' => $row['id'], 'text' => $row['nome']];
        }
    }

    // Busca de Produtos
    if ($_GET['action'] === 'search_produtos') {
        $sql = "SELECT id, nome, preco_venda, quantidade_estoque FROM produtos WHERE nome LIKE ?";
        if (!empty($subUsersQuery)) {
            $sql .= " AND id_usuario IN ({$subUsersQuery})";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                'id' => $row['id'],
                'text' => $row['nome'] . " (Estoque: " . $row['quantidade_estoque'] . ")",
                'preco_venda' => $row['preco_venda'],
                'estoque' => $row['quantidade_estoque']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['results' => $response]);
    exit;
}


// --- CARREGAMENTO NORMAL DA PÁGINA ---
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
    <title>Caixa de Vendas (PDV)</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1, h2 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; }
        label { font-weight: bold; }
        .form-control, .select2-container .select2-selection--single { background-color: #333; color: #eee; border-color: #444; height: calc(1.5em + .75rem + 2px); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: #eee; line-height: 38px; }
        .select2-dropdown { background-color: #333; border-color: #444; }
        .select2-results__option { color: #eee; }
        .select2-results__option--highlighted { background-color: #0af !important; }
        .table { color: #eee; }
        .table thead th { background-color: #0af; color: #fff; }
        .total-venda { font-size: 1.8rem; font-weight: bold; color: #28a745; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0069d9; border-color: #0062cc; }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-success:hover { background-color: #218838; border-color: #1e7e34; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-cash-register"></i> Caixa de Vendas (PDV)</h1>
        <a href="fechamento_caixa.php" class="btn btn-info"><i class="fas fa-print"></i> Fechamento de Caixa</a>
    </div>
    
    <div id="alert-container"></div>

    <form id="form-venda">
        <div class="form-group">
            <label for="cliente_id">Cliente</label>
            <select id="cliente_id" name="cliente_id" class="form-control" required></select>
        </div>
        
        <div class="card bg-dark text-white mb-4">
            <div class="card-header"><h2>Adicionar Produtos</h2></div>
            <div class="card-body">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-8">
                        <label for="produto_select">Produto</label>
                        <select id="produto_select" class="form-control"></select>
                    </div>
                    <div class="form-group col-md-4">
                        <button type="button" id="add-produto" class="btn btn-success btn-block"><i class="fas fa-plus"></i> Adicionar à Venda</button>
                    </div>
                </div>
            </div>
        </div>

        <h2><i class="fas fa-shopping-cart"></i> Itens da Venda</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Produto</th><th style="width: 120px;">Quantidade</th><th style="width: 150px;">Preço Unit.</th><th style="width: 150px;">Subtotal</th><th style="width: 100px;">Ação</th></tr></thead>
                <tbody id="venda-items"></tbody>
            </table>
        </div>
        <div class="text-right mt-3">
            <h3 class="total-venda">Total: R$ <span id="total-geral">0.00</span></h3>
        </div>

        <div class="form-row mt-4">
            <div class="form-group col-md-4">
                <label for="forma_pagamento">Forma de Pagamento</label>
                <select id="forma_pagamento" name="forma_pagamento" class="form-control" required>
                    <option value="dinheiro" selected>Dinheiro</option>
                    <option value="pix">PIX</option>
                    <option value="cartao_debito">Cartão de Débito</option>
                    <option value="cartao_credito">Cartão de Crédito</option>
                    <option value="fiado">Fiado (A Prazo)</option>
                </select>
            </div>
             <div class="form-group col-md-3">
                <label for="desconto">Desconto (R$)</label>
                <input type="text" id="desconto" name="desconto" class="form-control" placeholder="0.00">
            </div>
        </div>
        
        <div class="mt-4">
            <button type="button" id="btn-recibo" class="btn btn-primary btn-lg mr-2">
                <i class="fas fa-receipt"></i> Finalizar e Gerar Recibo
            </button>
            <button type="button" id="btn-nfe" class="btn btn-success btn-lg">
                <i class="fas fa-file-invoice"></i> Finalizar e Emitir NF-e
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    let tipoFinalizacao = 'recibo';

    $('#cliente_id').select2({
        placeholder: 'Selecione ou digite o nome de um cliente',
        ajax: {
            url: 'vendas.php?action=search_clientes',
            dataType: 'json',
            delay: 250,
            processResults: function (data) { return { results: data.results }; },
            cache: true
        }
    });

    $('#produto_select').select2({
        placeholder: 'Pesquisar produto pelo nome...',
        ajax: {
            url: 'vendas.php?action=search_produtos',
            dataType: 'json',
            delay: 250,
            processResults: function (data) { return { results: data.results }; },
            cache: true
        }
    });

    $('#add-produto').on('click', function() {
        var produtoData = $('#produto_select').select2('data')[0];
        if (!produtoData || !produtoData.id) {
            showAlert('Selecione um produto para adicionar.', 'warning');
            return;
        }
        var produtoId = produtoData.id;
        var produtoNome = produtoData.text.split(' (Estoque:')[0];
        var precoVenda = parseFloat(produtoData.preco_venda || 0).toFixed(2);
        var estoque = parseInt(produtoData.estoque);

        if ($('#venda-items').find(`tr[data-id="${produtoId}"]`).length > 0) {
            showAlert('Este produto já foi adicionado.', 'warning');
            return;
        }
        if (estoque <= 0) {
            showAlert('Este produto não tem estoque disponível.', 'danger');
            return;
        }
        
        var row = `<tr data-id="${produtoId}" data-preco="${precoVenda}" data-nome="${produtoNome}">
            <td>${produtoNome}</td>
            <td><input type="number" class="form-control quantidade" value="1" min="1" max="${estoque}" required></td>
            <td class="preco-unitario">R$ ${precoVenda}</td>
            <td class="subtotal">R$ ${precoVenda}</td>
            <td><button type="button" class="btn btn-danger btn-sm remover-item"><i class="fas fa-trash"></i></button></td>
        </tr>`;
        $('#venda-items').append(row);
        atualizarTotal();
        $('#produto_select').val(null).trigger('change');
    });

    $('#venda-items').on('click', '.remover-item', function() { $(this).closest('tr').remove(); atualizarTotal(); });
    $('#venda-items').on('input', '.quantidade', atualizarLinha);
    $('#desconto').on('input', atualizarTotal);

    function atualizarLinha() {
        var tr = $(this).closest('tr');
        var quantidade = parseInt(tr.find('.quantidade').val());
        var estoque = parseInt(tr.find('.quantidade').attr('max'));
        var preco = parseFloat(tr.data('preco'));
        if (quantidade > estoque) {
            showAlert('Quantidade excede o estoque (' + estoque + ').', 'warning');
            tr.find('.quantidade').val(estoque);
            quantidade = estoque;
        }
        if (isNaN(quantidade) || quantidade < 1) {
            quantidade = 1;
            tr.find('.quantidade').val(1);
        }
        var subtotal = (quantidade * preco).toFixed(2);
        tr.find('.subtotal').text('R$ ' + subtotal);
        atualizarTotal();
    }

    function atualizarTotal() {
        var total = 0;
        $('#venda-items tr').each(function() {
            total += parseFloat($(this).find('.subtotal').text().replace('R$ ', ''));
        });
        var desconto = parseFloat($('#desconto').val().replace(',', '.')) || 0;
        var totalLiquido = total - desconto;
        if(totalLiquido < 0) totalLiquido = 0;
        $('#total-geral').text(totalLiquido.toFixed(2));
    }

    $('#btn-recibo').on('click', function() {
        tipoFinalizacao = 'recibo';
        $('#form-venda').submit();
    });

    $('#btn-nfe').on('click', function() {
        tipoFinalizacao = 'nfe';
        $('#form-venda').submit();
    });

    $('#form-venda').on('submit', function(e) {
        e.preventDefault();
        if ($('#venda-items tr').length === 0) {
            showAlert('Adicione pelo menos um produto à venda.', 'danger');
            return;
        }
        
        let itens = [];
        $('#venda-items tr').each(function() {
            let tr = $(this);
            itens.push({ id: tr.data('id'), quantidade: parseInt(tr.find('.quantidade').val()), preco: parseFloat(tr.data('preco')) });
        });

        const formData = new FormData(this);
        formData.append('itens', JSON.stringify(itens));
        formData.append('tipo_finalizacao', tipoFinalizacao);

        showAlert('Processando venda...', 'info');

        fetch('../actions/registrar_venda.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) { throw new Error('Falha na resposta do servidor. Verifique o log de erros do PHP.'); }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                if (data.tipo_finalizacao === 'recibo') {
                    window.open('recibo_venda.php?id=' + data.venda_id, '_blank');
                } else if (data.tipo_finalizacao === 'nfe') {
                    emitirNFe(data.venda_id);
                }
                limparFormulario();
            } else {
                showAlert('Erro ao registrar venda: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro na requisição fetch:', error);
            showAlert('Ocorreu um erro de comunicação com o servidor. Verifique o console (F12).', 'danger');
        });
    });

    function emitirNFe(vendaId) {
        showAlert('Venda registrada! Iniciando emissão da NF-e...', 'info');
        const nfeData = new FormData();
        nfeData.append('id_venda', vendaId);

        fetch('../actions/emitir_nfce.php', {
            method: 'POST',
            body: nfeData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('NF-e emitida com sucesso! Chave: ' + data.chave, 'success');
            } else {
                showAlert('Falha ao emitir NF-e: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro na emissão da NF-e:', error);
            showAlert('Erro de comunicação ao tentar emitir a NF-e.', 'danger');
        });
    }

    function limparFormulario() {
        $('#form-venda')[0].reset();
        $('#cliente_id').val(null).trigger('change');
        $('#venda-items').empty();
        atualizarTotal();
    }
    
    function showAlert(message, type) {
        $('#alert-container').html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>`);
    }
});
</script>
</body>
</html>