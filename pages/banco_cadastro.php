<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Lógica para buscar contas bancárias já cadastradas
$where = [];
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId} OR id = {$mainUserId}";
    $where[] = "id_usuario IN ({$subUsersQuery})";
}

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

/* Responsividade */
@media (max-width: 992px) {
    .form-row {
        display: flex;
        flex-direction: column;
    }
    .form-group {
        width: 100% !important;
    }
    .btn-action {
        display: block;
        width: 100%;
        margin-bottom: 5px;
        text-align: center;
    }
    table, thead, tbody, th, td, tr {
        display: block;
    }
    .table thead tr {
        display: none; /* Esconde cabeçalho da tabela no mobile */
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
    }
}
</style>
</head>
<body>
<div class="container">
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
                    <th>Ações</th> </tr>
            </thead>
            <tbody>
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
        <a href="../actions/excluir_banco.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-delete btn-action" onclick="return confirm('Tem certeza que deseja excluir esta conta?');">
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