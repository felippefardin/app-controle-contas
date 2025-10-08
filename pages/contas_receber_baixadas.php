<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];

// Monta os filtros da consulta SQL
$where = ["status='baixada'"];

if ($perfil !== 'admin') {
    $where[] = "c.usuario_id = '$usuarioId'";
}

if (!empty($_GET['responsavel'])) $where[] = "responsavel LIKE '%" . $conn->real_escape_string($_GET['responsavel']) . "%'";
if (!empty($_GET['numero'])) $where[] = "numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['data_vencimento'])) $where[] = "data_vencimento='" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

$sql = "SELECT c.*, u.nome AS usuario_baixou
        FROM contas_receber c
        LEFT JOIN usuarios u ON c.baixado_por = u.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.data_baixa DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Contas a Receber - Baixadas</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />

<style>
/* RESET & BASE */
* { box-sizing: border-box; }
body { background-color:#121212; color:#eee; font-family:Arial,sans-serif; margin:0; padding:20px; }
h2,h3 { text-align:center; color:#00bfff; }
a { color:#00bfff; text-decoration:none; font-weight:bold; }
a:hover { text-decoration:underline; }

/* Formulário de Busca */
form.search-form { display:flex; flex-wrap:wrap; justify-content:center; gap:10px; margin-bottom:25px; max-width:900px; margin:auto; }
form.search-form input[type="text"], form.search-form input[type="date"] { padding:10px; font-size:16px; border-radius:5px; border:1px solid #444; background:#333; color:#eee; min-width:180px; }
form.search-form input::placeholder { color:#aaa; }
form.search-form button { background:#27ae60; color:white; border:none; padding:10px 22px; font-size:16px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; min-width:100px; }
form.search-form button:hover { background:#1e874b; }
form.search-form a.clear-filters { background:#cc3333; color:white; padding:10px 18px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; min-width:100px; display:flex; justify-content:center; align-items:center; }
form.search-form a.clear-filters:hover { background:#a02a2a; }

/* Botões exportar e adicionar */
.export-buttons { display:flex; justify-content:center; gap:12px; margin:20px 0; flex-wrap:wrap; }
.btn-export, .btn-add { background-color:#27ae60; color:white; border:none; padding:10px 22px; font-size:16px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; }
.btn-add { background-color:#00bfff; display:block; margin:0 auto 25px auto; }
.btn-export:hover { background-color:#218838; }
.btn-add:hover { background-color:#0099cc; }

/* Tabela */
table { width:100%; border-collapse:collapse; background:#1f1f1f; border-radius:8px; overflow:hidden; margin-top:10px; }
th, td { padding:12px 10px; border-bottom:1px solid #333; text-align:left; }
th { background:#222; color:#00bfff; }
tr:nth-child(even) { background:#2a2a2a; }
tr:hover { background:#333; }
tr.vencido { background:#662222 !important; }
.action-btn {
    margin-right: 10px;
    margin-bottom: 4px;
}

/* Botões ações */
.btn-gerar { background:#007bff; color:white; border:none; padding:5px 12px; font-size:14px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; }
.btn-gerar:hover { background:#0056b3; }
.btn-editar { background:#ffa500; color:white; border:none; padding:5px 12px; font-size:14px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; }
.btn-editar:hover { background:#cc8400; }
.btn-excluir { background:#cc3333; color:white; border:none; padding:5px 12px; font-size:14px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; }
.btn-excluir:hover { background:#a02a2a; }
.btn-baixar { background:#28a745; color:white; border:none; padding:5px 12px; font-size:14px; font-weight:bold; border-radius:5px; cursor:pointer; transition:0.3s; }
.btn-baixar:hover { background:#218838; }
.btn-primary {
    background-color: #27ae60;
    color: #fff;
    border: none;
    padding: 10px 22px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color .3s ease;
    display: inline-flex
;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    min-width: 120px;
    text-align: center;
}

/* Modal */
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; }
.modal-content { background:#222; padding:30px; max-width:400px; margin:100px auto; border-radius:10px; color:white; text-align:center; position:relative; }
.modal-content h3 { margin-top:0; margin-bottom:20px; color:#00bfff; }
.modal-content input, .modal-content select { width:100%; padding:8px; margin-bottom:10px; border-radius:5px; border:1px solid #444; background:#333; color:white; }
.modal-content button { padding:10px 20px; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin:5px; }
.modal-content button.confirm { background:#27ae60; color:white; }
.modal-content button.cancel { background:#cc3333; color:white; }
.modal-content button:hover.confirm { background:#1e874b; }
.modal-content button:hover.cancel { background:#a02a2a; }

/* Formulário adicionar */
#form-container { max-width:800px; margin:0 auto 30px auto; background:#1f1f1f; padding:20px; border-radius:8px; display:none; flex-direction:column; gap:12px; }
#form-container form input { flex:1 1 180px; padding:12px; font-size:16px; border-radius:5px; border:1px solid #444; background:#333; color:#eee; box-sizing:border-box; }
#form-container form button { background:#00bfff; color:white; border:none; padding:12px 25px; font-size:16px; font-weight:bold; border-radius:5px; cursor:pointer; flex-shrink:0; transition:0.3s; }
#form-container form button:hover { background:#0099cc; }

/* Responsivo */
@media(max-width:768px){
  form.search-form, #form-container form { flex-direction:column; align-items:stretch; }
  form.search-form button, form.search-form a.clear-filters, #form-container form button { width:100%; min-width:auto; }
  table, thead, tbody, th, td, tr { display:block; }
  th { display:none; }
  tr { margin-bottom:15px; border:1px solid #333; border-radius:8px; padding:10px; }
  td { position:relative; padding-left:50%; margin-bottom:10px; }
  td::before { position:absolute; top:10px; left:10px; font-weight:bold; color:#999; }
  td:nth-of-type(1)::before{ content:"Responsável"; }
  td:nth-of-type(2)::before{ content:"Vencimento"; }
  td:nth-of-type(3)::before{ content:"Número"; }
  td:nth-of-type(4)::before{ content:"Valor"; }
  td:nth-of-type(5)::before{ content:"Ações"; }
}
</style>
</head>
<body>

<h2>Contas a Receber - Baixadas</h2>

<form class="search-form" method="GET" action="">
    <input type="text" name="responsavel" placeholder="Responsável" value="<?= htmlspecialchars($_GET['responsavel'] ?? '') ?>">
    <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
    <input type="date" name="data_vencimento" value="<?= htmlspecialchars($_GET['data_vencimento'] ?? '') ?>">
    <button type="submit">Buscar</button>
    <a href="contas_receber_baixadas.php" class="clear-filters">Limpar</a>
</form>

<div class="export-buttons">
    <button type="button" class="btn-primary" onclick="document.getElementById('exportar_contas_receber').style.display='block'">Exportar Baixadas</button>
  </div>

<?php
// Filtros
$where = ["status='baixada'"];

if ($perfil !== 'admin') {
    $where[] = "c.usuario_id = '$usuarioId'";
}

if(!empty($_GET['responsavel'])) $where[] = "responsavel LIKE '%".$conn->real_escape_string($_GET['responsavel'])."%'";
if(!empty($_GET['numero'])) $where[] = "numero LIKE '%".$conn->real_escape_string($_GET['numero'])."%'";
if(!empty($_GET['data_vencimento'])) $where[] = "data_vencimento='".$conn->real_escape_string($_GET['data_vencimento'])."'";

$sql = "SELECT c.*, u.nome AS usuario_baixou FROM contas_receber c LEFT JOIN usuarios u ON c.baixado_por = u.id WHERE ".implode(" AND ", $where)." ORDER BY c.data_baixa DESC";
$result = $conn->query($sql);

echo "<table>";
echo "<thead><tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th></tr></thead>";
$hoje = date('Y-m-d');

while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td data-label='Responsável'>".htmlspecialchars($row['responsavel'])."</td>";
    echo "<td data-label='Vencimento'>".($row['data_vencimento']?date('d/m/Y', strtotime($row['data_vencimento'])):'-')."</td>";
    echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
    echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
    echo "<td data-label='Juros'>R$ ".number_format((float)($row['juros'] ?? 0),2,',','.')."</td>";
    echo "<td data-label='Forma de Pagamento'>".htmlspecialchars($row['forma_pagamento'] ?? '-')."</td>";
    echo "<td data-label='Data de Baixa'>".date('d/m/Y', strtotime($row['data_baixa']))."</td>";
    echo "<td data-label='Usuário'>".htmlspecialchars($row['usuario_baixou'] ?? '-')."</td>";
    echo "</tr>";
}
echo "</table>";

?>

<div id="exportar_contas_receber" class="modal" aria-hidden="true">
    <div class="modal-content">
      <span class="close" onclick="document.getElementById('exportar_contas_receber').style.display='none'">&times;</span>
      <h2>Exportar Contas</h2>

      <form action="../pages/exportar_contas_receber.php" method="get">
        <label for="tipo">Formato:</label>
        <select name="tipo" id="tipo" required style="width:100%; padding:8px; margin-bottom:15px;">
          <option value="pdf">PDF</option>
          <option value="csv">CSV</option>
          <option value="excel">Excel</option>
        </select>

        <input type="hidden" name="contas_receber" value="pendente">

        <label for="data_inicio">Data Início:</label>
        <input type="date" name="data_inicio" id="data_inicio" required />

        <label for="data_fim">Data Fim:</label>
        <input type="date" name="data_fim" id="data_fim" required />

        <div class="modal-actions">
          <button type="button" class="btn-danger" onclick="document.getElementById('exportar_contas_receber').style.display='none'">Cancelar</button>
          <button type="submit" class="btn-primary">Exportar</button>
        </div>
      </form>
    </div>
  </div>

<script>
function toggleForm(){
    const f=document.getElementById('form-container');
    f.style.display=(f.style.display==='flex')?'none':'flex';
}

// Fechar modais ao clicar fora
window.addEventListener('click', e=>{
    const exportModal = document.getElementById('exportar_contas_receber');
    if(e.target===exportModal) exportModal.style.display='none';
});
</script>

</body>
<?php include('../includes/footer.php'); ?>