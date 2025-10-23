<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

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
        body {
            background-color: #121212;
            color: #eee;
        }
        .container {
            background-color: #222;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        h1, h2 {
            color: #eee;
            border-bottom: 2px solid #0af;
            padding-bottom: 10px;
            margin-bottom: 1rem;
        }
        .form-control, .custom-select {
            background-color: #333;
            color: #eee;
            border: 1px solid #444;
        }
        .form-control:focus, .custom-select:focus {
            background-color: #333;
            color: #eee;
            border-color: #0af;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #0af;
            border: none;
        }
        .table { color: #eee; }
        .table thead { background-color: #0af; color: #fff; }
        .table tbody tr { background-color: #2c2c2c; }
        .table tbody tr:hover { background-color: #3c3c3c; }
        .modal-content {
            background-color: #222;
            border: 1px solid #444;
        }
        .modal-header {
            border-bottom: 1px solid #444;
        }
        .modal-footer {
            border-top: 1px solid #444;
        }
        .close {
            color: #fff;
            text-shadow: none;
            opacity: 0.7;
        }
        .close:hover {
            color: #fff;
            opacity: 1;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-box-open"></i> Controle de Estoque</h1>

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
                    <div class="form-group col-md-6">
                        <label for="quantidade">Quantidade</label>
                        <input type="number" class="form-control" name="quantidade" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea class="form-control" name="descricao"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="preco_compra">Preço de Compra</label>
                        <input type="text" class="form-control" name="preco_compra">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="preco_venda">Preço de Venda</label>
                        <input type="text" class="form-control" name="preco_venda">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="unidade_medida">Unidade de Medida</label>
                        <input type="text" class="form-control" name="unidade_medida">
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
                    <th>Quantidade</th>
                    <th>Preço de Compra</th>
                    <th>Preço de Venda</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= htmlspecialchars($row['quantidade']) ?></td>
                <td>R$ <?= number_format($row['preco_compra'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($row['preco_venda'], 2, ',', '.') ?></td>
                <td>
                    <a href="editar_produto.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                        <i class="fa-solid fa-pen-to-square"></i> Editar
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#excluirProdutoModal" data-url="../actions/excluir_produto.php?id=<?= $row['id'] ?>">
                        <i class="fa-solid fa-trash"></i> Excluir
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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.
      </div>
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
        // Configura o modal de exclusão para pegar a URL do botão que o acionou
        $('#excluirProdutoModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botão que acionou o modal
            var url = button.data('url'); // Extrai a URL do atributo data-url
            
            var modal = $(this);
            // Atualiza o link do botão de confirmação no modal
            modal.find('#confirmarExclusao').attr('href', url);
        });
    });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>