<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php'; // Importa Utils para Flash Message

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// 2. PEGA DADOS
$usuarioId = $_SESSION['usuario_id'];
$perfil = $_SESSION['nivel_acesso'];

// --- BLOCO AJAX ---
if (isset($_GET['action'])) {
    $term = "%" . ($_GET['term'] ?? '') . "%";
    $response = [];

    // Busca de Fornecedores
    if ($_GET['action'] === 'search_fornecedores') {
        $sql = "SELECT id, nome FROM pessoas_fornecedores WHERE tipo = 'fornecedor' AND nome LIKE ? AND id_usuario = ?";
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
        $sql = "SELECT id, nome, preco_compra, quantidade_estoque FROM produtos WHERE nome LIKE ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $term, $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                'id' => $row['id'],
                'text' => $row['nome'] . " (Estoque atual: " . $row['quantidade_estoque'] . ")",
                'preco_compra' => $row['preco_compra']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['results' => $response]);
    exit;
}

// --- CARREGAMENTO DA PÁGINA ---
include('../includes/header.php');

// EXIBE O FLASH CARD FLUTUANTE
display_flash_message();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Compra</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* === ESTILO GERAL === */
        body { background-color: #121212; color: #eee; }
        
        /* Container Principal - Ajustado para telas grandes */
        .container { 
            background-color: #222; 
            padding: 25px; 
            border-radius: 8px; 
            margin-top: 30px;
            margin-bottom: 30px;
            max-width: 1200px; /* Mais largo para Desktop Full */
        }

        h1, h2 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* Form Controls */
        .form-control, .select2-container .select2-selection--single { 
            background-color: #333; 
            color: #eee; 
            border-color: #444; 
            height: calc(1.5em + .75rem + 2px); 
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            color: #eee; 
            line-height: 38px; 
        }
        
        .select2-dropdown { background-color: #333; border-color: #444; }
        .select2-results__option { color: #eee; }
        .select2-results__option--highlighted { background-color: #0af !important; }
        
        /* Garante que o Select2 seja responsivo */
        .select2-container { width: 100% !important; }

        /* Tabelas */
        .table { color: #eee; }
        .table thead th { border-top: none; border-bottom: 2px solid #0af; }
        .table-bordered td, .table-bordered th { border: 1px solid #444; }
        
        .total-compra { font-size: 1.5rem; font-weight: bold; color: #28a745; }

        /* === RESPONSIVIDADE (TABLET E MOBILE) === */
        @media (max-width: 768px) {
            .container {
                padding: 15px; /* Menos padding no mobile */
                margin-top: 15px;
                width: 95%; /* Ocupa quase toda a largura */
            }

            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.3rem; }

            /* Ajuste na tabela para não quebrar */
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
            
            /* Botão de ação (Remover) menor no mobile */
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            /* Total um pouco menor no mobile */
            .total-compra { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-dolly"></i> Registrar Compra</h1>

    <form action="../actions/registrar_compra.php" method="POST" id="form-compra">
        <div class="form-group">
            <label for="fornecedor_id">Fornecedor</label>
            <select id="fornecedor_id" name="id_fornecedor" class="form-control" required></select>
        </div>
        
        <div class="card bg-dark text-white mb-4">
            <div class="card-header">
                <h2 class="mb-0" style="border:none; padding:0; font-size: 1.25rem;">Adicionar Produtos</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8 col-12">
                        <label>Produto</label>
                        <select id="produto_select" class="form-control"></select>
                    </div>
                    <div class="form-group col-md-4 col-12 d-flex align-items-end">
                        <button type="button" id="add-produto" class="btn btn-success btn-block">Adicionar à Compra</button>
                    </div>
                </div>
            </div>
        </div>

        <h2><i class="fas fa-boxes"></i> Itens da Compra</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th style="width: 120px;">Qtd</th>
                        <th style="width: 150px;">Custo Un.</th>
                        <th>Subtotal</th>
                        <th style="width: 80px;">Ação</th>
                    </tr>
                </thead>
                <tbody id="compra-items">
                </tbody>
            </table>
        </div>
        <div class="text-right mt-3">
            <h3 class="total-compra">Total: R$ <span id="total-geral">0.00</span></h3>
            <input type="hidden" name="valor_total" id="valor_total_hidden" value="0">
        </div>

        <div class="form-group mt-4">
            <label for="observacao">Observações</label>
            <textarea name="observacao" class="form-control" rows="3"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary btn-lg btn-block mt-4 mb-3">Finalizar Compra</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Configuração padrão do Select2 para largura responsiva
    const select2Config = {
        width: '100%', // Força 100% de largura no container pai
        language: {
            noResults: function() { return "Nenhum resultado encontrado"; },
            searching: function() { return "Pesquisando..."; }
        }
    };

    // Inicializar Select2 para Fornecedores
    $('#fornecedor_id').select2({
        ...select2Config,
        placeholder: 'Selecione um fornecedor',
        ajax: {
            url: 'compras.php?action=search_fornecedores',
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
        ...select2Config,
        placeholder: 'Pesquisar produto...',
        ajax: {
            url: 'compras.php?action=search_produtos',
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
        var produtoNome = produtoData.text.split(' (Estoque atual:')[0];
        var precoCompra = parseFloat(produtoData.preco_compra || 0).toFixed(2);

        if ($('#compra-items').find(`tr[data-id="${produtoId}"]`).length > 0) {
            alert('Este produto já foi adicionado.');
            return;
        }
        
        var row = `
            <tr data-id="${produtoId}">
                <td class="align-middle">${produtoNome}<input type="hidden" name="produtos[${produtoId}][id]" value="${produtoId}"></td>
                <td><input type="number" name="produtos[${produtoId}][quantidade]" class="form-control quantidade" value="1" min="1" required></td>
                <td><input type="text" name="produtos[${produtoId}][preco]" class="form-control preco" value="${precoCompra}" required></td>
                <td class="subtotal align-middle">${precoCompra}</td>
                <td class="align-middle text-center"><button type="button" class="btn btn-danger btn-sm remover-item"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#compra-items').append(row);
        atualizarTotal();
        
        // Limpa a seleção do produto após adicionar
        $('#produto_select').val(null).trigger('change');
    });

    // Remover item da tabela
    $('#compra-items').on('click', '.remover-item', function() {
        $(this).closest('tr').remove();
        atualizarTotal();
    });

    // Atualizar subtotal e total geral ao mudar quantidade ou preço
    $('#compra-items').on('input', '.quantidade, .preco', function() {
        var tr = $(this).closest('tr');
        var quantidade = parseInt(tr.find('.quantidade').val());
        // Aceita vírgula ou ponto como decimal
        var preco = parseFloat(tr.find('.preco').val().replace(',', '.'));

        if (isNaN(quantidade) || quantidade < 1) quantidade = 0;
        if (isNaN(preco)) preco = 0;

        var subtotal = (quantidade * preco).toFixed(2);
        tr.find('.subtotal').text(subtotal);
        atualizarTotal();
    });

    function atualizarTotal() {
        var total = 0;
        $('#compra-items tr').each(function() {
            var subtotal = parseFloat($(this).find('.subtotal').text());
            if (!isNaN(subtotal)) {
                total += subtotal;
            }
        });
        $('#total-geral').text(total.toFixed(2));
        $('#valor_total_hidden').val(total.toFixed(2));
    }

    $('#form-compra').on('submit', function(e){
        if ($('#compra-items tr').length === 0) {
            e.preventDefault();
            alert('Adicione pelo menos um produto à compra.');
        }
    });
});
</script>

<?php include('../includes/footer.php'); ?>

</body>
</html>