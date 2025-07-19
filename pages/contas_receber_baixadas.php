<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

include('../includes/header.php');
include('../database.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Contas a Receber Baixadas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    /* RESET & BASE */
    * {
      box-sizing: border-box;
    }
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }
    h2 {
      text-align: center;
      color: #00bfff;
      margin-bottom: 20px;
    }
    a {
      color: #00bfff;
      text-decoration: none;
      font-weight: bold;
    }
    a:hover {
      text-decoration: underline;
    }

    /* Formulário de Busca */
    form.search-form {
      max-width: 900px;
      margin: 0 auto 25px auto;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }
    form.search-form input[type="text"],
    form.search-form input[type="number"],
    form.search-form input[type="date"] {
      padding: 10px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #444;
      background-color: #333;
      color: #eee;
      min-width: 180px;
      box-sizing: border-box;
    }
    form.search-form input::placeholder {
      color: #aaa;
    }
    form.search-form button,
    form.search-form a.clear-filters {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 10px 22px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      min-width: 120px;
      text-align: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
    }
    form.search-form button:hover,
    form.search-form a.clear-filters:hover {
      background-color: #1e874b;
    }
    form.search-form a.clear-filters {
      background-color: #cc3333;
    }
    form.search-form a.clear-filters:hover {
      background-color: #a02a2a;
    }

    /* Botões Exportar */
    .export-buttons {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin: 20px 0;
      flex-wrap: wrap;
    }
    .export-buttons a button {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .export-buttons a button:hover {
      background-color: #1e874b;
    }

    /* Tabela */
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1f1f1f;
      border-radius: 8px;
      overflow: hidden;
      margin-top: 10px;
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

    /* Responsivo */
    @media (max-width: 768px) {
      form.search-form {
        flex-direction: column;
        align-items: stretch;
      }
      form.search-form button,
      form.search-form a.clear-filters {
        width: 100%;
        min-width: auto;
      }
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
        margin-bottom: 10px;
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

<h2>Contas a Receber Baixadas</h2>

<!-- Formulário de Busca -->
<form method="GET" class="search-form" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?= htmlspecialchars($_GET['responsavel'] ?? '') ?>">
  <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
  <input type="number" step="0.01" name="valor" placeholder="Valor" value="<?= htmlspecialchars($_GET['valor'] ?? '') ?>">
  <input type="date" name="data_baixa" value="<?= htmlspecialchars($_GET['data_baixa'] ?? '') ?>">
  <button type="submit">Buscar</button>
  <a href="contas_receber_baixadas.php" class="clear-filters">Limpar Filtros</a>
</form>

<!-- Botões de Exportação -->
<div class="export-buttons">
  <a href="../pages/exportar.php?tipo=pdf&status=baixada"><button type="button">Exportar PDF</button></a>
  <a href="../pages/exportar.php?tipo=excel&status=baixada"><button type="button">Exportar Excel</button></a>
  <a href="../pages/exportar.php?tipo=csv&status=baixada"><button type="button">Exportar CSV</button></a>
</div>

<?php
$where = ["cr.status = 'baixada'"];
if (!empty($_GET['responsavel'])) $where[] = "cr.responsavel LIKE '%" . $conn->real_escape_string($_GET['responsavel']) . "%'";
if (!empty($_GET['numero'])) $where[] = "cr.numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['valor'])) $where[] = "cr.valor = " . floatval($_GET['valor']);
if (!empty($_GET['data_baixa'])) $where[] = "cr.data_baixa = '" . $conn->real_escape_string($_GET['data_baixa']) . "'";

$sql = "SELECT cr.*, u.nome AS usuario 
        FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por = u.id
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY cr.data_baixa DESC";

$result = $conn->query($sql);

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
} else {
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<thead><tr>
            <th>Responsável</th>
            <th>Número</th>
            <th>Valor</th>
            <th>Forma</th>
            <th>Data de Baixa</th>
            <th>Baixado por</th>
          </tr></thead>";
        echo "<tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td data-label='Responsável'>" . htmlspecialchars($row['responsavel']) . "</td>";
            echo "<td data-label='Número'>" . htmlspecialchars($row['numero']) . "</td>";
            echo "<td data-label='Valor'>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
            echo "<td data-label='Forma'>" . htmlspecialchars($row['forma_pagamento']) . "</td>";
            echo "<td data-label='Data de Baixa'>" . date('d/m/Y', strtotime($row['data_baixa'])) . "</td>";
            echo "<td data-label='Baixado por'>" . htmlspecialchars($row['usuario']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p style='text-align:center; margin-top:30px;'>Nenhuma conta baixada encontrada.</p>";
    }
}
?>

<p><a href="contas_receber.php">← Voltar para Contas a Receber</a></p>

</body>
</html>

<?php include('../includes/footer.php'); ?>
