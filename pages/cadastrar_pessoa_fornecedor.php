<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// 2. PEGA O ID DO USUÁRIO DA SESSÃO CORRETA
$usuarioId = $_SESSION['usuario_logado']['id'];

include('../includes/header.php');

// 3. CONSULTA SQL PARA LISTAR APENAS OS REGISTROS DO USUÁRIO LOGADO
$sql = "SELECT * FROM pessoas_fornecedores WHERE id_usuario = ? ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Clientes/Fornecedores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Seus estilos CSS (sem alterações) */
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
            text-align: center;
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
        }
        .btn-primary {
            background-color: #0af;
            border: none;
        }
        .table thead th {
            background-color: #0af;
            color: #fff;
        }
        .table tbody tr {
            background-color: #2c2c2c;
        }
        .table tbody tr:hover {
            background-color: #3c3c3c;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-users"></i> Clientes e Fornecedores</h1>

    <div class="card bg-dark text-white mb-4">
        <div class="card-header">
            <h2>Cadastrar Novo</h2>
        </div>
        <div class="card-body">
            <form action="../actions/cadastrar_pessoa_fornecedor_action.php" method="POST">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nome">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="cpf_cnpj">CPF ou CNPJ</label>
                        <input type="text" class="form-control" name="cpf_cnpj">
                    </div>
                </div>
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" class="form-control" name="endereco">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="contato">Contato (Telefone)</label>
                        <input type="text" class="form-control" name="contato">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">E-mail</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select name="tipo" class="custom-select" required>
                        <option value="pessoa">Pessoa (Cliente)</option>
                        <option value="fornecedor">Fornecedor</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>
        </div>
    </div>

    <h2><i class="fa-solid fa-list"></i> Cadastrados</h2>
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Pesquisar..." style="background-color: #333; color: #eee; border-color: #444;">
    <div class="table-responsive">
        <table class="table table-bordered table-dark">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF/CNPJ</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nome']) ?></td>
                <td><?= htmlspecialchars($row['cpf_cnpj']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= ucfirst(htmlspecialchars($row['tipo'])) ?></td>
                <td>
                    <a href="historico_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-history"></i></a>
                    <a href="editar_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a href="../actions/excluir_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');"><i class="fa-solid fa-trash"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Script de busca (sem alterações)
document.getElementById('searchInput').addEventListener('keyup', function() {
    var filter = this.value.toLowerCase();
    var rows = document.getElementById('tableBody').getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var found = false;
        for (var j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
});
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>