<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco
$servername = "localhost";
$username = "root";
$password = "";
$database = "app_controle_contas";
include('../includes/header.php');

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Adiciona a lógica de filtro de usuário
$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];

// Monta filtros SQL
$where = ["status='pendente'"];
if ($perfil !== 'admin') {
    $where[] = "usuario_id = '$usuarioId'";
}
if(!empty($_GET['fornecedor'])) $where[] = "fornecedor LIKE '%".$conn->real_escape_string($_GET['fornecedor'])."%'";
if(!empty($_GET['numero'])) $where[] = "numero LIKE '%".$conn->real_escape_string($_GET['numero'])."%'";
if(!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $where[] = "data_vencimento BETWEEN '".$conn->real_escape_string($_GET['data_inicio'])."' AND '".$conn->real_escape_string($_GET['data_fim'])."'";
} elseif(!empty($_GET['data_inicio'])) $where[] = "data_vencimento >= '".$conn->real_escape_string($_GET['data_inicio'])."'";
elseif(!empty($_GET['data_fim'])) $where[] = "data_vencimento <= '".$conn->real_escape_string($_GET['data_fim'])."'";

$sql = "SELECT * FROM contas_pagar WHERE ".implode(" AND ",$where)." ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Contas a Pagar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <style>
    /* RESET & BASE */
    * { box-sizing: border-box; }
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0; padding: 20px;
    }
    h2, h3 { text-align: center; color: #00bfff; }
    a { color: #00bfff; text-decoration: none; font-weight: bold; }
    a:hover { text-decoration: underline; }
    p { text-align: center; margin-top: 20px; }

    /* Formulário de Busca */
    form.search-form {
      display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;
      margin-bottom: 25px; max-width: 900px; margin-left:auto; margin-right:auto;
    }
    form.search-form input[type="text"],
    form.search-form input[type="date"] {
      padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #444;
      background-color: #333; color: #eee; min-width: 180px;
    }
    form.search-form input::placeholder { color: #aaa; }
    form.search-form button {
      background-color: #27ae60; color: white; border: none;
      padding: 10px 22px; font-size: 16px; font-weight: bold;
      border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;
      min-width: 100px;
    }
    form.search-form button:hover { background-color: #1e874b; }
    form.search-form a.clear-filters {
      display: inline-flex; align-items: center; justify-content: center;
      background-color: #cc3333; color: white; padding: 10px 18px;
      font-weight: bold; border-radius: 5px; text-decoration: none;
      cursor: pointer; min-width: 100px; transition: background-color 0.3s ease;
    }
    form.search-form a.clear-filters:hover { background-color: #a02a2a; }

    /* Botões exportar e adicionar */
    .export-buttons { display: flex; justify-content: center; gap: 12px; margin: 20px 0; flex-wrap: wrap; }
    .export-buttons button { background-color: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background-color 0.3s ease; }
    .export-buttons button:hover { background-color: #1e874b; }
    .btn-add { background-color: #00bfff; color: white; border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; display: block; margin: 0 auto 25px auto; transition: background-color 0.3s ease; }
    .btn-add:hover { background-color: #0099cc; }

    /* Tabela */
    table { width: 100%; border-collapse: collapse; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }
    tr:hover { background-color: #333; }
    tr.vencido { background-color: #662222 !important; }

    /* Formulário de Adição */
    #form-container {
      max-width: 800px; margin: 0 auto 30px auto;
      background-color: #1f1f1f; padding: 20px; border-radius: 8px;
      display: none; flex-direction: column; gap: 12px;
    }
    #form-container h3 { margin-top: 0; color: #00bfff; text-align: center; }
    #form-container form { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
    #form-container form input[type="text"], #form-container form input[type="date"] {
      flex: 1 1 180px; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444;
      background-color: #333; color: #eee; box-sizing: border-box;
    }
    #form-container form input::placeholder { color: #aaa; }
    #form-container form button {
      background-color: #00bfff; color: white; border: none;
      padding: 12px 25px; font-size: 16px; font-weight: bold;
      border-radius: 5px; cursor: pointer; flex-shrink: 0;
      transition: background-color 0.3s ease;
    }
    #form-container form button:hover { background-color: #0099cc; }

    /* Responsivo */
    @media (max-width: 768px) {
      form.search-form, #form-container form { flex-direction: column; align-items: stretch; }
      form.search-form button, form.search-form a.clear-filters, #form-container form button { width: 100%; min-width: auto; }
      table, thead, tbody, th, td, tr { display: block; }
      th { display: none; }
      tr { margin-bottom: 15px; border: 1px solid #333; border-radius: 8px; padding: 10px; }
      td { position: relative; padding-left: 50%; margin-bottom: 10px; }
      td::before { position: absolute; top: 10px; left: 10px; font-weight: bold; color: #999; }
      td:nth-of-type(1)::before { content: "Fornecedor"; }
      td:nth-of-type(2)::before { content: "Vencimento"; }
      td:nth-of-type(3)::before { content: "Número"; }
      td:nth-of-type(4)::before { content: "Valor"; }
      td:nth-of-type(5)::before { content: "Ações"; }
    }

    /* Modal e botão exportação */
    .btn-export { background-color: #28a745; color: white; border: none; padding: 10px 14px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background-color 0.3s ease; }
    .btn-export:hover { background-color: #218838; }
    #deleteModal, #exportModal { display: none; position: fixed; z-index: 9999; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.7); color:white; align-items:center; justify-content:center; }
    #deleteModal .modal-content, #exportModal .modal-content { background-color:#222; padding:30px; max-width:400px; margin:100px auto; border-radius:10px; text-align:center; position:relative; }
    #deleteModal h3, #exportModal h3 { margin-top:0; margin-bottom:20px; color:#00bfff; }
    #deleteModal button.confirm { background-color:#27ae60; color:white; }
    #deleteModal button.confirm:hover { background-color:#1e874b; }
    #deleteModal button.cancel { background-color:#cc3333; color:white; }
    #deleteModal button.cancel:hover { background-color:#a02a2a; }

    /* Mensagem sobreposta */
    #successMsg {
      display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
      background-color:#27ae60; color:white; padding:20px 35px; border-radius:8px;
      z-index:10000; box-shadow:0 2px 10px rgba(0,0,0,0.3); font-weight:bold; font-size:18px;
      opacity:0; transition:opacity 0.5s ease;
    }

    /* Botões editar, baixar e exckuir */

    .btn-action {
  display: inline-flex;
  align-items: center;
  gap: 6px; /* espaço entre ícone e texto */
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: bold;
  text-decoration: none;
  color: white;
  cursor: pointer;
  transition: background-color 0.3s ease;
  margin: 2px;
}

/* Cada tipo com cor diferente */
.btn-baixar { background-color: #27ae60; }
.btn-baixar:hover { background-color: #1e874b; }

.btn-editar { background-color: #00bfff; }
.btn-editar:hover { background-color: #0099cc; }

.btn-excluir { background-color: #cc3333; }
.btn-excluir:hover { background-color: #a02a2a; }
  </style>

  <script>
    function toggleForm() {
      const form = document.getElementById('form-container');
      form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'flex' : 'none';
    }

    let deleteId = null;
    function openDeleteModal(id) { deleteId = id; document.getElementById('deleteModal').style.display='flex'; }
    function closeDeleteModal() { deleteId=null; document.getElementById('deleteModal').style.display='none'; }
    function confirmDelete() { if(deleteId) window.location.href='../actions/excluir_conta_pagar.php?id='+deleteId; }

    window.onclick = function(event){
      const deleteModal = document.getElementById('deleteModal');
      const exportModal = document.getElementById('exportModal');
      if(event.target===deleteModal) closeDeleteModal();
      if(event.target===exportModal) exportModal.style.display='none';
    }

    window.onload = function() {
      const urlParams = new URLSearchParams(window.location.search);
      if(urlParams.get('excluido')==='1'){
        const msg = document.getElementById('successMsg');
        msg.style.display='block';
        setTimeout(()=>msg.style.opacity='1',50);
        setTimeout(()=>{ msg.style.opacity='0'; setTimeout(()=>msg.style.display='none',500); },3000);
        urlParams.delete('excluido');
        window.history.replaceState({}, '', `${location.pathname}?${urlParams}`);
      }
    }
  </script>
</head>
<body>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


<div id="successMsg">✅ Conta excluída com sucesso!</div>

<h2>Contas a Pagar</h2>

<form class="search-form" method="GET" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?php echo htmlspecialchars($_GET['responsavel'] ?? ''); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($_GET['numero'] ?? ''); ?>">
  <input type="date" name="data_vencimento" placeholder="Data Vencimento" value="<?php echo htmlspecialchars($_GET['data_vencimento'] ?? ''); ?>">
  <button type="submit">Buscar</button>
  <a href="contas_receber.php" class="clear-filters">Limpar</a>
</form>

<div class="export-buttons">
  <button type="button" class="btn-export" onclick="document.getElementById('exportModal').style.display='block'">Exportar</button>
</div>

<button class="btn-add" onclick="toggleForm()">➕ Adicionar Nova Conta</button>

<div id="form-container">
  <h3>Nova Conta</h3>
  <form action="../actions/add_conta_pagar.php" method="POST">
    <input type="text" name="fornecedor" placeholder="Fornecedor" required>
    <input type="text" name="numero" placeholder="Número" required>
    <input type="text" name="valor" placeholder="Valor" required oninput="this.value=this.value.replace(/[^0-9.,]/g,'')">
    <input type="date" name="data_vencimento" required>
    <button type="submit" onclick="return confirm('Deseja adicionar esta conta?')">Adicionar</button>
  </form>
</div>

<?php
echo "<table>";
echo "<tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr>";
$hoje = date('Y-m-d');
while($row = $result->fetch_assoc()){
    $vencidoClass = ($row['data_vencimento']<$hoje) ? "vencido" : "";
    echo "<tr class='$vencidoClass'>";
    echo "<td>".htmlspecialchars($row['fornecedor'])."</td>";
    echo "<td>".date('d/m/Y',strtotime($row['data_vencimento']))."</td>";
    echo "<td>".htmlspecialchars($row['numero'])."</td>";
    echo "<td>R$ ".number_format($row['valor'],2,',','.')."</td>";
   echo "<td>
        <a href='../actions/baixar_conta.php?id={$row['id']}' class='btn-action btn-baixar'>
          <i class='fa-solid fa-check'></i> Baixar
        </a>
        <a href='../actions/editar_conta_pagar.php?id={$row['id']}' class='btn-action btn-editar'>
          <i class='fa-solid fa-pen'></i> Editar
        </a>
        <a href='#' onclick='openDeleteModal({$row['id']}); return false;' class='btn-action btn-excluir'>
          <i class='fa-solid fa-trash'></i> Excluir
        </a>
      </td>";
    echo "</tr>";
}
echo "</table>";
?>

<div id="exportModal">
  <div class="modal-content">
    <h3>Exportar Dados</h3>
    <form method="GET" action="../pages/exportar_contas_pagar.php">
      <input type="hidden" name="status" value="pendente">
      <label for="tipo">Tipo de Exportação:</label>
      <select name="tipo" id="tipo" required style="width:100%; padding:8px; margin-bottom:15px;">
        <option value="pdf">PDF</option>
        <option value="excel">Excel</option>
        <option value="csv">CSV</option>
      </select>
      <label for="data_inicio">Data Início:</label>
      <input type="date" name="data_inicio" id="data_inicio" style="width:100%; padding:8px; margin-bottom:10px;" value="<?php echo htmlspecialchars($_GET['data_inicio'] ?? ''); ?>">
      <label for="data_fim">Data Fim:</label>
      <input type="date" name="data_fim" id="data_fim" style="width:100%; padding:8px; margin-bottom:20px;" value="<?php echo htmlspecialchars($_GET['data_fim'] ?? ''); ?>">
      <button type="submit" style="padding:10px 20px; background-color:#27ae60; color:white; border:none; border-radius:5px;">Exportar</button>
      <button type="button" onclick="document.getElementById('exportModal').style.display='none'" style="padding:10px 20px; background-color:#cc3333; color:white; border:none; border-radius:5px; margin-left:10px;">Cancelar</button>
    </form>
  </div>
</div>

<div id="deleteModal">
  <div class="modal-content">
    <h3>Confirmar Exclusão</h3>
    <p>Tem certeza que deseja excluir esta conta?</p>
    <button class="confirm" onclick="confirmDelete()">Sim</button>
    <button class="cancel" onclick="closeDeleteModal()">Não</button>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>