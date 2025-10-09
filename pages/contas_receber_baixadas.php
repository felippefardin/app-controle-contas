<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
// Assumindo que o id_criador está na sessão. Use 0 ou null como padrão.
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// --- CORREÇÃO: Especificado "c.status" para remover a ambiguidade ---
$where = ["c.status='baixada'"];

if ($perfil !== 'admin') {
    // Se id_criador for maior que 0, o usuário é secundário.
    // O ID principal é o id_criador. Caso contrário, é o próprio ID do usuário.
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;

    // Subconsulta para obter todos os IDs de usuários associados à conta principal
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";

    // A cláusula WHERE agora inclui o ID do usuário principal e todos os seus usuários secundários
    $where[] = "(c.usuario_id = {$mainUserId} OR c.usuario_id IN ({$subUsersQuery}))";
}

if (!empty($_GET['responsavel'])) $where[] = "responsavel LIKE '%" . $conn->real_escape_string($_GET['responsavel']) . "%'";
if (!empty($_GET['numero'])) $where[] = "numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['data_vencimento'])) $where[] = "data_vencimento='" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

$sql = "SELECT c.*, u.nome AS usuario_baixou
        FROM contas_receber c
        LEFT JOIN usuarios u ON c.baixado_por = u.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.data_baixa DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Contas a Receber - Baixadas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* SEUS ESTILOS CSS PERMANECEM AQUI */
        body { background-color:#121212; color:#eee; font-family:Arial,sans-serif; margin:0; padding:20px; }
        h2 { text-align:center; color:#00bfff; margin-bottom: 20px; }
        table { width:100%; border-collapse:collapse; background:#1f1f1f; border-radius:8px; overflow:hidden; margin-top:10px; }
        th, td { padding:12px 10px; border-bottom:1px solid #333; text-align:left; }
        th { background:#222; color:#00bfff; }
        /* ... (resto do seu CSS) ... */
    </style>
</head>
<body>

<h2>Contas a Receber - Baixadas</h2>

<?php
if ($result) {
    echo "<table>";
    echo "<thead><tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th></tr></thead>";
    echo "<tbody>";
    while($row = $result->fetch_assoc()){
        echo "<tr>";
        echo "<td data-label='Responsável'>".htmlspecialchars($row['responsavel'])."</td>";
        echo "<td data-label='Vencimento'>".($row['data_vencimento'] ? date('d/m/Y', strtotime($row['data_vencimento'])) : '-')."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
        echo "<td data-label='Juros'>R$ ".number_format((float)($row['juros'] ?? 0),2,',','.')."</td>";
        echo "<td data-label='Forma de Pagamento'>".htmlspecialchars($row['forma_pagamento'] ?? '-')."</td>";
        echo "<td data-label='Data de Baixa'>".($row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-')."</td>";
        echo "<td data-label='Usuário'>".htmlspecialchars($row['usuario_baixou'] ?? '-')."</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta baixada encontrada.</p>";
}
?>

</body>
</html>
<?php include('../includes/footer.php'); ?>