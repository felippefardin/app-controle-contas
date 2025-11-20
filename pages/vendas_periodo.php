<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha de conexão com o banco de dados.");
}

// 2. DADOS DO USUÁRIO LOGADO
$id_usuario_logado = $_SESSION['usuario_id'];
$perfil_usuario    = $_SESSION['nivel_acesso']; // 'admin', 'proprietario', 'padrao'

// 3. FILTROS DO FORMULÁRIO
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Padrão: início do mês atual
$data_fim    = $_GET['data_fim'] ?? date('Y-m-t');     // Padrão: fim do mês atual
$porcentagem_comissao = isset($_GET['comissao']) ? floatval($_GET['comissao']) : 0;

// 🔒 LÓGICA DE SEGURANÇA DO FILTRO DE USUÁRIO
// Passo 1: Define o padrão como o próprio usuário logado
$usuario_filtro = $id_usuario_logado; 

// Passo 2: Se for ADMIN ou PROPRIETÁRIO, permite ver todos ou escolher outro
if ($perfil_usuario === 'admin' || $perfil_usuario === 'proprietario') {
    if (isset($_GET['usuario_id'])) {
        $usuario_filtro = $_GET['usuario_id']; // Pode ser 'todos' ou um ID específico
    } else {
        $usuario_filtro = 'todos'; // Admin vê geral por padrão
    }
} else {
    // ⛔ SE FOR PADRÃO, FORÇA O FILTRO PARA O PRÓPRIO ID (Segurança)
    $usuario_filtro = $id_usuario_logado;
}

// 4. CONSTRUÇÃO DA QUERY
$params = [];
$types = "";

$sql = "SELECT v.id, v.data_venda, v.valor_total, u.nome as nome_vendedor, c.nome as nome_cliente 
        FROM vendas v
        JOIN usuarios u ON v.id_usuario = u.id
        LEFT JOIN pessoas_fornecedores c ON v.id_cliente = c.id
        WHERE v.data_venda BETWEEN ? AND ? ";

// Adiciona as datas aos parâmetros (com horário completo para pegar o dia todo)
$params[] = $data_inicio . " 00:00:00";
$params[] = $data_fim . " 23:59:59";
$types .= "ss";

// Aplica o filtro de usuário se não for 'todos'
if ($usuario_filtro !== 'todos') {
    $sql .= " AND v.id_usuario = ? ";
    $params[] = $usuario_filtro;
    $types .= "i";
}

$sql .= " ORDER BY v.data_venda DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 5. CÁLCULO DOS TOTAIS
$total_vendas = 0;
$vendas = [];
while ($row = $result->fetch_assoc()) {
    $total_vendas += $row['valor_total'];
    $vendas[] = $row;
}

// Calcula a comissão baseada no total filtrado
$valor_comissao = ($total_vendas * $porcentagem_comissao) / 100;

// 6. BUSCA LISTA DE USUÁRIOS (SOMENTE PARA O SELECT DO ADMIN)
$usuarios_lista = [];
if ($perfil_usuario === 'admin' || $perfil_usuario === 'proprietario') {
    $res_users = $conn->query("SELECT id, nome FROM usuarios ORDER BY nome ASC");
    $usuarios_lista = $res_users->fetch_all(MYSQLI_ASSOC);
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas e Comissão</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #1e1e1e; padding: 25px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
        h2 { color: #00bfff; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        
        .filter-box { background: #2a2a2a; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #444; }
        .form-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .form-group { flex: 1; min-width: 150px; }
        .form-group label { display: block; margin-bottom: 5px; color: #bbb; font-size: 0.9em; }
        .form-control { width: 100%; padding: 10px; background: #333; border: 1px solid #555; color: #fff; border-radius: 5px; box-sizing: border-box; }
        
        .btn-submit { background-color: #00bfff; color: #121212; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s; height: 40px; }
        .btn-submit:hover { background-color: #0095cc; }

        .results-box { display: flex; gap: 20px; margin-bottom: 30px; }
        .card-result { flex: 1; background: #242424; padding: 20px; border-radius: 8px; text-align: center; border-left: 5px solid #444; }
        .card-result h3 { margin: 0 0 10px 0; font-size: 1rem; color: #aaa; }
        .card-result p { margin: 0; font-size: 1.8rem; font-weight: bold; color: #fff; }
        
        .card-total { border-left-color: #28a745; }
        .card-comissao { border-left-color: #ffc107; }
        .card-comissao p { color: #ffc107; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #222; border-radius: 5px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #333; color: #00bfff; }
        tr:hover { background-color: #2a2a2a; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 0.8em; background: #444; }

        @media (max-width: 768px) {
            .results-box { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-chart-line"></i> Vendas por Período & Comissão</h2>

    <form method="GET" class="filter-box">
        <div class="form-row">
            <div class="form-group">
                <label>Data Início:</label>
                <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" required>
            </div>
            <div class="form-group">
                <label>Data Fim:</label>
                <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>" required>
            </div>

            <?php if ($perfil_usuario === 'admin' || $perfil_usuario === 'proprietario'): ?>
            <div class="form-group">
                <label>Vendedor:</label>
                <select name="usuario_id" class="form-control">
                    <option value="todos" <?= $usuario_filtro === 'todos' ? 'selected' : '' ?>>-- Todos --</option>
                    <?php foreach ($usuarios_lista as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $usuario_filtro == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Comissão (%):</label>
                <input type="number" name="comissao" class="form-control" step="0.1" min="0" max="100" value="<?= htmlspecialchars($porcentagem_comissao) ?>" placeholder="Ex: 5">
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-search"></i> Filtrar</button>
        </div>
    </form>

    <div class="results-box">
        <div class="card-result card-total">
            <h3>Total Vendido</h3>
            <p>R$ <?= number_format($total_vendas, 2, ',', '.') ?></p>
        </div>
        <div class="card-result card-comissao">
            <h3>Comissão (<?= $porcentagem_comissao ?>%)</h3>
            <p>R$ <?= number_format($valor_comissao, 2, ',', '.') ?></p>
        </div>
    </div>

    <?php if (count($vendas) > 0): ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <?php if ($usuario_filtro === 'todos'): ?>
                            <th>Vendedor</th>
                        <?php endif; ?>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                            <td><?= htmlspecialchars($venda['nome_cliente'] ?? 'Cliente não iden.') ?></td>
                            
                            <?php if ($usuario_filtro === 'todos'): ?>
                                <td><span class="badge"><?= htmlspecialchars($venda['nome_vendedor']) ?></span></td>
                            <?php endif; ?>
                            
                            <td style="color: #28a745; font-weight: bold;">R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #aaa; margin-top: 20px;">Nenhuma venda encontrada neste período.</p>
    <?php endif; ?>
    
    <div style="margin-top: 20px; text-align: right;">
        <button onclick="window.print()" style="background: #555; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> Imprimir Relatório
        </button>
    </div>

</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>