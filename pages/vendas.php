<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ----------------------------
// 1. VERIFICA SE USUÁRIO ESTÁ LOGADO
// ----------------------------
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) { 
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ----------------------------
// 2. DADOS DO USUÁRIO
// ----------------------------
$id_usuario = $_SESSION['usuario_id'];
$perfil     = $_SESSION['nivel_acesso'];

// ----------------------------
// 3. BLOCO AJAX PARA BUSCAS
// ----------------------------
if (isset($_GET['action'])) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $response = [];

    if ($_GET['action'] === 'search_clientes') {
        $sql = "SELECT id, nome 
                FROM pessoas_fornecedores 
                WHERE tipo = 'pessoa' AND nome LIKE ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $term, $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = ['id' => $row['id'], 'text' => $row['nome']];
        }
    }

    if ($_GET['action'] === 'search_produtos') {
        $sql = "SELECT id, nome, preco_venda, quantidade_estoque 
                FROM produtos 
                WHERE nome LIKE ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $term, $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                'id'          => $row['id'],
                'text'        => $row['nome'] . " (Estoque: " . $row['quantidade_estoque'] . ")",
                'preco_venda' => $row['preco_venda'],
                'estoque'     => $row['quantidade_estoque']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['results' => $response]);
    exit;
}

// ----------------------------
// 4. INCLUDES DE CABEÇALHO
// ----------------------------
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
</head>
<body>
    <style>
/* =========================================
   PADRÃO GERAL
========================================= */
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
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
    max-width: 1200px;
}

/* TÍTULOS */
h1, h2 {
    color: #00bfff;
    border-bottom: 2px solid #00bfff;
    padding-bottom: 8px;
    margin-bottom: 20px;
    font-weight: 600;
}

/* LABELS */
label {
    font-weight: bold;
    color: #ccc;
}

/* =========================================
   CAMPOS DE FORMULÁRIO
========================================= */
.form-control,
.select2-container .select2-selection--single {
    background-color: #2a2a2a !important;
    color: #eee !important;
    border: 1px solid #444 !important;
    border-radius: 6px !important;
    height: calc(1.5em + .75rem + 2px) !important;
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #00bfff !important;
    box-shadow: 0 0 5px #00bfff33 !important;
}

/* SELECT2 */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #eee !important;
    line-height: 38px !important;
}

.select2-dropdown {
    background-color: #333 !important;
    border: 1px solid #444 !important;
}

.select2-results__option {
    color: #eee !important;
}

.select2-results__option--highlighted {
    background-color: #00bfff !important;
}

/* =========================================
   BOTÕES
========================================= */
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

/* =========================================
   TABELAS
========================================= */
.table-responsive {
    background-color: #1a1a1a;
    border-radius: 8px;
    overflow-x: auto;
    box-shadow: inset 0 0 5px rgba(255, 255, 255, 0.05);
}

