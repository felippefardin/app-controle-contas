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
    /* Botão export */

 .btn-export-green {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 14px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }

  .btn-export-green:hover {
    background-color: #218838;
  }

  .modal {
    display: none;
    position: fixed;
    z-index: 999;
    padding-top: 100px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
  }

  .modal-content {
    background-color: #fefefe;
    margin: auto;
    padding: 30px;
    border: 1px solid #888;
    width: 100%;
    max-width: 500px;
    border-radius: 8px;
  }

  .modal-content label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
  }

  .modal-content input,
  .modal-content select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .close {
    float: right;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
  }

  .close:hover {
    color: black;
  }
  </style>
</head>
<body>

<h2>Contas a Receber Baixadas</h2>

<form method="GET" class="search-form" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?= htmlspecialchars($_GET['responsavel'] ?? '') ?>">
  <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
  <input type="number" step="0.01" name="valor" placeholder="Valor" value="<?= htmlspecialchars($_GET['valor'] ?? '') ?>">
  <input type="date" name="data_baixa" value="<?= htmlspecialchars($_GET['data_baixa'] ?? '') ?>">
  <button type="submit">Buscar</button>
  <a href="contas_receber_baixadas.php" class="clear-filters">Limpar Filtros</a>
</form>

<!-- <div class="export-buttons">
  <a href="../pages/exportar.php?tipo=pdf&status=baixada"><button type="button">Exportar PDF</button></a>
  <a href="../pages/exportar.php?tipo=excel&status=baixada"><button type="button">Exportar Excel</button></a>
  <a href="../pages/exportar.php?tipo=csv&status=baixada"><button type="button">Exportar CSV</button></a>
</div> -->

<!-- Botão para abrir o modal -->
<div class="export-buttons">
  <button type="button" class="btn-export-green" onclick="document.getElementById('export_receber_baixadas').style.display='block'">Exportar Baixadas</button>
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
            <th>Ações</th>
          </tr></thead>";
        echo "<tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td data-label='Responsável'>" . htmlspecialchars($row['responsavel'] ?? '') . "</td>";
            echo "<td data-label='Número'>" . htmlspecialchars($row['numero'] ?? '') . "</td>";
            echo "<td data-label='Valor'>R$ " . number_format(floatval($row['valor'] ?? 0), 2, ',', '.') . "</td>";
            echo "<td data-label='Forma'>" . htmlspecialchars($row['forma_pagamento'] ?? '') . "</td>";

            $data_baixa = $row['data_baixa'] ?? null;
            echo "<td data-label='Data de Baixa'>" . ($data_baixa ? date('d/m/Y', strtotime($data_baixa)) : '-') . "</td>";

            echo "<td data-label='Baixado por'>" . htmlspecialchars($row['usuario'] ?? '') . "</td>";

            echo "<td data-label='Ações'>";
            echo "<a href='../actions/excluir_conta_receber.php?id=" . htmlspecialchars($row['id'] ?? '') . "' ";
            echo "onclick=\"return confirm('Deseja excluir esta conta baixada?')\">Excluir</a>";
            echo "</td>";
            echo "</tr>";
        } // ← CHAVE FECHANDO O while
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>Nenhuma conta baixada encontrada.</p>";
    }
}
?>
<!-- <p><a href="contas_receber.php">← Voltar para Contas a Receber</a></p> -->

<!-- Modal Exportar Baixadas -->
<div id="export_receber_baixadas" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('export_receber_baixadas').style.display='none'">&times;</span>
    <h2>Exportar Contas Baixadas</h2>

    <form action="../pages/export_receber.php" method="get">
      <!-- tipo do formato -->
      <label for="tipo">Formato:</label>
      <select name="tipo" id="tipo" required>
        <option value="pdf">PDF</option>
        <option value="csv">CSV</option>
        <option value="excel">Excel</option>
      </select>

      <!-- status fixo como "baixada" -->
      <input type="hidden" name="status" value="baixada">

      <!-- data início -->
      <label for="data_inicio">Data Início:</label>
      <input type="date" name="data_inicio" id="data_inicio" required />

      <!-- data fim -->
      <label for="data_fim">Data Fim:</label>
      <input type="date" name="data_fim" id="data_fim" required />

      <br><br>
      <button type="submit" class="btn-export-green">Exportar</button>
    </form>
  </div>
</div>

</body>
</html>

<?php include('../includes/footer.php'); ?>
