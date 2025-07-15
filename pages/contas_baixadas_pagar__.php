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
  <style>
    body {
      background-color: #121212;
      color: #f0f0f0;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 40px;
    }

    h2 {
      text-align: center;
      color: #00bfff;
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1f1f1f;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 30px;
    }

    th, td {
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #333;
      color: #fff;
      font-weight: bold;
    }

    tr:nth-child(even) {
      background-color: #2c2c2c;
    }

    tr:nth-child(odd) {
      background-color: #262626;
    }

    tr:hover {
      background-color: #444;
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
    }

    @media (max-width: 600px) {
      body {
        padding: 20px;
      }

      table, thead, tbody, th, td, tr {
        display: block;
      }

      th {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }

      td {
        position: relative;
        padding-left: 50%;
        margin-bottom: 10px;
        border: none;
      }

      td::before {
        position: absolute;
        left: 10px;
        top: 12px;
        white-space: nowrap;
        color: #aaa;
        font-weight: bold;
      }

      td:nth-of-type(1)::before { content: "Fornecedor"; }
      td:nth-of-type(2)::before { content: "Vencimento"; }
      td:nth-of-type(3)::before { content: "Número"; }
      td:nth-of-type(4)::before { content: "Valor"; }
      td:nth-of-type(5)::before { content: "Forma de Pagamento"; }
      td:nth-of-type(6)::before { content: "Data de Baixa"; }
      td:nth-of-type(7)::before { content: "Usuário"; }
    }
  </style>
</head>
<body>

<h2>Contas a Pagar - Baixadas</h2>

<?php
$sql = "SELECT c.*, u.nome AS usuario_baixou 
        FROM contas_pagar_baixadas c 
        LEFT JOIN usuarios u ON c.usuario_id = u.id 
        ORDER BY data_baixa DESC";
$result = $conn->query($sql);

echo "<table>";
echo "<tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['fornecedor']) . "</td>";
    echo "<td>" . htmlspecialchars($row['data_vencimento']) . "</td>";
    echo "<td>" . htmlspecialchars($row['numero']) . "</td>";
    echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
    echo "<td>" . htmlspecialchars($row['forma_pagamento']) . "</td>";
    echo "<td>" . htmlspecialchars($row['data_baixa']) . "</td>";
    echo "<td>" . htmlspecialchars($row['usuario_baixou']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

<p><a href="home.php">← Voltar para a Home</a></p>

</body>
</html>

<?php include('../includes/footer.php'); ?>
