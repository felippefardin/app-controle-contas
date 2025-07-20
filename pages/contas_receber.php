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
  <meta charset="UTF-8" />
  <title>Contas a Receber</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
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
    h2, h3 {
      text-align: center;
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
    p {
      text-align: center;
      margin-top: 20px;
    }

    /* Formulário de Busca */
    form.search-form {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin-bottom: 25px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    form.search-form input[type="text"],
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
    form.search-form button {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 10px 22px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      min-width: 100px;
    }
    form.search-form button:hover {
      background-color: #1e874b;
    }
    form.search-form a.clear-filters {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background-color: #cc3333;
      color: white;
      padding: 10px 18px;
      font-weight: bold;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      min-width: 100px;
      transition: background-color 0.3s ease;
    }
    form.search-form a.clear-filters:hover {
      background-color: #a02a2a;
    }

    /* Botões exportar e adicionar */
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
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .export-buttons a button:hover {
      background-color: #1e874b;
    }
    .btn-add {
      background-color: #00bfff;
      color: white;
      border: none;
      padding: 10px 22px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      display: block;
      margin: 0 auto 25px auto;
      transition: background-color 0.3s ease;
    }
    .btn-add:hover {
      background-color: #0099cc;
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
      background-color: #2a2a2a;
    }
    tr:hover {
      background-color: #333;
    }
    /* Linhas vencidas */
    tr.vencido {
      background-color: #662222 !important;
    }

    /* Formulário de Adição */
    #form-container {
      max-width: 800px;
      margin: 0 auto 30px auto;
      background-color: #1f1f1f;
      padding: 20px;
      border-radius: 8px;
      display: none;
      flex-direction: column;
      gap: 12px;
    }
    #form-container h3 {
      margin-top: 0;
      color: #00bfff;
      text-align: center;
    }
    #form-container form {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
    }
    #form-container form input[type="text"],
    #form-container form input[type="date"] {
      flex: 1 1 180px;
      padding: 12px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #444;
      background-color: #333;
      color: #eee;
      box-sizing: border-box;
    }
    #form-container form input::placeholder {
      color: #aaa;
    }
    #form-container form button {
      background-color: #00bfff;
      color: white;
      border: none;
      padding: 12px 25px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      flex-shrink: 0;
      transition: background-color 0.3s ease;
    }
    #form-container form button:hover {
      background-color: #0099cc;
    }

    /* Responsivo */
    @media (max-width: 768px) {
      form.search-form,
      #form-container form {
        flex-direction: column;
        align-items: stretch;
      }
      form.search-form button,
      form.search-form a.clear-filters,
      #form-container form button {
        min-width: auto;
        width: 100%;
      }
      table, thead, tbody, th, td, tr {
        display: block;
      }
      th {
        display: none;
      }
      tr {
        margin-bottom: 15px;
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

  <script>
    function toggleForm() {
      const form = document.getElementById('form-container');
      form.style.display = form.style.display === 'none' || form.style.display === '' ? 'flex' : 'none';
    }
  </script>
</head>
<body>

<h2>Contas a Receber</h2>

<!-- Formulário de Busca -->
<form class="search-form" method="GET" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?php echo htmlspecialchars($_GET['responsavel'] ?? ''); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($_GET['numero'] ?? ''); ?>">
  <input type="date" name="data_vencimento" placeholder="Data Vencimento" value="<?php echo htmlspecialchars($_GET['data_vencimento'] ?? ''); ?>">
  <button type="submit">Buscar</button>
  <a href="contas_receber.php" class="clear-filters">Limpar</a>
</form>


<!-- Botões Exportar -->
<!-- <div class="export-buttons">
  <a href="exportar.php?tipo=pdf&status=receber"><button type="button">Exportar PDF</button></a>
  <a href="exportar.php?tipo=csv&status=receber"><button type="button">Exportar CSV</button></a>
  <a href="exportar.php?tipo=excel&status=receber"><button type="button">Exportar Excel</button></a>
</div> -->

<!-- Botão Exportar -->
<div class="export-buttons">
  <button type="button" class="btn-export-green" onclick="document.getElementById('export_receber').style.display='block'">Exportar</button>
</div>

<!-- Botão Adicionar Conta -->
<button class="btn-add" onclick="toggleForm()">
  <i class="fa fa-plus"></i> Adicionar Nova Conta
</button>

<!-- Formulário de Adição -->
<div id="form-container">
  <h3>Nova Conta</h3>
  <form action="../actions/add_conta_receber.php" method="POST">
    <input type="text" name="responsavel" placeholder="Responsável" required>
    <input type="text" name="numero" placeholder="Número" required>
    <input type="text" name="valor" placeholder="Valor" required oninput="this.value = this.value.replace(/[^0-9.,]/g, '')">
    <input type="date" name="data_vencimento" required>
    <button type="submit" onclick="return confirm('Deseja adicionar esta conta?')">Adicionar</button>
  </form>
</div>

<?php
// Monta os filtros na consulta SQL com segurança
$where = ["status = 'pendente'"];

if (!empty($_GET['responsavel'])) {
    $responsavel = $conn->real_escape_string($_GET['responsavel']);
    $where[] = "responsavel LIKE '%$responsavel%'";
}
if (!empty($_GET['numero'])) {
    $numero = $conn->real_escape_string($_GET['numero']);
    $where[] = "numero LIKE '%$numero%'";
}
if (!empty($_GET['data_vencimento'])) {
    $data_vencimento = $conn->real_escape_string($_GET['data_vencimento']);
    $where[] = "data_vencimento = '$data_vencimento'";
}

$sql = "SELECT * FROM contas_receber WHERE " . implode(" AND ", $where) . " ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

echo "<table>";
echo "<tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr>";
$hoje = date('Y-m-d');
while ($row = $result->fetch_assoc()) {
    // Valores protegidos para evitar null
    $responsavel = $row['responsavel'] ?? '';
    $data_vencimento = $row['data_vencimento'] ?? '';
    $numero = $row['numero'] ?? '';
    $valor = $row['valor'] ?? 0;

    $vencidoClass = ($data_vencimento !== '' && $data_vencimento < $hoje) ? "vencido" : "";

    echo "<tr class='{$vencidoClass}'>";
    echo "<td>" . htmlspecialchars($responsavel) . "</td>";

    if ($data_vencimento !== '') {
        echo "<td>" . date('d/m/Y', strtotime($data_vencimento)) . "</td>";
    } else {
        echo "<td> - </td>";
    }

    echo "<td>" . htmlspecialchars($numero) . "</td>";
    echo "<td>R$ " . number_format((float)$valor, 2, ',', '.') . "</td>";

    echo "<td>";
    echo "<a href='../actions/baixar_conta_receber.php?id=" . htmlspecialchars($row['id']) . "'>Baixar</a> | ";
    echo "<a href='../actions/editar_conta_receber.php?id=" . htmlspecialchars($row['id']) . "'>Editar</a>";

    if ($_SESSION['usuario']['perfil'] === 'admin') {
        echo " | <a href='../actions/enviar_codigo_exclusao.php?id=" . htmlspecialchars($row['id']) . "' onclick=\"return confirm('Deseja excluir esta conta? Um código será enviado para o e-mail do administrador.')\">Excluir</a>";
    }

    echo "</td>";
    echo "</tr>";
} 
?>

<p><a href="contas_receber_baixadas.php">Ver contas baixadas</a></p>
<!-- <p><a href="home.php">← Voltar para a Home</a></p> -->

<script>
  // Toggle formulário adicionar conta
  function toggleForm() {
    const form = document.getElementById('form-container');
    form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'flex' : 'none';
  }


   let tipoExportacaoSelecionado = '';

  function abrirModal(tipo) {
    tipoExportacaoSelecionado = tipo;
    document.getElementById('modal-exportar').style.display = 'flex';
  }

  function fecharModal() {
    document.getElementById('modal-exportar').style.display = 'none';
    document.getElementById('dataExportacao').value = '';
  }

  window.onclick = function(event) {
    var modal = document.getElementById('exportModal');
    if (event.target == modal) {
      modal.style.display = "none";
    }
  } 
</script>

<!-- Modal Exportar -->
<div id="export_receber" class="modal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('export_receber').style.display='none'">&times;</span>
    <h2>Exportar</h2>
    <form action="../pages/export_receber.php" method="get">
      <label for="tipo">Tipo:</label>
      <select name="tipo" id="tipo" required>
        <option value="pdf">PDF</option>
        <option value="csv">CSV</option>
        <option value="excel">Excel</option>
      </select>

      <label for="status">Status:</label>
      <select name="status" id="status">
        <option value="">Todos</option>
        <!-- <option value="pendente">Pendente</option>
        <option value="recebido">Recebido</option> -->
      </select>

      <label for="data_inicio">Data Início:</label>
      <input type="date" name="data_inicio" id="data_inicio" />

      <label for="data_fim">Data Fim:</label>
      <input type="date" name="data_fim" id="data_fim" />

      <br><br>
      <button type="submit" class="btn-export-green">Exportar</button>
    </form>
  </div>
</div>



</body>
</html>

<?php include('../includes/footer.php'); ?>
