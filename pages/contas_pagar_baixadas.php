<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include('../includes/header.php');
include('../database.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Pagar - Baixadas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background-color: #121212;
      color: #f0f0f0;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #00bfff;
    }

    .search-bar {
      max-width: 1000px;
      margin: 20px auto;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    .search-bar input[type="text"],
    .search-bar input[type="number"],
    .search-bar input[type="date"] {
      padding: 10px 10px 10px 32px;
      border-radius: 5px;
      border: none;
      background-color: #333;
      color: #eee;
      width: 180px;
      font-size: 14px;
    }

    .search-bar button {
      background-color: #00bfff;
      color: white;
      padding: 10px 18px;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      width: 150px;
    }

    .search-bar button:hover {
      background-color: #0099cc;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1f1f1f;
      border-radius: 8px;
      overflow: hidden;
      margin-top: 20px;
    }

    th, td {
      padding: 12px 10px;
      text-align: left;
      border-bottom: 1px solid #333;
    }

    th {
      background-color: #222;
      color: #00bfff;
    }

    tr:nth-child(even) {
      background-color: #262626;
    }

    tr:hover {
      background-color: #333;
    }

    a {
      color: #00bfff;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
    }

    .export-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin: 20px 0;
      flex-wrap: wrap;
    }

    .export-buttons a button {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    .export-buttons a button:hover {
      background-color: #1e874b;
    }

    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }

      th {
        display: none;
      }

      tr {
        margin-bottom: 20px;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 10px;
      }

      td {
        position: relative;
        padding-left: 50%;
      }

      td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        top: 12px;
        font-weight: bold;
        color: #aaa;
      }
    }
  </style>
</head>
<body>

<h2>Contas a Pagar - Baixadas</h2>

<!-- Barra de busca -->
<form method="GET" class="search-bar">
  <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?= htmlspecialchars($_GET['fornecedor'] ?? '') ?>">
  <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
  <input type="number" step="0.01" name="valor" placeholder="Valor" value="<?= htmlspecialchars($_GET['valor'] ?? '') ?>">
  <input type="date" name="data_vencimento" value="<?= htmlspecialchars($_GET['data_vencimento'] ?? '') ?>">
  <button type="submit">Buscar</button>
  <a href="contas_pagar_baixadas.php"><button type="button">Limpar Filtros</button></a>
</form>

<!-- Botões de Exportação -->
<div class="export-buttons">  
  <a href="../pages/exportar.php?tipo=pdf&status=baixada"><button type="button">Exportar PDF</button></a>
  <a href="../pages/exportar.php?tipo=excel&status=baixada"><button type="button">Exportar Excel</button></a>
  <a href="../pages/exportar.php?tipo=csv&status=baixada"><button type="button">Exportar CSV</button></a>
</div>

<?php
$where = ["status = 'baixada'"];
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

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
} else {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td data-label='Fornecedor'>" . htmlspecialchars($row['fornecedor']) . "</td>";
        echo "<td data-label='Vencimento'>" . htmlspecialchars($row['data_vencimento']) . "</td>";
        echo "<td data-label='Número'>" . htmlspecialchars($row['numero']) . "</td>";
        echo "<td data-label='Valor'>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
        echo "<td data-label='Forma de Pagamento'>" . htmlspecialchars($row['forma_pagamento']) . "</td>";
        echo "<td data-label='Data de Baixa'>" . htmlspecialchars($row['data_baixa']) . "</td>";
        echo "<td data-label='Usuário'>" . htmlspecialchars($row['usuario_baixou']) . "</td>";
        echo "<td data-label='Ações'>
                <a href='../actions/excluir_conta_pagar.php?id={$row['id']}' onclick=\"return confirm('Deseja excluir esta conta baixada?')\">Excluir</a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>

<p><a href="contas_pagar.php">← Voltar para a Home</a></p>

</body>
</html>

<?php include('../includes/footer.php'); ?>
