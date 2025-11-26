<?php
require_once '../../includes/session_init.php';
include('../../database.php');

$conn = getMasterConnection();

// Filtro
$busca = $_GET['busca'] ?? '';
$filtro_exclusao = isset($_GET['filtro_exclusao']);

$where = "WHERE status = 'resolvido'";

if (!empty($busca)) {
    $where .= " AND (protocolo LIKE '%$busca%' OR nome LIKE '%$busca%')";
}

// Filtro de 5 anos
if ($filtro_exclusao) {
    $where .= " AND resolvido_em <= DATE_SUB(NOW(), INTERVAL 5 YEAR)";
}

$query = "SELECT *, 
          (resolvido_em <= DATE_SUB(NOW(), INTERVAL 5 YEAR)) as elegivel_exclusao 
          FROM suporte_login $where ORDER BY resolvido_em DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Suporte Resolvidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #121212; color: #eee; }
        .table-dark { --bs-table-bg: #1e1e1e; }
        .btn-exclusao { background-color: #dc3545; color: white; }
        .alert-elegivel { border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.1); }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success"><i class="fas fa-check-circle"></i> Suporte - Resolvidos</h2>
            <div>
                <a href="suporte_via_login.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Voltar</a>
                <a href="?filtro_exclusao=1" class="btn btn-outline-danger <?= $filtro_exclusao ? 'active' : '' ?>">
                    <i class="fas fa-trash-clock"></i> Elegíveis Exclusão (5+ anos)
                </a>
            </div>
        </div>

        <!-- Busca -->
        <form class="mb-4 d-flex gap-2">
            <input type="text" name="busca" class="form-control bg-dark text-light border-secondary" placeholder="Buscar protocolo..." value="<?= htmlspecialchars($busca) ?>">
            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
            <?php if($filtro_exclusao): ?>
                <input type="hidden" name="filtro_exclusao" value="1">
            <?php endif; ?>
        </form>

        <div class="card bg-dark border-secondary p-3">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Resolvido Em</th>
                        <th>Nome</th>
                        <th>Descrição Resumida</th>
                        <th class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['elegivel_exclusao'] ? 'alert-elegivel' : '' ?>">
                            <td class="fw-bold text-info"><?= $row['protocolo'] ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($row['resolvido_em'])) ?>
                                <?php if($row['elegivel_exclusao']): ?>
                                    <span class="badge bg-danger ms-1" title="Arquivado há mais de 5 anos">Excluir</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['anonimo'] ? 'Anônimo' : htmlspecialchars($row['nome']) ?></td>
                            <td class="text-muted text-truncate" style="max-width: 250px;">
                                <?= htmlspecialchars(substr($row['descricao'], 0, 50)) ?>...
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info" onclick="alert('Detalhes: <?= addslashes($row['descricao']) ?>')"><i class="fas fa-eye"></i></button>
                                
                                <button class="btn btn-sm btn-danger" onclick="excluirSuporte(<?= $row['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function excluirSuporte(id) {
            if(confirm('Tem certeza que deseja excluir este registro permanentemente?')) {
                fetch('../../actions/admin_suporte_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'acao=excluir&id=' + id
                }).then(() => location.reload());
            }
        }
    </script>
</body>
</html>