<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_pessoa_fornecedor = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pessoa_fornecedor === 0) {
    echo "<div class='container'><h1>ID inválido.</h1></div>";
    exit;
}

// Buscar nome do cliente/fornecedor
$stmt = $conn->prepare("SELECT nome FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_pessoa_fornecedor, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$pessoa = $result->fetch_assoc();
$nome_pessoa = $pessoa ? $pessoa['nome'] : 'Não encontrado';

// Buscar histórico de contas a pagar (pendentes e baixadas) com categoria
$stmt_pagar = $conn->prepare("
    SELECT cp.*, c.nome AS nome_categoria
    FROM contas_pagar cp
    LEFT JOIN categorias c ON cp.id_categoria = c.id
    WHERE cp.id_pessoa_fornecedor = ? AND cp.usuario_id = ? 
    ORDER BY cp.data_vencimento DESC
");
$stmt_pagar->bind_param("ii", $id_pessoa_fornecedor, $id_usuario);
$stmt_pagar->execute();
$result_pagar = $stmt_pagar->get_result();

// Buscar histórico de contas a receber (pendentes e baixadas) com categoria
$stmt_receber = $conn->prepare("
    SELECT cr.*, c.nome AS nome_categoria
    FROM contas_receber cr
    LEFT JOIN categorias c ON cr.id_categoria = c.id
    WHERE cr.id_pessoa_fornecedor = ? AND cr.usuario_id = ? 
    ORDER BY cr.data_vencimento DESC
");
$stmt_receber->bind_param("ii", $id_pessoa_fornecedor, $id_usuario);
$stmt_receber->execute();
$result_receber = $stmt_receber->get_result();

// Buscar histórico de estoque
$stmt_estoque = $conn->prepare("
    SELECT me.*, p.nome AS nome_produto 
    FROM movimento_estoque me
    JOIN produtos p ON me.id_produto = p.id
    WHERE me.id_pessoa_fornecedor = ? AND me.id_usuario = ? 
    ORDER BY me.data_movimento DESC
");
$stmt_estoque->bind_param("ii", $id_pessoa_fornecedor, $id_usuario);
$stmt_estoque->execute();
$result_estoque = $stmt_estoque->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico de <?= htmlspecialchars($nome_pessoa) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1, h2 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 1rem; }
        .table { color: #eee; }
        .table thead { background-color: #0af; color: #fff; }
        .table tbody tr { background-color: #2c2c2c; }
        .table tbody tr:hover { background-color: #3c3c3c; }
        .badge-entrada, .badge-baixada { background-color: #28a745; color: white; }
        .badge-saida, .badge-pendente { background-color: #dc3545; color: white; }
        #searchInput {
            background-color: #333;
            color: #eee;
            border: 1px solid #444;
            margin-bottom: 20px;
        }
        #searchInput:focus {
            border-color: #0af;
            box-shadow: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-history"></i> Histórico de: <?= htmlspecialchars($nome_pessoa) ?></h1>

    <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar por descrição, valor, categoria ou produto...">

    <h2><i class="fa-solid fa-file-invoice-dollar"></i> Histórico de Contas a Pagar</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Categoria</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="searchable">
                <?php if ($result_pagar->num_rows > 0): ?>
                    <?php while ($row = $result_pagar->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['numero'] ?? 'N/D') ?></td>
                        <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
                        <td><?= date("d/m/Y", strtotime($row['data_vencimento'])) ?></td>
                        <td><?= htmlspecialchars($row['nome_categoria'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge <?= $row['status'] == 'baixada' ? 'badge-baixada' : 'badge-pendente' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Nenhuma conta a pagar encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2><i class="fa-solid fa-hand-holding-dollar"></i> Histórico de Contas a Receber</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Categoria</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="searchable">
                <?php if ($result_receber->num_rows > 0): ?>
                    <?php while ($row = $result_receber->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['numero'] ?? 'N/D') ?></td>
                        <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
                        <td><?= date("d/m/Y", strtotime($row['data_vencimento'])) ?></td>
                        <td><?= htmlspecialchars($row['nome_categoria'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge <?= $row['status'] == 'baixada' ? 'badge-baixada' : 'badge-pendente' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Nenhuma conta a receber encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2><i class="fa-solid fa-box-open"></i> Histórico de Produtos (Estoque)</h2>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody class="searchable">
                <?php if ($result_estoque->num_rows > 0): ?>
                    <?php while ($row = $result_estoque->fetch_assoc()): ?>
                    <tr>
                        <td><?= date("d/m/Y H:i", strtotime($row['data_movimento'])) ?></td>
                        <td><?= htmlspecialchars($row['nome_produto']) ?></td>
                        <td>
                            <span class="badge <?= $row['tipo'] == 'entrada' ? 'badge-entrada' : 'badge-saida' ?>">
                                <?= ucfirst($row['tipo']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['quantidade']) ?></td>
                        <td><?= htmlspecialchars($row['observacao']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Nenhum movimento de estoque encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchableTables = document.querySelectorAll('.searchable');

        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase().trim();

            searchableTables.forEach(function(tbody) {
                const rows = tbody.querySelectorAll('tr');

                rows.forEach(function(row) {
                    // Ignora a linha de "nenhum registro encontrado"
                    if (row.querySelectorAll('td').length <= 1) {
                        return;
                    }

                    const cells = row.querySelectorAll('td');
                    let found = false;
                    cells.forEach(function(cell) {
                        if (cell.textContent.toLowerCase().includes(filter)) {
                            found = true;
                        }
                    });

                    if (found) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>