.table {
    color: #ddd;
    width: 100%;
    border-collapse: collapse;
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

/* =========================================
   TOTAL
========================================= */
.total-venda {
    font-size: 1.8rem;
    font-weight: bold;
    color: #28a745;
    text-shadow: 0 0 8px #28a74555;
}

/* =========================================
   ALERTAS
========================================= */
.alert {
    border-radius: 6px;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

/* =========================================
   CARDS
========================================= */
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

/* =========================================
   SCROLL
========================================= */
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

/* =========================================
   META DE VENDAS
========================================= */
.meta-widget {
    background-color: #2a2a2a;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 25px;
    border: 1px solid #333;
}

.meta-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.meta-header-titulo {
    color: #00bfff;
    font-weight: 600;
}

.meta-header-valores {
    color: #eee;
    font-weight: bold;
}

.meta-header-editar {
    color: #00bfff;
    cursor: pointer;
    transition: color 0.2s;
    font-size: 0.9rem;
}

.meta-header-editar:hover {
    color: #fff;
}

.progress {
    height: 25px;
    background-color: #1a1a1a;
    border-radius: 5px;
    border: 1px solid #444;
    padding: 2px;
}

.progress-bar {
    background-color: #28a745 !important;
    color: #fff;
    font-weight: bold;
    line-height: 21px;
    text-align: center;
    overflow: visible;
    text-shadow: 0 0 2px #000;
    transition: width 0.6s ease;
}

</style>
<div class="container">

    <!-- =========================
         HEADER
    ========================== -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-cash-register"></i> Caixa de Vendas (PDV)</h1>
        <a href="fechamento_caixa.php" class="btn btn-info">
            <i class="fas fa-print"></i> Fechamento de Caixa
        </a>
    </div>

    <!-- =========================
         META DE VENDAS
    ========================== -->
    <div class="meta-widget">
        <div class="meta-header">
            <span class="meta-header-titulo"><i class="fas fa-bullseye"></i> Meta de Vendas (Mês)</span>
            <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
                <span id="btn-editar-meta" class="meta-header-editar" data-toggle="modal" data-target="#modalMetaVendas" title="Definir Meta do Mês">
                    <i class="fas fa-pencil-alt"></i> Definir Meta
                </span>
            <?php endif; ?>
        </div>
        <div class="progress">
            <div id="meta-progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
        <div class="meta-header-valores text-center mt-2">
            <span id="meta-valores-texto">Atual: R$ 0,00 / Meta: R$ 0,00</span>
        </div>
    </div>

    <!-- ALERTA -->
    <div id="alert-container"></div>

    <!-- =========================
         FORMULÁRIO DE VENDA
    ========================== -->
    <form id="form-venda">

        <!-- CLIENTE -->
        <div class="form-group">
            <label for="cliente_id">Cliente</label>
            <select id="cliente_id" name="cliente_id" class="form-control" required></select>
        </div>

        <!-- ADICIONAR PRODUTOS -->
        <div class="card bg-dark text-white mb-4">
            <div class="card-header"><h2>Adicionar Produtos</h2></div>
            <div class="card-body">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-8">
                        <label for="produto_select">Produto</label>
                        <select id="produto_select" class="form-control"></select>
                    </div>
                    <div class="form-group col-md-4">
                        <button type="button" id="add-produto" class="btn btn-success btn-block">
                            <i class="fas fa-plus"></i> Adicionar à Venda
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ITENS DA VENDA -->
        <h2><i class="fas fa-shopping-cart"></i> Itens da Venda</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th style="width: 120px;">Quantidade</th>
                        <th style="width: 150px;">Preço Unit.</th>
                        <th style="width: 150px;">Subtotal</th>
                        <th style="width: 100px;">Ação</th>
                    </tr>
                </thead>
                <tbody id="venda-items"></tbody>
            </table>
        </div>

        <!-- TOTAL -->
        <div class="text-right mt-3">
            <h3 class="total-venda">Total: R$ <span id="total-geral">0.00</span></h3>
        </div>

        <!-- FORMA DE PAGAMENTO E DESCONTO -->
        <div class="form-row mt-4">
            <div class="form-group col-md-4">
                <label for="forma_pagamento">Forma de Pagamento</label>
                <select id="forma_pagamento" name="forma_pagamento" class="form-control" required>
                    <option value="dinheiro" selected>Dinheiro</option>
                    <option value="pix">PIX</option>
                    <option value="cartao_debito">Cartão de Débito</option>
                    <option value="cartao_credito">Cartão de Crédito</option>
                    <option value="receber">A Receber (A Prazo)</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="desconto">Desconto (R$)</label>
                <input type="text" id="desconto" name="desconto" class="form-control" placeholder="0.00">
            </div>
        </div>

        <!-- BOTÕES FINALIZAR -->
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

<!-- =========================
     MODAL META DE VENDAS (ADMIN)
========================== -->
<?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
<div class="modal fade" id="modalMetaVendas" tabindex="-1" role="dialog" aria-labelledby="modalMetaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background-color: #222; color: #eee;">
            <div class="modal-header" style="border-bottom: 1px solid #444;">
                <h5 class="modal-title" id="modalMetaLabel">Definir Meta de Vendas do Mês</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-meta-vendas">
                    <div class="form-group">
                        <label for="valor_meta_input">Valor da Meta (R$)</label>
                        <input type="text" class="form-control" id="valor_meta_input" name="meta" placeholder="Ex: 10000,00" style="background-color: #333; color: #eee; border: 1px solid #555;">
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #444;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-salvar-meta" class="btn btn-primary">Salvar Meta</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include('../includes/footer.php'); ?>

<!-- =========================
     SCRIPTS
========================== -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function() {

    let tipoFinalizacao = 'recibo';
    const userPerfil = '<?= htmlspecialchars($perfil, ENT_QUOTES, 'UTF-8') ?>';

    /* ================================
       CARREGAR META DE VENDAS
    ================================== */
    function carregarMetaVendas() {
        fetch('../actions/get_meta_vendas.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {

                const meta = parseFloat(data.meta);
                const atual = parseFloat(data.atual);
                let percentual = 0;

                if (meta > 0) {
                    percentual = Math.min(100, (atual / meta) * 100);
                }

                $('#meta-valores-texto').text(`Atual: R$ ${data.atual_formatado} / Meta: R$ ${data.meta_formatada}`);

                $('#meta-progress-bar')
                    .css('width', percentual + '%')
                    .attr('aria-valuenow', percentual)
                    .text(percentual.toFixed(1) + '%');

                if (userPerfil === 'admin' || userPerfil === 'proprietario') {
                    $('#valor_meta_input').val(data.meta_formatada.replace(/\./g, '')); 
                }

            } else {
                console.warn('Não foi possível carregar a meta de vendas: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Erro ao buscar meta:', err);
            showAlert('Erro de comunicação ao buscar meta de vendas.', 'danger');
        });
    }

    carregarMetaVendas();

    /* ================================
       MODAL DE META (ADMIN)
    ================================== */
    if (userPerfil === 'admin' || userPerfil === 'proprietario') {

        $('#valor_meta_input').mask('000.000.000,00', {reverse: true});

        $('#btn-salvar-meta').on('click', function() {

            const formData = new FormData();
            const metaValor = $('#valor_meta_input').val();

            if (metaValor === '') {
                showAlert('Por favor, insira um valor para a meta.', 'warning');
                return;
            }

            formData.append('meta', metaValor);

            fetch('../actions/set_meta_vendas.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    $('#modalMetaVendas').modal('hide');
                    carregarMetaVendas();

                } else {
                    showAlert('Erro: ' + data.message, 'danger');
                }
            })
            .catch(err => {
                console.error('Erro ao salvar meta:', err);
                showAlert('Erro de comunicação ao salvar a meta.', 'danger');
            });
        });
    }

    /* ================================
       SELECT2 CLIENTES
    ================================== */
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

    /* ================================
       SELECT2 PRODUTOS
    ================================== */
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

    /* ================================
       ADICIONAR PRODUTO
    ================================== */
    $('#add-produto').on('click', function() {

        const produtoData = $('#produto_select').select2('data')[0];
        if (!produtoData || !produtoData.id)
            return showAlert('Selecione um produto para adicionar.', 'warning');

        const produtoId = produtoData.id;
        const produtoNome = produtoData.text.split(' (Estoque:')[0];
        const precoVenda = parseFloat(produtoData.preco_venda || 0).toFixed(2);
        const estoque = parseInt(produtoData.estoque);

        if ($(`#venda-items tr[data-id="${produtoId}"]`).length > 0)
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

    /* ================================
       ALTERAÇÃO DE QUANTIDADE
    ================================== */
    $('#venda-items').on('input', '.quantidade', function() {

        const tr = $(this).closest('tr');
        let qtd = parseInt($(this).val()) || 1;
        const estoque = parseInt($(this).attr('max'));

        if (qtd < 1) {
            qtd = 1;
            $(this).val(1);
        }

        if (qtd > estoque) {
            showAlert(`⚠️ Estoque máximo disponível: ${estoque}`, 'warning');
            $(this).val(estoque);
            qtd = estoque;
        }

        const preco = parseFloat(tr.data('preco'));
        const subtotal = (qtd * preco).toFixed(2);

        tr.find('.subtotal').text('R$ ' + subtotal);

        atualizarTotal();
    });

    /* ================================
       REMOVER ITEM
    ================================== */
    $('#venda-items').on('click', '.remover-item', function() {
        $(this).closest('tr').remove();
        atualizarTotal();
    });

    /* ================================
       MÁSCARA DE DESCONTO
    ================================== */
    $('#desconto').mask('000.000.000,00', {reverse: true});
    $('#desconto').on('input', atualizarTotal);

    /* ================================
       CALCULAR TOTAL
    ================================== */
    function atualizarTotal() {

        let total = 0;

        $('#venda-items tr').each(function() {
            const valor = parseFloat($(this).find('.subtotal').text().replace('R$ ', '')) || 0;
            total += valor;
        });

        let desconto = parseFloat($('#desconto').val().replace(/\./g, '').replace(',', '.')) || 0;

        if (desconto > total)
            desconto = total;

        const totalLiquido = (total - desconto).toFixed(2);

        $('#total-geral').text(totalLiquido.replace('.', ','));
    }

    /* ================================
       BOTÕES FINALIZAR
    ================================== */
    $('#btn-recibo').on('click', function() {
        tipoFinalizacao = 'recibo';
        $('#form-venda').submit();
    });

    $('#btn-nfe').on('click', function() {
        tipoFinalizacao = 'nfe';
        $('#form-venda').submit();
    });

    /* ================================
       SUBMIT DA VENDA
    ================================== */
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

        const descontoFormatado =
            parseFloat($('#desconto').val().replace(/\./g, '').replace(',', '.')) || 0;

        formData.set('desconto', descontoFormatado);

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
                carregarMetaVendas();

            } else {
                showAlert('Erro: ' + data.message, 'danger');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            showAlert('Erro ao comunicar com o servidor.', 'danger');
        });

    });

    /* ================================
       EMITIR NF-e
    ================================== */
    function emitirNFe(vendaId) {

        showAlert('⏳ Emitindo NF-e...', 'info');

        fetch('../actions/emitir_nfce.php', {
            method: 'POST',
            body: new URLSearchParams({ id_venda: vendaId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success)
                showAlert('✅ NF-e emitida! Chave: ' + data.chave, 'success');
            else
                showAlert('❌ Erro ao emitir NF-e: ' + data.message, 'danger');
        })
        .catch(err => {
            console.error(err);
            showAlert('Erro de comunicação com o servidor na emissão da NF-e.', 'danger');
        });
    }

    /* ================================
       LIMPAR FORMULÁRIO
    ================================== */
    function limparFormulario() {
        $('#form-venda')[0].reset();
        $('#cliente_id').val(null).trigger('change');
        $('#venda-items').empty();
        atualizarTotal();
    }

    /* ================================
       ALERTA DINÂMICO
    ================================== */
    function showAlert(message, type) {

        $('#alert-container .alert').alert('close');

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