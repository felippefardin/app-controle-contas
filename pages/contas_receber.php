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

<h2>Contas a Receber</h2>

<!-- Formulário de busca -->
<form class="search-form" method="GET" action="">
    <input type="text" name="responsavel" placeholder="Responsável" value="<?= htmlspecialchars($_GET['responsavel'] ?? '') ?>">
    <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
    <input type="date" name="data_vencimento" value="<?= htmlspecialchars($_GET['data_vencimento'] ?? '') ?>">
    <button type="submit">Buscar</button>
    <a href="contas_receber.php" class="clear-filters">Limpar</a>
</form>

<!-- Botões exportar e adicionar -->
 <div class="export-buttons">
    <button type="button" class="btn-primary" onclick="document.getElementById('exportar_contas_receber').style.display='block'">Exportar Baixadas</button>
  </div>
<button class="btn-add" onclick="toggleForm()">Adicionar Nova Conta</button>

<!-- Formulário Adicionar -->
<div id="form-container">
    <h3>Nova Conta</h3>
    <form method="POST" action="../actions/add_conta_receber.php">
        <input type="text" name="responsavel" placeholder="Responsável" required>
        <input type="text" name="numero" placeholder="Número" required>
        <input type="text" name="valor" placeholder="Valor" required>
        <input type="date" name="data_vencimento" required>
        <button type="submit" onclick="return confirm('Deseja adicionar esta conta?')">Adicionar</button>
    </form>
</div>

<?php
// Filtros
$where = ["status='pendente'"];
if(!empty($_GET['responsavel'])) $where[] = "responsavel LIKE '%".$conn->real_escape_string($_GET['responsavel'])."%'";
if(!empty($_GET['numero'])) $where[] = "numero LIKE '%".$conn->real_escape_string($_GET['numero'])."%'";
if(!empty($_GET['data_vencimento'])) $where[] = "data_vencimento='".$conn->real_escape_string($_GET['data_vencimento'])."'";

$sql = "SELECT * FROM contas_receber WHERE ".implode(" AND ", $where)." ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

echo "<table>";
echo "<tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr>";
$hoje = date('Y-m-d');

while($row = $result->fetch_assoc()){
    $vencido = ($row['data_vencimento']<$hoje)?'vencido':'';
    echo "<tr class='$vencido'>";
    echo "<td>".htmlspecialchars($row['responsavel'])."</td>";
    echo "<td>".($row['data_vencimento']?date('d/m/Y', strtotime($row['data_vencimento'])):'-')."</td>";
    echo "<td>".htmlspecialchars($row['numero'])."</td>";
    echo "<td>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
    echo "<td>";
    echo "<button class='btn-gerar action-btn' data-id='{$row['id']}' data-responsavel='".htmlspecialchars($row['responsavel'])."' data-email='".htmlspecialchars($row['email']??'')."'>Gerar Cobrança</button> ";
    echo "<button class='btn-editar action-btn' data-id='{$row['id']}' data-responsavel='".htmlspecialchars($row['responsavel'])."' data-numero='".htmlspecialchars($row['numero'])."' data-valor='{$row['valor']}' data-data='{$row['data_vencimento']}'>Editar</button> ";
    echo "<a href='../actions/baixar_conta_receber.php?id={$row['id']}' class='btn-baixar action-btn'>Baixar</a> ";
    if($_SESSION['usuario']['perfil']==='admin'){
        echo "<button class='btn-excluir action-btn' data-id='{$row['id']}'>Excluir</button>";
    }
    echo "</td></tr>";
}
echo "</table>";

?>

<!-- Modais -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h3>Confirmar Exclusão</h3>
    <p>Tem certeza que deseja excluir esta conta?</p>
    <button class="confirm">Sim, excluir</button>
    <button class="cancel">Cancelar</button>
  </div>
</div>

