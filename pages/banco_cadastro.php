<?php
require_once '../includes/session_init.php';
require_once '../database.php'; 

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO CORRETA
$usuarioId = $_SESSION['usuario_id'];
$perfil = $_SESSION['nivel_acesso'] ?? 'padrao';

include('../includes/header.php');

// ✅ 3. BUSCA OS BANCOS
$where = ["id_usuario = " . intval($usuarioId)];

$sql = "SELECT * FROM contas_bancarias";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY nome_banco ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Contas Bancárias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
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
        .form-control {
            background-color: #333;
            color: #eee;
            border: 1px solid #444;
        }
        .form-control:focus {
            background-color: #333;
            color: #eee;
            border-color: #0af;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #0af;
            border: none;
        }
        .table {
            color: #eee;
        }
        .table thead {
            background-color: #0af;
            color: #fff;
        }
        .table tbody tr {
            background-color: #2c2c2c;
        }
        .table tbody tr:hover {
            background-color: #3c3c3c;
        }
        .btn-action {
            padding: 5px 10px;
            font-size: 14px;
            margin: 0 2px;
            text-decoration: none !important;
            color: white !important;
        }
        .btn-edit { background-color: #17a2b8; }
        .btn-delete { background-color: #dc3545; }
        
        /* Estilos do Modal Escuro */
        .modal-content {
            background-color: #333;
            color: #eee;
            border: 1px solid #444;
        }
        .modal-header {
            border-bottom: 1px solid #444;
        }
        .modal-footer {
            border-top: 1px solid #444;
        }
        .close {
            color: #eee;
            text-shadow: none;
        }
        .close:hover {
            color: #0af;
        }

        @media (max-width: 992px) {
            .form-row { display: flex; flex-direction: column; }
            .form-group { width: 100% !important; }
            .btn-action { display: block; width: 100%; margin-bottom: 5px; text-align: center; }
            table, thead, tbody, th, td, tr { display: block; }
            .table thead tr { display: none; }
            .table tbody tr { margin-bottom: 15px; border-bottom: 2px solid #0af; padding: 10px 5px; }
            .table tbody td { display: flex; justify-content: space-between; padding: 5px 10px; text-align: left; }
            .table tbody td::before { content: attr(data-label); font-weight: bold; color: #0af; }
        }
    </style>
</head>
<body>
<div class="container">
    
    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle"></i> Banco cadastrado com sucesso!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sucesso_edicao']) && $_GET['sucesso_edicao'] == 1): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle"></i> Banco atualizado com sucesso!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sucesso_exclusao']) && $_GET['sucesso_exclusao'] == 1): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-trash-can"></i> Banco excluído com sucesso!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) || isset($_GET['erro_edicao']) || isset($_GET['erro_exclusao'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i> Ocorreu um erro na operação. Tente novamente.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <h1><i class="fa-solid fa-university"></i> Minhas Contas Bancárias</h1>
    
    <div class="card bg-dark text-white mb-4">
        <div class="card-header">
            <h2>Cadastrar Nova Conta</h2>
        </div>
        <div class="card-body">
            <form action="../actions/cadastrar_banco_action.php" method="POST">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nome_banco">Nome do Banco</label>
                        <input type="text" class="form-control" name="nome_banco" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="tipo_conta">Tipo de Conta (ex: Corrente, Poupança)</label>
                        <input type="text" class="form-control" name="tipo_conta">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="agencia">Agência</label>
                        <input type="text" class="form-control" name="agencia">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="conta">Número da Conta</label>
                        <input type="text" class="form-control" name="conta" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="chave_pix">Chave PIX</label>
                    <input type="text" class="form-control" name="chave_pix">
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar Conta</button>
            </form>
        </div>
    </div>

    <h2><i class="fa-solid fa-list"></i> Contas Cadastradas</h2>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Agência</th>
                    <th>Conta</th>
                    <th>Chave PIX</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Banco"><?= htmlspecialchars($row['nome_banco']) ?></td>
                        <td data-label="Agência"><?= htmlspecialchars($row['agencia']) ?></td>
                        <td data-label="Conta"><?= htmlspecialchars($row['conta']) ?></td>
                        <td data-label="Chave PIX"><?= htmlspecialchars($row['chave_pix']) ?></td>
                        <td data-label="Ações">
                            <a href="editar_banco.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit btn-action">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-delete btn-action" 
                                    data-toggle="modal" 
                                    data-target="#modalExcluir"
                                    data-id="<?= $row['id'] ?>"
                                    data-nome="<?= htmlspecialchars($row['nome_banco']) ?>">
                                <i class="fa-solid fa-trash"></i> Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhuma conta bancária cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalExcluir" tabindex="-1" role="dialog" aria-labelledby="modalExcluirLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExcluirLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir o banco <strong id="nomeBancoExcluir"></strong>? <br>
                Essa ação não pode ser desfeita.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">Confirmar Exclusão</a>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
    // Script para passar os dados para o modal
    $('#modalExcluir').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Botão que acionou o modal
        var id = button.data('id'); // Extrai info dos atributos data-*
        var nome = button.data('nome');
        
        var modal = $(this);
        modal.find('#nomeBancoExcluir').text(nome);
        // Atualiza o link do botão de confirmação
        modal.find('#btnConfirmarExclusao').attr('href', '../actions/excluir_banco.php?id=' + id);
    });
</script>

</body>
</html>