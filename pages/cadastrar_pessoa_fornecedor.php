<?php
require_once '../includes/session_init.php';
// Caminhos ajustados para o local correto do arquivo
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

// Lógica para buscar pessoas e fornecedores já cadastrados
$stmt = $conn->prepare("SELECT * FROM pessoas_fornecedores WHERE id_usuario = ? ORDER BY nome ASC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Clientes/Fornecedores</title>
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

/* Container principal */
.container {
    background-color: #222;
    padding: 25px;
    border-radius: 8px;
    margin-top: 30px;
}

/* Títulos */
h1, h2 {
    color: #eee;
    border-bottom: 2px solid #0af;
    padding-bottom: 10px;
    margin-bottom: 1rem;
}

/* Formulário */
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

/* Botão */
.btn-primary {
    background-color: #0af;
    border: none;
}

/* Tabela */
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

/* Botões de ação */
.btn-action {
    padding: 5px 10px;
    font-size: 14px;
    margin: 0 2px;
    text-decoration: none !important;
    color: white !important;
}
.btn-edit { background-color: #17a2b8; }
.btn-delete { background-color: #dc3545; }

/* RESPONSIVIDADE MOBILE */
@media (max-width: 992px) {
    .form-row {
        display: flex;
        flex-direction: column;
    }
    .form-group {
        width: 100% !important;
        margin-bottom: 15px;
    }
    .btn-action {
        display: block;
        width: 100%;
        margin-bottom: 5px;
        text-align: center;
    }

    /* Tabela em estilo card para mobile */
    table, thead, tbody, th, td, tr {
        display: block;
    }
    .table thead tr {
        display: none; /* Esconde cabeçalho no mobile */
    }
    .table tbody tr {
        margin-bottom: 15px;
        border-bottom: 2px solid #0af;
        padding: 10px 5px;
    }
    .table tbody td {
        display: flex;
        justify-content: space-between;
        padding: 5px 10px;
        text-align: left;
    }
    .table tbody td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #0af;
        flex-basis: 40%;
    }
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
                        <input type="text" class="form-control" name="cpf_cnpj" required>
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
                        <input type="email" class="form-control" name="email" required>
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
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF/CNPJ</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Ações</th> </tr>
            </thead>
            <tbody>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td data-label="Nome"><?= htmlspecialchars($row['nome']) ?></td>
    <td data-label="CPF/CNPJ"><?= htmlspecialchars($row['cpf_cnpj']) ?></td>
    <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
    <td data-label="Tipo"><?= ucfirst(htmlspecialchars($row['tipo'])) ?></td>
    <td data-label="Ações">
        <a href="editar_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit btn-action">
            <i class="fa-solid fa-pen-to-square"></i> Editar
        </a>
        <a href="../actions/excluir_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-delete btn-action" onclick="return confirm('Tem certeza que deseja excluir este registro?');">
            <i class="fa-solid fa-trash"></i> Excluir
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>