<div id="cobrancaModal" class="modal">
  <div class="modal-content">
    <h3>Gerar Cobrança</h3>
    <form method="POST" action="../actions/enviar_cobranca.php">
      <input type="hidden" name="conta_id" id="conta_id">
      <input type="email" name="email" id="email" placeholder="E-mail do responsável" required>
      <input type="text" name="pix" placeholder="Chave PIX (opcional)">
      <button type="submit" class="confirm">Enviar Cobrança</button>
      <button type="button" class="cancel" onclick="cobrancaModal.style.display='none'">Cancelar</button>
    </form>
  </div>
</div>

<!-- Modal Editar -->
<div id="editarModal" class="modal">
  <div class="modal-content">
    <h3>Editar Conta</h3>
    <form method="POST" action="../actions/editar_conta_receber.php">
      <input type="hidden" name="id" id="editar_id">
      <input type="text" name="responsavel" id="editar_responsavel" required>
      <input type="text" name="numero" id="editar_numero" required>
      <input type="text" name="valor" id="editar_valor" required>
      <input type="date" name="data_vencimento" id="editar_data" required>
      <button type="submit" class="confirm">Salvar</button>
      <button type="button" class="cancel" onclick="editarModal.style.display='none'">Cancelar</button>
    </form>
  </div>
</div>


<div id="baixarModal" class="modal">
  <div class="modal-content">
    <h3>Baixar Conta</h3>
    <p>Deseja marcar esta conta como paga?</p>
    <form method="POST" action="../actions/baixar_conta_receber.php">
      <input type="hidden" name="id" id="baixar_id">
      <button type="submit" class="confirm">Confirmar</button>
      <button type="button" class="cancel" onclick="baixarModal.style.display='none'">Cancelar</button>
    </form>
  </div>
</div>

<!-- Modal Exportar Baixadas -->
  <div id="exportar_contas_receber" class="modal" aria-hidden="true">
    <div class="modal-content">
      <span class="close" onclick="document.getElementById('exportar_contas_receber').style.display='none'">&times;</span>
      <h2>Exportar Contas</h2>

      <form action="../pages/exportar_contas_receber.php" method="get">
        <label for="tipo">Formato:</label>
        <select name="tipo" id="tipo" required>
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

// Funções modais
const deleteModal=document.getElementById('deleteModal');
const cobrancaModal=document.getElementById('cobrancaModal');
const editarModal=document.getElementById('editarModal');
const baixarModal=document.getElementById('baixarModal');

// Excluir
document.querySelectorAll('.btn-excluir').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const id=btn.dataset.id;
        deleteModal.style.display='flex';
        deleteModal.querySelector('.confirm').onclick=()=>{ window.location.href=`../actions/excluir_conta_receber.php?id=${id}`; }
    });
});

// Gerar cobrança
document.querySelectorAll('.btn-gerar').forEach(btn=>{
    btn.addEventListener('click',()=>{
        cobrancaModal.style.display='flex';
        document.getElementById('conta_id').value=btn.dataset.id;
        document.getElementById('email').value=btn.dataset.email||'';
    });
});

// Editar
document.querySelectorAll('.btn-editar').forEach(btn=>{
    btn.addEventListener('click',()=>{
        editarModal.style.display='flex';
        document.getElementById('editar_id').value = btn.dataset.id;
        document.getElementById('editar_responsavel').value = btn.dataset.responsavel;
        document.getElementById('editar_numero').value = btn.dataset.numero;
        document.getElementById('editar_valor').value = btn.dataset.valor;
        document.getElementById('editar_data').value = btn.dataset.data;
    });
});


    // ESC fecha modais
    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const exportModal = document.getElementById('exportar_contas_receber');
        if (deleteModal.style.display === 'block') closeDeleteModal();
        if (exportModal.style.display === 'block') exportModal.style.display = 'none';
      }
    });


// Fechar modais ao clicar fora
window.addEventListener('click', e=>{
    if(e.target===deleteModal) deleteModal.style.display='none';
    if(e.target===cobrancaModal) cobrancaModal.style.display='none';
    if(e.target===editarModal) editarModal.style.display='none';
    if(e.target===baixarModal) baixarModal.style.display='none';
});
</script>

</body>
</html>

<?php include('../includes/footer.php'); ?>
