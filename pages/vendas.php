<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Incluído no início

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    // Se não estiver logado, redireciona para o login
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO CORRETA
$usuario_logado = $_SESSION['usuario_logado'];
$usuarioId = $usuario_logado['id'];
$perfil = $usuario_logado['nivel_acesso'];

// --- BLOCO DE PROCESSAMENTO AJAX PARA BUSCAS ---
if (isset($_GET['action'])) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $response = [];

    // ✅ 3. SIMPLIFICA A QUERY AJAX PARA O MODELO SAAS
    // Busca de Clientes
    if ($_GET['action'] === 'search_clientes') {
        $sql = "SELECT id, nome FROM pessoas_fornecedores WHERE tipo = 'pessoa' AND nome LIKE ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $term, $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = ['id' => $row['id'], 'text' => $row['nome']];
        }
    }

    // Busca de Produtos
    if ($_GET['action'] === 'search_produtos') {
        $sql = "SELECT id, nome, preco_venda, quantidade_estoque FROM produtos WHERE nome LIKE ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $term, $usuarioId);
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
    /* ======= PADRÃO GERAL ======= */
    body {
        background-color: #121212;
        color: #eee;
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    .container {
        background-color: #1e1e1e;
        padding: 25px;
        border-radius: 10px;
        margin: 30px auto;
        box-shadow: 0 0 15px rgba(0,0,0,0.4);
        max-width: 1200px;
    }

    h1, h2 {
        color: #00bfff;
        border-bottom: 2px solid #00bfff;
        padding-bottom: 8px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    label { font-weight: bold; color: #ccc; }

    .form-control,
    .select2-container .select2-selection--single {
        background-color: #2a2a2a;
        color: #eee;
        border: 1px solid #444;
        border-radius: 6px;
        height: calc(1.5em + .75rem + 2px);
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #00bfff;
        box-shadow: 0 0 5px #00bfff33;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #eee;
        line-height: 38px;
    }

    .select2-dropdown {
        background-color: #333;
        border: 1px solid #444;
    }

    .select2-results__option { color: #eee; }
    .select2-results__option--highlighted { background-color: #00bfff !important; }

    /* ======= BOTÕES ======= */
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }

    /* ======= TABELA DE ITENS ======= */
    .table-responsive {
        background-color: #1a1a1a;
        border-radius: 8px;
        overflow-x: auto;
        box-shadow: inset 0 0 5px rgba(255,255,255,0.05);
    }

    .table {
        color: #ddd;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #00bfff;
        color: #fff;
        text-align: center;
        padding: 10px;
        border: none;
        font-weight: 600;
    }

    .table tbody tr {
        background-color: #2a2a2a;
        transition: background-color 0.2s, transform 0.1s;
    }

    .table tbody tr:hover {
        background-color: #333;
        transform: scale(1.01);
    }

    .table td {
        vertical-align: middle;
        text-align: center;
        border-top: 1px solid #444;
        padding: 10px;
    }

    .table input.form-control {
        background-color: #2b2b2b;
        border: 1px solid #444;
        color: #fff;
        text-align: center;
        padding: 5px;
        border-radius: 5px;
    }

    /* ======= TOTAL ======= */
    .total-venda {
        font-size: 1.8rem;
        font-weight: bold;
        color: #28a745;
        text-shadow: 0 0 8px #28a74555;
    }

    /* ======= ALERTAS ======= */
    .alert {
        border-radius: 6px;
        font-weight: 500;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    /* ======= CARD DE PRODUTOS ======= */
    .card.bg-dark {
        background-color: #1a1a1a !important;
        border: 1px solid #333;
    }

    .card-header {
        background-color: #00bfff22;
        border-bottom: 1px solid #00bfff55;
        color: #00bfff;
        font-weight: 600;
    }

    /* ======= ROLAGEM SUAVE ======= */
    ::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    ::-webkit-scrollbar-thumb {
        background-color: #555;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background-color: #00bfff;
    }
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

<?php include('../includes/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    let tipoFinalizacao = 'recibo';

    // === Select2: Clientes ===
    $('#cliente_id').select2({
        placeholder: 'Selecione ou digite o nome de um cliente',
        ajax: {
            url: 'vendas.php?action=search_clientes',
            dataType: 'json',
            delay: 250,
            processResults: data => ({ results: data.results }),
            cache: true
        }
    });

    // === Select2: Produtos ===
    $('#produto_select').select2({
        placeholder: 'Pesquisar produto pelo nome...',
        ajax: {
            url: 'vendas.php?action=search_produtos',
            dataType: 'json',
            delay: 250,
            processResults: data => ({ results: data.results }),
            cache: true
        }
    });

    // === Adicionar Produto ===
    $('#add-produto').on('click', function() {
        const produtoData = $('#produto_select').select2('data')[0];
        if (!produtoData || !produtoData.id) return showAlert('Selecione um produto para adicionar.', 'warning');

        const produtoId = produtoData.id;
        const produtoNome = produtoData.text.split(' (Estoque:')[0];
        const precoVenda = parseFloat(produtoData.preco_venda || 0).toFixed(2);
        const estoque = parseInt(produtoData.estoque);

        if ($('#venda-items').find(`tr[data-id="${produtoId}"]`).length > 0)
            return showAlert('Este produto já foi adicionado.', 'warning');
        if (estoque <= 0)
            return showAlert('Este produto está sem estoque.', 'danger');

        const row = `
            <tr data-id="${produtoId}" data-preco="${precoVenda}">
                <td>${produtoNome}</td>
                <td><input type="number" class="form-control quantidade" value="1" min="1" max="${estoque}"></td>
                <td class="preco-unitario">R$ ${precoVenda}</td>
                <td class="subtotal">R$ ${precoVenda}</td>
                <td><button type="button" class="btn btn-danger btn-sm remover-item"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#venda-items').append(row);
        atualizarTotal();
        $('#produto_select').val(null).trigger('change');
    });

    // === Atualizar linha quando quantidade mudar ===
    $('#venda-items').on('input', '.quantidade', function() {
        const tr = $(this).closest('tr');
        const qtd = Math.max(1, parseInt($(this).val()) || 1);
        const estoque = parseInt($(this).attr('max'));
        if (qtd > estoque) {
            showAlert(`⚠️ Estoque máximo disponível: ${estoque}`, 'warning');
            $(this).val(estoque);
        }
        const preco = parseFloat(tr.data('preco'));
        const subtotal = (qtd * preco).toFixed(2);
        tr.find('.subtotal').text('R$ ' + subtotal);
        atualizarTotal();
    });

    // === Remover produto ===
    $('#venda-items').on('click', '.remover-item', function() {
        $(this).closest('tr').remove();
        atualizarTotal();
    });

    // === Atualizar total com desconto ===
    $('#desconto').on('input', atualizarTotal);

    function atualizarTotal() {
        let total = 0;
        $('#venda-items tr').each(function() {
            const valor = parseFloat($(this).find('.subtotal').text().replace('R$ ', '')) || 0;
            total += valor;
        });
        let desconto = parseFloat($('#desconto').val().replace(',', '.')) || 0;
        if (desconto > total) desconto = total;
        const totalLiquido = (total - desconto).toFixed(2);
        $('#total-geral').text(totalLiquido);
    }

    // === Finalizar Venda ===
    $('#btn-recibo').on('click', function() {
        tipoFinalizacao = 'recibo';
        $('#form-venda').submit();
    });
    $('#btn-nfe').on('click', function() {
        tipoFinalizacao = 'nfe';
        $('#form-venda').submit();
    });

    // === Envio do formulário ===
    $('#form-venda').on('submit', function(e) {
        e.preventDefault();
        if ($('#venda-items tr').length === 0)
            return showAlert('Adicione pelo menos um produto à venda.', 'danger');

        const itens = $('#venda-items tr').map(function() {
            const tr = $(this);
            return {
                id: tr.data('id'),
                quantidade: parseInt(tr.find('.quantidade').val()),
                preco: parseFloat(tr.data('preco'))
            };
        }).get();

        const formData = new FormData(this);
        formData.append('itens', JSON.stringify(itens));
        formData.append('tipo_finalizacao', tipoFinalizacao);

        showAlert('Processando venda...', 'info');

        fetch('../actions/registrar_venda.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                if (tipoFinalizacao === 'recibo') {
                    window.open('recibo_venda.php?id=' + data.venda_id, '_blank');
                } else {
                    emitirNFe(data.venda_id);
                }
                limparFormulario();
            } else {
                showAlert('Erro: ' + data.message, 'danger');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            showAlert('Erro ao comunicar com o servidor.', 'danger');
        });
    });

    function emitirNFe(vendaId) {
        showAlert('⏳ Emitindo NF-e...', 'info');
        fetch('../actions/emitir_nfce.php', {
            method: 'POST',
            body: new URLSearchParams({ id_venda: vendaId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) showAlert('✅ NF-e emitida! Chave: ' + data.chave, 'success');
            else showAlert('❌ Erro ao emitir NF-e: ' + data.message, 'danger');
        })
        .catch(err => {
            console.error(err);
            showAlert('Erro de comunicação com o servidor na emissão da NF-e.', 'danger');
        });
    }

    function limparFormulario() {
        $('#form-venda')[0].reset();
        $('#cliente_id').val(null).trigger('change');
        $('#venda-items').empty();
        atualizarTotal();
    }

    function showAlert(message, type) {
        $('#alert-container').html(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        `);
    }
});
</script>
</body>
</html>