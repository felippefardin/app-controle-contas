<?php
session_start();
include('../includes/header.php');
include('../database.php');
if (!isset($_SESSION['usuario'])) {
  header('Location: login.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Receber</title>
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
      text-align: center;
      color: #00bfff;
    }

    .export-buttons {
      text-align: center;
      margin: 20px 0;
    }

    .export-buttons a button {
      margin: 5px;
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      background-color: #00bfff;
      color: white;
      cursor: pointer;
    }

    .export-buttons a button:hover {
      background-color: #0095cc;
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

    tr[style*="background-color:#ff9999"] {
      background-color: #662222 !important;
    }

    a {
      color: #00bfff;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
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

    form input, form button {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border-radius: 5px;
      border: none;
      font-size: 16px;
    }

    form button {
      background-color: #00bfff;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    form button:hover {
      background-color: #0095cc;
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
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid #333;
        border-radius: 8px;
      }

      td {
        position: relative;
        padding-left: 50%;
        margin-bottom: 10px;
      }

      td::before {
        position: absolute;
        top: 10px;
        left: 10px;
        font-weight: bold;
        color: #999;
      }

      td:nth-of-type(1)::before { content: "Responsável"; }
      td:nth-of-type(2)::before { content: "Vencimento"; }
      td:nth-of-type(3)::before { content: "Número"; }
      td:nth-of-type(4)::before { content: "Valor"; }
      td:nth-of-type(5)::before { content: "Ações"; }
    }
  </style>
</head>
<body>

<h2>Contas a Receber</h2>



<div class="export-buttons">
  <a href="exportar.php?tipo=pdf&status=receber"><button>Exportar PDF</button></a>
  <a href="exportar.php?tipo=csv&status=receber"><button>Exportar CSV</button></a>
  <a href="exportar.php?tipo=excel&status=receber"><button>Exportar Excel</button></a>
</div>

<?php
$sql = "SELECT * FROM contas_receber WHERE status = 'pendente' ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

echo "<table>";
echo "<tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr>";
$hoje = date('Y-m-d');
while ($row = $result->fetch_assoc()) {
    $style = ($row['data_vencimento'] < $hoje) ? "style='background-color:#ff9999'" : "";
    echo "<tr $style>";
    echo "<td>" . htmlspecialchars($row['responsavel']) . "</td>";
    echo "<td>" . htmlspecialchars($row['data_vencimento']) . "</td>";
    echo "<td>" . htmlspecialchars($row['numero']) . "</td>";
    echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
    echo "<td>
        <a href='../actions/baixar_conta_receber.php?id={$row['id']}'>Baixar</a> |
        <a href='../actions/editar_conta_receber.php?id={$row['id']}'>Editar</a>";
    if ($_SESSION['usuario']['perfil'] === 'admin') {
        echo " | <a href='../actions/enviar_codigo_exclusao.php?id={$row['id']}' onclick=\"return confirm('Deseja excluir esta conta? Um código será enviado para o e-mail do administrador.')\">Excluir</a>";
    }
    echo "</td></tr>";
}
echo "</table>";
?>

<h3>Nova Conta a Receber</h3>
<form action="../actions/add_conta_receber.php" method="POST">
  <input type="text" name="responsavel" placeholder="Responsável" required>
  <input type="date" name="data_vencimento" required>
  <input type="text" name="numero" placeholder="Número" required>
  <input type="number" step="0.01" name="valor" placeholder="Valor" required>
  <button type="submit" onclick="return confirm('Deseja adicionar esta conta?')">Adicionar</button>
</form>

<p><a href="contas_receber_baixadas.php">Ver contas baixadas</a></p>
<p><a href="home.php">← Voltar para a Home</a></p>

</body>
</html>

<?php include('../includes/footer.php'); ?>
