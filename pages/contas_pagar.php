<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include('../database.php');
include('../includes/header.php');
include('../includes/footer.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Pagar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; }

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

    tr:nth-child(even) { background-color: #262626; }
    tr:hover { background-color: #333; }

    .search-bar {
      max-width: 1000px;
      margin: 20px auto;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    .search-bar .input-group {
      position: relative;
    }

    .search-bar .input-group i {
      position: absolute;
      top: 12px;
      left: 10px;
      color: #aaa;
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
    }

    .search-bar button:hover {
      background-color: #0099cc;
    }

    .export-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .export-buttons a button {
      background-color: #444;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    .export-buttons a button:hover {
      background-color: #222;
    }

    form.add-form {
      margin-top: 30px;
      background-color: #1f1f1f;
      padding: 20px;
      border-radius: 8px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    form.add-form input, select, button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
    }

    form.add-form input, select {
      background-color: #333;
      color: #eee;
    }

    form.add-form input::placeholder {
      color: #aaa;
    }

    form.add-form button {
      background-color: #00bfff;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    form.add-form button:hover {
      background-color: #0099cc;
    }

    a {
      color: #00bfff;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover { text-decoration: underline; }

    p {
      text-align: center;
      margin-top: 20px;
    }

    @media (max-width: 768px) {
      .search-bar {
        flex-direction: column;
        align-items: center;
      }

      table, thead, tbody, th, td, tr {
        display: block;
      }

      th { display: none; }

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

    /* Botão exporta */
    .export-buttons {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin: 20px 0;
  flex-wrap: wrap;
}

.export-buttons a button {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.export-buttons .btn-pdf {
  background-color: #27ae60;
  color: #fff;
}

.export-buttons .btn-excel {
  background-color: #27ae60;
  color: #fff;
}

.export-buttons .btn-csv {
  background-color: #27ae60;
  color: #fff;
}

.export-buttons a button:hover {
  opacity: 0.9;
}

.btn-acao {
  width: 160px;
  padding: 10px 18px;
  border: none;
  border-radius: 5px;
  font-weight: bold;
  font-size: 14px;
  background-color: #00bfff;
  color: white;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-acao:hover {
  background-color: #0099cc;
}

        

  </style>
</head>
<body>

<h2>Contas a Pagar</h2>

<!-- Busca unificada -->
<form method="GET" class="search-bar">
  <div class="input-group">
    <i class="fa fa-user"></i>
    <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?= htmlspecialchars($_GET['fornecedor'] ?? '') ?>">
  </div>
  <div class="input-group">
    <i class="fa fa-file"></i>
    <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
  </div>
  <div class="input-group">
    <i class="fa fa-money-bill"></i>
    <input type="number" step="0.01" name="valor" placeholder="Valor" value="<?= htmlspecialchars($_GET['valor'] ?? '') ?>">
  </div>
  <div class="input-group">
    <i class="fa fa-calendar"></i>
    <input type="date" name="data_vencimento" value="<?= htmlspecialchars($_GET['data_vencimento'] ?? '') ?>">
  </div>

  <button type="submit" class="btn-acao">Buscar</button>
  <a href="contas_pagar.php"><button type="button" class="btn-acao">Limpar Filtros</button></a>
</form>


<!-- Botões de Exportação - Estilo Moderno -->
<div class="export-buttons">
  <a href="../pages/exportar.php?tipo=pdf">
    <button type="button" class="btn-pdf">Exportar PDF</button>
  </a>
  <a href="../pages/exportar.php?tipo=excel">
    <button type="button" class="btn-excel">Exportar Excel</button>
  </a>
  <a href="../pages/exportar.php?tipo=csv">
    <button type="button" class="btn-csv">Exportar CSV</button>
  </a>
</div>




<div style="text-align: center; margin-top: 20px;">
  <button onclick="toggleForm()" style="background-color:#00bfff; color:#fff; padding:10px 20px; border:none; border-radius:5px; font-size:16px; cursor:pointer;">
    <i class="fa fa-plus"></i> Adicionar Nova Conta
  </button>
</div>

<!-- Formulário escondido inicialmente -->
<div id="form-container" style="display: none;">
  <h3>Nova Conta</h3>
  <form class="add-form" action="../actions/add_conta_pagar.php" method="POST">
    <input type="text" name="fornecedor" placeholder="Fornecedor" required>
    <input type="text" name="numero" placeholder="Número" required>
    <input type="text" name="valor" placeholder="Valor" required oninput="this.value = this.value.replace(/[^0-9.,]/g, '')">
    <input type="date" name="data_vencimento" required>
    <button type="submit" onclick="return confirm('Deseja adicionar esta conta?')">Adicionar</button>
  </form>
</div>

<?php
$where = [];
if (!empty($_GET['fornecedor'])) $where[] = "fornecedor LIKE '%" . $conn->real_escape_string($_GET['fornecedor']) . "%'";
if (!empty($_GET['numero'])) $where[] = "numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['valor'])) $where[] = "valor = " . floatval($_GET['valor']);
if (!empty($_GET['data_vencimento'])) $where[] = "data_vencimento = '" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

$where[] = "status = 'pendente'";
$sql = "SELECT * FROM contas_pagar WHERE " . implode(" AND ", $where) . " ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

echo "<table>";
echo "<tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr>";

$hoje = date('Y-m-d');
while ($row = $result->fetch_assoc()) {
    $vencido = ($row['data_vencimento'] < $hoje) ? "style='background-color:#802020'" : "";
    $data_formatada = date('d/m/Y', strtotime($row['data_vencimento']));
    echo "<tr $vencido>";
    echo "<td data-label='Fornecedor'>" . htmlspecialchars($row['fornecedor']) . "</td>";
    echo "<td data-label='Vencimento'>" . $data_formatada . "</td>";
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


<!-- Script para alternar visibilidade -->
<script>
  function toggleForm() {
    const form = document.getElementById('form-container');
    form.style.display = (form.style.display === 'none') ? 'block' : 'none';
  }
</script>

<p><a href="home.php">Voltar</a></p>
<p><a href="contas_pagar_baixadas.php">Ver contas baixadas</a></p>

</body>
</html>
