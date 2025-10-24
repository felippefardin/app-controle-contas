<?php
require_once '../includes/session_init.php';
require_once '../includes/header.php';
require_once '../database.php'; // Este arquivo cria a variável $conn

// Verificação de sessão correta
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

// Voltando a usar a conexão original "$conn" com "bind_param"
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id_usuario = ? ORDER BY nome ASC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Controle de Estoque</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Seu CSS dark mode (sem alterações) */
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1, h2 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 1rem; }
        .form-control, .custom-select { background-color: #333; color: #eee; border: 1px solid #444; }
        .form-control:focus, .custom-select:focus { background-color: #333; color: #eee; border-color: #0af; box-shadow: none; }
        .btn-primary { background-color: #0af; border: none; }
        .table { color: #eee; }
        .table thead th { background-color: #0af; color: #fff; }
        .table tbody tr { background-color: #2c2c2c; }
        .table tbody tr:hover { background-color: #3c3c3c; }
        .modal-content { background-color: #222; border: 1px solid #444; }
        .modal-header, .modal-footer { border-color: #444; }
        .close { color: #fff; text-shadow: none; opacity: 0.7; }
        .close:hover { opacity: 1; }
        .table-danger, .table-danger > th, .table-danger > td { background-color: #dc3545 !important; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-box-open"></i> Controle de Estoque</h1>

    <?php
    // Verifica se a sessão com os produtos de estoque baixo existe
    if (isset($_SESSION['produtos_estoque_baixo']) && !empty($_SESSION['produtos_estoque_baixo'])) {
        echo '<div id="notificacao-estoque-baixo" class="alert alert-danger">';
        echo '<strong>Atenção!</strong> Os seguintes produtos estão com estoque baixo:';
        echo '<ul>';
        foreach ($_SESSION['produtos_estoque_baixo'] as $produto) {
            echo '<li>' . htmlspecialchars($produto['nome']) . ' (Estoque: ' . htmlspecialchars($produto['quantidade_estoque']) . ')</li>';
        }
        echo '</ul>';
        echo '</div>';
        // Remove a variável da sessão para não mostrar a mensagem novamente
        unset($_SESSION['produtos_estoque_baixo']);
    }
    ?>

    <div class="card bg-dark text-white mb-4">
        <div class="card-header">
            <h2>Cadastrar Novo Produto</h2>
        </div>
        <div class="card-body">
            <form action="../actions/cadastrar_produto_action.php" method="POST">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nome">Nome do Produto</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="quantidade_estoque">Quantidade em Estoque</label>
                        <input type="number" class="form-control" name="quantidade_estoque" value="0" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="quantidade_minima">Quantidade Mínima</label>
                        <input type="number" class="form-control" name="quantidade_minima" value="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea class="form-control" name="descricao" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="preco_compra">Preço de Compra</label>
                        <input type="text" class="form-control" name="preco_compra" placeholder="0.00">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="preco_venda">Preço de Venda</label>
                        <input type="text" class="form-control" name="preco_venda" placeholder="0.00" required>
                    </div>
                </div>
                <hr style="border-top: 1px solid #444;">
                <h5>Informações Fiscais (para emissão de NFe)</h5>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="ncm">NCM</label>
                        <input type="text" class="form-control" name="ncm" placeholder="Ex: 84713000">
                        <small class="form-text text-muted">Nomenclatura Comum do Mercosul.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="cfop">CFOP</label>
                        <input type="text" class="form-control" name="cfop" placeholder="Ex: 5102">
                        <small class="form-text text-muted">Código Fiscal de Operações e Prestações.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar Produto</button>
            </form>
        </div>
    </div>

    <h2><i class="fa-solid fa-list"></i> Produtos Cadastrados</h2>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Estoque</th>
                    <th>Preço de Venda</th>
                    <th>NCM</th>
                    <th>CFOP</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php while($produto = $result->fetch_assoc()): ?>
                <tr class="<?= ($produto['quantidade_estoque'] <= $produto['quantidade_minima'] && $produto['quantidade_minima'] > 0) ? 'table-danger' : '' ?>">
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td><?= htmlspecialchars($produto['quantidade_estoque'] ?? $produto['quantidade']) ?></td>
                    <td>R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($produto['ncm'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($produto['cfop'] ?? '-') ?></td>
                    <td>
                        <a href="editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-info" title="Editar">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#excluirProdutoModal" data-url="../actions/excluir_produto_action.php?id=<?= $produto['id'] ?>" title="Excluir">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="excluirProdutoModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Confirmar Exclusão</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <a href="#" id="confirmarExclusao" class="btn btn-danger">Excluir</a>
      </div>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        // Faz a notificação desaparecer em 5 segundos
        setTimeout(function() {
            $('#notificacao-estoque-baixo').fadeOut('slow');
        }, 5000); // 5 segundos

        $('#excluirProdutoModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var url = button.data('url');
            $(this).find('#confirmarExclusao').attr('href', url);
        });
    });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>