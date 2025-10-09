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
// Assumindo que o id_criador está na sessão. Use 0 ou null como padrão.
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Monta filtros SQL
$where = ["status='pendente'"];
if ($perfil !== 'admin') {
    // Se id_criador for maior que 0, o usuário é secundário.
    // O ID principal é o id_criador. Caso contrário, é o próprio ID do usuário.
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;

    // Subconsulta para obter todos os IDs de usuários associados à conta principal
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";

    // A cláusula WHERE agora inclui o ID do usuário principal e todos os seus usuários secundários
    $where[] = "(usuario_id = {$mainUserId} OR usuario_id IN ({$subUsersQuery}))";
}
if(!empty($_GET['responsavel'])) $where[] = "responsavel LIKE '%".$conn->real_escape_string($_GET['responsavel'])."%'";
if(!empty($_GET['numero'])) $where[] = "numero LIKE '%".$conn->real_escape_string($_GET['numero'])."%'";
if(!empty($_GET['data_vencimento'])) $where[] = "data_vencimento='".$conn->real_escape_string($_GET['data_vencimento'])."'";

$sql = "SELECT * FROM contas_receber WHERE ".implode(" AND ", $where)." ORDER BY data_vencimento ASC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Contas a Receber</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
    form.search-form button, form.search-form a.clear-filters { color:white; border:none; padding:10px 22px; font-weight:bold; border-radius:5px; cursor:pointer; transition:background-color 0.3s; min-width:120px; text-align:center; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; }
    form.search-form button { background:#27ae60; font-size:16px; }
    form.search-form button:hover { background:#1e874b; }
    form.search-form a.clear-filters { background:#cc3333; }
    form.search-form a.clear-filters:hover { background:#a02a2a; }

    /* Botões */
    .action-buttons-group { display:flex; justify-content:center; gap:12px; margin:20px 0; flex-wrap:wrap; }
    .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .btn-add { background-color:#00bfff; color:white; }
    .btn-add:hover { background-color:#0099cc; }
    .btn-export { background-color: #28a745; color: white; }
    .btn-export:hover { background-color: #218838; }
    
    /* Tabela */
    table { width:100%; border-collapse:collapse; background:#1f1f1f; border-radius:8px; overflow:hidden; margin-top:10px; }
    th, td { padding:12px 10px; border-bottom:1px solid #333; text-align:left; }
    th { background:#222; color:#00bfff; }
    tr:nth-child(even) { background:#2a2a2a; }
    tr:hover { background:#333; }
    tr.vencido { background:#662222 !important; }
    .btn-action { margin: 2px; }

    /* --- ESTILOS DO NOVO MODAL --- */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 800px; position: relative; }
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content .close-btn:hover { color: #00bfff; }
    .modal-content form { display: flex; flex-wrap: wrap; gap: 15px; }
    .modal-content form input { flex: 1 1 200px; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
    .modal-content form button { flex: 1 1 100%; background-color: #00bfff; color: white; border: none; padding: 12px 25px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .modal-content form button:hover { background-color: #0099cc; }
    
    /* Responsivo */
    @media(max-width:768px){
      td { padding-left: 50%; text-align: right; }
      td::before { content: attr(data-label); position: absolute; left: 10px; font-weight: bold; color: #999; text-align: left; }
      .modal-content form { flex-direction: column; }
    }
    
</style>
</head>
<body>

<h2>Contas a Receber</h2>

<form class="search-form" method="GET" action="">
    <input type="text" name="responsavel" placeholder="Responsável" value="<?= htmlspecialchars($_GET['responsavel'] ?? '') ?>">
    <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
    <input type="date" name="data_vencimento" value="<?= htmlspecialchars($_GET['data_vencimento'] ?? '') ?>">
    <button type="submit"><i class="fa fa-search"></i> Buscar</button>
    <a href="contas_receber.php" class="clear-filters">Limpar</a>
</form>

<div class="action-buttons-group">
    <button class="btn btn-add" onclick="toggleForm()">➕ Adicionar Nova Conta</button>
    <button type="button" class="btn btn-export" onclick="document.getElementById('exportar_contas_receber').style.display='flex'">Exportar</button>
</div>

<div id="addContaModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="toggleForm()">&times;</span>
    <h3>Nova Conta a Receber</h3>
    <form method="POST" action="../actions/add_conta_receber.php">
        <input type="text" name="responsavel" placeholder="Responsável" required>
        <input type="text" name="numero" placeholder="Número" required>
        <input type="text" name="valor" placeholder="Valor (ex: 123,45)" required oninput="this.value=this.value.replace(/[^0-9.,]/g,'')">
        <input type="date" name="data_vencimento" required>
        <button type="submit">Adicionar Conta</button>
    </form>
  </div>
</div>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    $hoje = date('Y-m-d');
    while($row = $result->fetch_assoc()){
        $vencido = ($row['data_vencimento'] < $hoje) ? 'vencido' : '';
        echo "<tr class='$vencido'>";
        echo "<td data-label='Responsável'>".htmlspecialchars($row['responsavel'])."</td>";
        echo "<td data-label='Vencimento'>".($row['data_vencimento'] ? date('d/m/Y', strtotime($row['data_vencimento'])) : '-')."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
        echo "<td data-label='Ações'>";
        // Seus botões de ação aqui
        echo "<a href='../actions/baixar_conta_receber.php?id={$row['id']}' class='btn-action btn-baixar'>Baixar</a>";
        echo "</td></tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta a receber pendente encontrada.</p>";
}
?>

<div id="exportar_contas_receber" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('exportar_contas_receber').style.display='none'">&times;</span>
        <h3>Exportar Relatório de Contas a Receber</h3>
        <form action="../actions/exportar_contas_receber.php" method="POST" target="_blank" onsubmit="return validateExportForm(this);">
            <div class="form-group">
                <label for="status">Tipo de Relatório:</label>
                <select name="status" id="exportStatusReceber" onchange="updateDateLabel('Receber')">
                    <option value="pendente">Contas Pendentes</option>
                    <option value="baixada">Contas Baixadas</option>
                </select>
            </div>
            <div class="form-group">
                <label for="data_inicio" id="dateLabelInicioReceber">Filtrar de (Data de Vencimento):</label>
                <input type="date" name="data_inicio" required>
            </div>
            <div class="form-group">
                <label for="data_fim" id="dateLabelFimReceber">Até (Data de Vencimento):</label>
                <input type="date" name="data_fim" required>
            </div>
            <div class="form-group">
                <label for="formato">Formato do Arquivo:</label>
                <select name="formato">
                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel (XLSX)</option>
                    <option value="csv">CSV</option>
                </select>
            </div>
            <button type="submit">Gerar Relatório</button>
        </form>
    </div>
</div>

<script>
function toggleForm(){ 
    const modal = document.getElementById('addContaModal'); 
    modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex'; 
}

// Fechar modais ao clicar fora
window.addEventListener('click', e => {
    const addModal = document.getElementById('addContaModal');
    const exportModal = document.getElementById('exportar_contas_receber');
    if(e.target === addModal) addModal.style.display = 'none';
    if(e.target === exportModal) exportModal.style.display = 'none';
    // Adicione aqui a lógica para outros modais se necessário
});
</script>

</body>
</html>

<?php include('../includes/footer.php'); ?>