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

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1f1f1f;
      border-radius: 8px;
      overflow: hidden;
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

    p {
      text-align: center;
      margin-top: 20px;
    }

    /* Responsivo para celular */
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
        background-color: #1f1f1f;
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

<h2>Contas a Pagar - Baixadas</h2>

<?php
$sql = "SELECT c.*, u.nome AS usuario_baixou 
        FROM contas_pagar c 
        LEFT JOIN usuarios u ON c.baixado_por = u.id 
        WHERE c.status = 'baixada' 
        ORDER BY c.data_baixa DESC";

$result = $conn->query($sql);

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
} else {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th></tr></thead>";
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
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>

<p><a href="home.php">← Voltar para a Home</a></p>

</body>
</html>

<?php include('../includes/footer.php'); ?>
