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

$where = ["status = 'baixada'"];

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

    /* Botão export */
    .btn-export {
      background-color: #28a745; /* Verde */
      color: white;
      border: none;
      padding: 10px 14px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      transition: background-color 0.3s ease;
    }

    .btn-export:hover {
      background-color: #218838;
    }

    /* Modal de exclusão */
    .modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0; top: 0;
      width: 100%; height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.7);
    }

    .modal-content {
      background-color: #222;
      margin: 10% auto;
      padding: 30px;
      border-radius: 8px;
      max-width: 400px;
      color: #eee;
      position: relative;
      box-shadow: 0 0 15px rgba(0, 191, 255, 0.6);
    }

    .close {
      color: #aaa;
      position: absolute;
      top: 12px;
      right: 20px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover {
      color: #00bfff;
    }

    .btn-delete {
      background-color: transparent;
      border: none;
      color: #cc3333;
      cursor: pointer;
      font-weight: bold;
      text-decoration: underline;
      font-size: 1em;
      padding: 0;
    }

    .btn-delete:hover {
      color: #a02a2a;
      text-decoration: none;
    }
  </style>
</head>
<body>
   <div id="successMsg" style="
    display:none;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%);
    background-color:#27ae60;
    color:white;
    padding:20px 35px;
    border-radius:8px;
    z-index:10000;
    box-shadow:0 2px 10px rgba(0,0,0,0.3);
    font-weight:bold;
    font-size:18px;
    opacity:0;
    transition:opacity 0.5s ease;
    ">
    ✅ Conta excluída com sucesso!
</div>

<h2>Contas a Pagar - Baixadas</h2>


<form class="search-form" method="GET" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?php echo htmlspecialchars($_GET['responsavel'] ?? ''); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($_GET['numero'] ?? ''); ?>">
  <input type="date" name="data_vencimento" placeholder="Data Vencimento" value="<?php echo htmlspecialchars($_GET['data_vencimento'] ?? ''); ?>">
  <button type="submit">Buscar</button>
  <a href="contas_pagar_baixadas.php" class="clear-filters">Limpar</a>
</form>

<div class="export-buttons">
  <button type="button" class="btn-export" onclick="document.getElementById('exportModal').style.display='block'">Exportar</button>
</div>

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
        echo "<td data-label='Data de Baixa'>" . ($row['data_baixa'] && strtotime($row['data_baixa']) !== false ? date('d/m/Y', strtotime($row['data_baixa'])) : '-') . "</td>";
        echo "<td data-label='Usuário'>" . htmlspecialchars($row['usuario_baixou'] ?? '-') . "</td>";
        echo "<td data-label='Ações'>
                <button class='btn-delete' onclick='openDeleteModal({$row['id']})'>Excluir</button>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>

<div id="exportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.7); z-index:9999;">
  <div style="background:#222; padding:30px; max-width:400px; margin:100px auto; border-radius:10px; color:white; position:relative;">
    <h3 style="margin-top:0;">Exportar Dados</h3>
    <form method="GET" action="../pages/exportar.php">
      <input type="hidden" name="status" value="baixada">
      <label for="tipo">Tipo de Exportação:</label>
      <select name="tipo" id="tipo" required style="width:100%; padding:8px; margin-bottom:15px;">
        <option value="pdf">PDF</option>
        <option value="excel">Excel</option>
        <option value="csv">CSV</option>
      </select>

      <label for="data_inicio">Data Início:</label>
      <input type="date" name="data_inicio" style="width:100%; padding:8px; margin-bottom:10px;">

      <label for="data_fim">Data Fim:</label>
      <input type="date" name="data_fim" style="width:100%; padding:8px; margin-bottom:20px;">

      <button type="submit" style="padding:10px 20px; background-color:#27ae60; color:white; border:none; border-radius:5px;">Exportar</button>
      <button type="button" onclick="document.getElementById('exportModal').style.display='none'" style="padding:10px 20px; background-color:#cc3333; color:white; border:none; border-radius:5px; margin-left:10px;">Cancelar</button>
    </form>
  </div>
</div>

<div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDeleteModal()">&times;</span>
    <h3>Confirmar Exclusão</h3>
    <p>Tem certeza que deseja excluir esta conta baixada?</p>
    <div style="margin-top:20px; text-align: right;">
      <button onclick="closeDeleteModal()" style="background-color:#cc3333; color:white; border:none; padding:10px 20px; border-radius:5px; margin-right:10px; cursor:pointer;">Cancelar</button>
      <a id="confirmDeleteBtn" href="#" style="background-color:#27ae60; color:white; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:bold;">Confirmar</a>
    </div>
  </div>
</div>

<script>
  let deleteId = null;

  function openDeleteModal(id) {
    deleteId = id;
    document.getElementById('confirmDeleteBtn').href = `../actions/excluir_conta_pagar.php?id=${id}`;
    document.getElementById('deleteModal').style.display = 'block';
  }

  function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteId = null;
  }

  // Fecha o modal se clicar fora da caixa
  window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
      closeDeleteModal();
    }
  };
  window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('excluido') === '1') {
        const msg = document.getElementById('successMsg');
        msg.style.display = 'block';
        setTimeout(() => {
            msg.style.opacity = '1';
        }, 50);

        // Some depois de 3 segundos
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.style.display = 'none', 500);
        }, 3000);

        // Remove o parâmetro da URL
        urlParams.delete('excluido');
        window.history.replaceState({}, '', `${location.pathname}?${urlParams}`);
    }
}
</script>

</body>
<?php include('../includes/footer.php'); ?>