<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

// 2. VERIFICAÇÃO DE LOGIN
if (!isset($_SESSION['usuario_principal']) || !isset($_SESSION['usuario'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Monta filtros SQL
$where = [];
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId} OR id = {$mainUserId}";
    $where[] = "id_usuario IN ({$subUsersQuery})";
}

$sql = "SELECT * FROM pessoas_fornecedores";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY nome ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Clientes/Fornecedores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
   
</head>
<body>
<style>
/* ====== BASE DARK ====== */
body {
    background-color: #121212;
    color: #eee;
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    padding-bottom: 60px;
}

/* ====== CONTAINER ====== */
.container {
    background-color: #222;
    padding: 25px;
    border-radius: 8px;
    margin-top: 30px;
    box-shadow: 0 0 15px rgba(0, 170, 255, 0.1);
}

/* ====== TÍTULOS ====== */
h1, h2 {
    color: #eee;
    border-bottom: 2px solid #0af;
    padding-bottom: 10px;
    margin-bottom: 1.5rem;
    text-align: center;
}

/* ====== FORMULÁRIOS ====== */
label {
    font-weight: 500;
    color: #ccc;
}

.form-control, .custom-select {
    background-color: #333;
    color: #eee;
    border: 1px solid #444;
    border-radius: 6px;
    transition: border-color 0.2s ease;
}

.form-control:focus, .custom-select:focus {
    background-color: #333;
    color: #eee;
    border-color: #0af;
    box-shadow: none;
}

/* ====== BOTÃO PRINCIPAL ====== */
.btn-primary {
    background-color: #0af;
    border: none;
    color: #fff;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 6px;
    transition: 0.3s;
}

.btn-primary:hover {
    background-color: #0095e6;
    transform: scale(1.03);
}

/* ====== BOTÕES DE AÇÃO ====== */
.btn-sm {
    margin: 2px;
    border-radius: 6px;
    transition: transform 0.2s, opacity 0.2s;
}

.btn-sm:hover {
    transform: scale(1.05);
    opacity: 0.9;
}

/* ====== CORES DOS BOTÕES ====== */
.btn-success { background-color: #28a745; border: none; }
.btn-success:hover { background-color: #218838; }

.btn-info { background-color: #007bff; border: none; }
.btn-info:hover { background-color: #0069d9; }

.btn-danger { background-color: #dc3545; border: none; }
.btn-danger:hover { background-color: #c82333; }

/* ====== TABELAS ====== */
.table {
    color: #eee;
    border-radius: 6px;
    overflow: hidden;
}

.table thead th {
    background-color: #0af;
    color: #fff;
    border: none;
}

.table tbody tr {
    background-color: #2c2c2c;
    transition: background-color 0.3s ease;
}

.table tbody tr:hover {
    background-color: #3c3c3c;
}

.table td, .table th {
    text-align: center;
    vertical-align: middle;
}

/* ====== CAMPO DE PESQUISA ====== */
#searchInput {
    background-color: #333;
    color: #eee;
    border: 1px solid #444;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 20px;
}

#searchInput:focus {
    border-color: #0af;
    box-shadow: none;
}

/* ====== MODAL (CASO SEJA USADO) ====== */
.modal-content {
    background-color: #222;
    border: 1px solid #444;
}

.modal-header, .modal-footer {
    border-color: #444;
}

.close {
    color: #fff;
    text-shadow: none;
    opacity: 0.7;
}

.close:hover {
    opacity: 1;
}

/* ====== DESTAQUE ERROS ====== */
.table-danger,
.table-danger > th,
.table-danger > td {
    background-color: #dc3545 !important;
    color: #fff !important;
}

/* ====== RESPONSIVIDADE ====== */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }

    .form-group {
        width: 100%;
    }

    .btn, .btn-sm {
        width: 100%;
        margin-top: 8px;
    }

    h1, h2 {
        font-size: 1.3rem;
    }
}
</style>


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
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Pesquisar...">
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
                    <a href="historico_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-history"></i> Histórico
                    </a>
                    <a href="editar_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                        <i class="fa-solid fa-pen-to-square"></i> Editar
                    </a>
                    <a href="../actions/excluir_pessoa_fornecedor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este registro?');">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
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
        if (found) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>