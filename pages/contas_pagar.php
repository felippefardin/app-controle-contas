<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include('../database.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Pagar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }

    h2, h3 {
      color: #00bfff;
      text-align: center;
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

    form {
      margin-top: 30px;
      background-color: #1f1f1f;
      padding: 20px;
      border-radius: 8px;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }

    input, select, button {
      display: block;
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
    }

    input, select {
      background-color: #333;
      color: #eee;
    }

    button {
      background-color: #00bfff;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    button:hover {
      background-color: #0099cc;
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

<h2>Contas a Pagar</h2>

<?php
$sql = "SELECT * FROM contas_pagar WHERE status = 'pendente' ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

echo "<table>";
echo "<tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr>";

$hoje = date('Y-m-d');
while ($row = $result->fetch_assoc()) {
    $vencido = ($row['data_vencimento'] < $hoje) ? "style='background-color:#802020'" : "";
    echo "<tr $vencido>";
    echo "<td data-label='Fornecedor'>" . htmlspecialchars($row['fornecedor']) . "</td>";
    echo "<td data-label='Vencimento'>" . htmlspecialchars($row['data_vencimento']) . "</td>";
    echo "<td data-label='Número'>" . htmlspecialchars($row['numero']) . "</td>";
    echo "<td data-label='Valor'>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
    echo "<td data-label='Ações'>
        <a href='../actions/baixar_conta.php?id={$row['id']}'>Baixar</a> |
        <a href='../actions/editar_conta_pagar.php?id={$row['id']}'>Editar</a> |
        <a href='../actions/excluir_conta_pagar.php?id={$row['id']}' onclick=\"return confirm('Deseja realmente excluir esta conta?')\">Excluir</a>
    </td>";
    echo "</tr>";
}
echo "</table>";
?>

<h3>Nova Conta</h3>
<form action="../actions/add_conta_pagar.php" method="POST">
  <input type="text" name="fornecedor" placeholder="Fornecedor" required>
  <input type="date" name="data_vencimento" required>
  <input type="text" name="numero" placeholder="Número" required>
  <input type="number" step="0.01" name="valor" placeholder="Valor" required>
  <button type="submit" onclick="return confirm('Deseja adicionar esta conta?')">Adicionar</button>
</form>

<p><a href="home.php">Voltar</a></p>
<p><a href="contas_pagar_baixadas.php">Ver contas baixadas</a></p>

</body>
</html>
