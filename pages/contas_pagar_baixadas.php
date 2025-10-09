<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include('../includes/header.php');
include('../database.php');

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];

// --- CORREÇÃO: Especificado "c.status" para remover a ambiguidade ---
$where = ["c.status = 'baixada'"];

if ($perfil !== 'admin') {
    $where[] = "c.usuario_id = '$usuarioId'";
}

if (!empty($_GET['fornecedor'])) $where[] = "fornecedor LIKE '%" . $conn->real_escape_string($_GET['fornecedor']) . "%'";
if (!empty($_GET['numero'])) $where[] = "numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['valor'])) $where[] = "valor = " . floatval($_GET['valor']);
if (!empty($_GET['data_vencimento'])) $where[] = "data_vencimento = '" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

$sql = "SELECT c.*, u.nome AS usuario_baixou
        FROM contas_pagar c
        LEFT JOIN usuarios u ON c.baixado_por = u.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.data_baixa DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Contas a Pagar - Baixadas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    /* SEUS ESTILOS CSS PERMANECEM AQUI */
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #00bfff; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    .btn-delete { background-color: transparent; border: none; color: #cc3333; cursor: pointer; font-weight: bold; text-decoration: underline; font-size: 1em; padding: 0; }
    /* Adicione o resto do seu CSS aqui para manter a página igual */
  </style>
</head>
<body>

<h2>Contas a Pagar - Baixadas</h2>

<?php
if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
} else {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td data-label='Fornecedor'>" . htmlspecialchars($row['fornecedor']) . "</td>";
        echo "<td data-label='Vencimento'>" . date('d/m/Y', strtotime($row['data_vencimento'])) . "</td>";
        echo "<td data-label='Número'>" . htmlspecialchars($row['numero']) . "</td>";
        echo "<td data-label='Valor'>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
        echo "<td data-label='Juros'>R$ " . number_format($row['juros'], 2, ',', '.') . "</td>";
        echo "<td data-label='Forma de Pagamento'>" . htmlspecialchars($row['forma_pagamento'] ?? '-') . "</td>";
        echo "<td data-label='Data de Baixa'>" . ($row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-') . "</td>";
        echo "<td data-label='Usuário'>" . htmlspecialchars($row['usuario_baixou'] ?? '-') . "</td>";
        echo "<td data-label='Ações'>
                <button class='btn-delete' onclick='openDeleteModal({$row['id']})'>Excluir</button>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>

<div id="deleteModal" class="modal" style="display: none;">
    </div>
<script>
    function openDeleteModal(id) {
        // Sua função de abrir modal aqui
    }
</script>

</body>
</html>
<?php include('../includes/footer.php'); ?>