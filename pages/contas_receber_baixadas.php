<?php
session_start();
include('../includes/header.php');
include('../database.php');
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Receber Baixadas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
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
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1f1f1f;
      margin-top: 20px;
      border-radius: 8px;
      overflow: hidden;
    }

    th, td {
      padding: 12px 10px;
      border-bottom: 1px solid #333;
      text-align: left;
    }

    th {
      background-color: #222;
      color: #00bfff;
    }

    tr:nth-child(even) {
      background-color: #2a2a2a;
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
      margin-top: 30px;
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
        padding: 10px;
        border-radius: 8px;
      }

      td {
        position: relative;
        padding-left: 50%;
      }

      td::before {
        position: absolute;
        left: 10px;
        top: 12px;
        font-weight: bold;
        color: #aaa;
      }

      td:nth-of-type(1)::before { content: "Responsável"; }
      td:nth-of-type(2)::before { content: "Número"; }
      td:nth-of-type(3)::before { content: "Valor"; }
      td:nth-of-type(4)::before { content: "Forma"; }
      td:nth-of-type(5)::before { content: "Data de Baixa"; }
      td:nth-of-type(6)::before { content: "Baixado por"; }
    }
  </style>
</head>
<body>

<h2>Contas a Receber Baixadas</h2>

<?php
$sql = "SELECT cr.*, u.nome AS usuario FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por = u.id
        WHERE cr.status = 'baixada'
        ORDER BY cr.data_baixa DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Responsável</th><th>Número</th><th>Valor</th><th>Forma</th><th>Data de Baixa</th><th>Baixado por</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['responsavel']) . "</td>";
        echo "<td>" . htmlspecialchars($row['numero']) . "</td>";
        echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($row['forma_pagamento']) . "</td>";
        echo "<td>" . htmlspecialchars($row['data_baixa']) . "</td>";
        echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhuma conta baixada encontrada.</p>";
}
?>

<p><a href="contas_receber.php">Voltar para Contas a Receber</a></p>

</body>
</html>

<?php include('../includes/footer.php'); ?>
