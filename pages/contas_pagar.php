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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
    form.search-form button, form.search-form a.clear-filters {
      color: white; border: none; padding: 10px 22px; font-weight: bold;
      border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;
      min-width: 120px; text-align: center; display: inline-flex;
      align-items: center; justify-content: center; text-decoration: none;
    }
    form.search-form button { background-color: #27ae60; font-size: 16px; }
    form.search-form button:hover { background-color: #1e874b; }
    form.search-form a.clear-filters { background-color: #cc3333; }
    form.search-form a.clear-filters:hover { background-color: #a02a2a; }

    /* Botões */
    .action-buttons-group { display: flex; justify-content: center; gap: 12px; margin: 20px 0; flex-wrap: wrap; }
    .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .btn-add { background-color: #00bfff; color: white; }
    .btn-add:hover { background-color: #0099cc; }
    .btn-export { background-color: #28a745; color: white; padding: 10px 14px; }
    .btn-export:hover { background-color: #218838; }

    /* Tabela */
    table { width: 100%; border-collapse: collapse; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }
    tr:hover { background-color: #333; }
    tr.vencido { background-color: #662222 !important; }
    
    .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 14px; font-weight: bold; text-decoration: none; color: white; cursor: pointer; transition: background-color 0.3s ease; margin: 2px; }
    .btn-baixar { background-color: #27ae60; }
    .btn-baixar:hover { background-color: #1e874b; }
    .btn-editar { background-color: #00bfff; }
    .btn-editar:hover { background-color: #0099cc; }
    .btn-excluir { background-color: #cc3333; }
    .btn-excluir:hover { background-color: #a02a2a; }
    
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
    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr { display: block; }
      th { display: none; }
      tr { margin-bottom: 15px; border: 1px solid #333; border-radius: 8px; padding: 10px; }
      td { position: relative; padding-left: 50%; text-align: right; }
      td::before { content: attr(data-label); position: absolute; left: 10px; font-weight: bold; color: #999; text-align: left; }
      .modal-content form { flex-direction: column; }
    }
  </style>
</head>
<body>

<h2>Contas a Pagar</h2>

<form class="search-form" method="GET" action="">
  <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?php echo htmlspecialchars($_GET['fornecedor'] ?? ''); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($_GET['numero'] ?? ''); ?>">
  <input type="date" name="data_vencimento" placeholder="Data Vencimento" value="<?php echo htmlspecialchars($_GET['data_vencimento'] ?? ''); ?>">
  <button type="submit"><i class="fa fa-search"></i> Buscar</button>
  <a href="contas_pagar.php" class="clear-filters">Limpar</a>
</form>

<div class="action-buttons-group">
  <button class="btn btn-add" onclick="toggleForm()">➕ Adicionar Nova Conta</button>
  <button type="button" class="btn btn-export" onclick="document.getElementById('exportModal').style.display='flex'">Exportar</button>
</div>

<div id="addContaModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="toggleForm()">&times;</span>
    <h3>Nova Conta a Pagar</h3>
    <form action="../actions/add_conta_pagar.php" method="POST">
      <input type="text" name="fornecedor" placeholder="Fornecedor" required>
      <input type="text" name="numero" placeholder="Número" required>
      <input type="text" name="valor" placeholder="Valor (ex: 123,45)" required oninput="this.value=this.value.replace(/[^0-9.,]/g,'')">
      <input type="date" name="data_vencimento" required>
      <button type="submit">Adicionar Conta</button>
    </form>
  </div>
</div>

<?php
if ($result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    $hoje = date('Y-m-d');
    while($row = $result->fetch_assoc()){
        $vencidoClass = ($row['data_vencimento'] < $hoje) ? "vencido" : "";
        echo "<tr class='$vencidoClass'>";
        echo "<td data-label='Fornecedor'>".htmlspecialchars($row['fornecedor'])."</td>";
        echo "<td data-label='Vencimento'>".date('d/m/Y',strtotime($row['data_vencimento']))."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        echo "<td data-label='Valor'>R$ ".number_format($row['valor'],2,',','.')."</td>";
        echo "<td data-label='Ações'>
                <a href='../actions/baixar_conta.php?id={$row['id']}' class='btn-action btn-baixar'><i class='fa-solid fa-check'></i> Baixar</a>
                <a href='../actions/editar_conta_pagar.php?id={$row['id']}' class='btn-action btn-editar'><i class='fa-solid fa-pen'></i> Editar</a>
                <a href='#' onclick='openDeleteModal({$row['id']}); return false;' class='btn-action btn-excluir'><i class='fa-solid fa-trash'></i> Excluir</a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta pendente encontrada.</p>";
}
?>

<div id="exportModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('exportModal').style.display='none'">&times;</span>
        <h3>Exportar Contas a Pagar</h3>
        <form action="../actions/exportar_contas_pagar.php" method="POST" target="_blank">
            <input type="hidden" name="status" value="pendente">

            <div class="form-group">
                <label for="data_inicio">De (Vencimento):</label>
                <input type="date" name="data_inicio" required>
            </div>
            <div class="form-group">
                <label for="data_fim">Até (Vencimento):</label>
                <input type="date" name="data_fim" required>
            </div>
            <div class="form-group">
                <label for="formato">Formato:</label>
                <select name="formato">
                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel (XLSX)</option>
                    <option value="csv">CSV</option>
                </select>
            </div>
            <button type="submit">Exportar</button>
        </form>
    </div>
</div>
<div id="deleteModal" class="modal">
    <div class="modal-content">
        </div>
</div>

<script>
  function toggleForm() {
    const modal = document.getElementById('addContaModal');
    modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
  }

  // Lógica para fechar modais
  window.onclick = function(event) {
    const addModal = document.getElementById('addContaModal');
    const exportModal = document.getElementById('exportModal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target == addModal) {
        addModal.style.display = 'none';
    }
    if (event.target == exportModal) {
        exportModal.style.display = 'none';
    }
    if (event.target == deleteModal) {
        deleteModal.style.display = 'none';
    }
  };

  // Funções para o modal de exclusão
  function openDeleteModal(id) { /* ... */ }
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>