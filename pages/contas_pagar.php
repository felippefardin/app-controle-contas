<?php
require_once '../includes/session_init.php';

// Bloco para lidar com a pesquisa de fornecedores via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'search_fornecedor') {
    include '../database.php';
    if (!isset($_SESSION['usuario']['id'])) {
        echo json_encode([]);
        exit;
    }

    $usuarioId = $_SESSION['usuario']['id'];
    $term = $_GET['term'] ?? '';

    $stmt = $conn->prepare("SELECT id, nome FROM pessoas_fornecedores WHERE id_usuario = ? AND nome LIKE ? ORDER BY nome ASC LIMIT 10");
    $searchTerm = "%{$term}%";
    $stmt->bind_param("is", $usuarioId, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $fornecedores = [];
    while ($row = $result->fetch_assoc()) {
        $fornecedores[] = $row;
    }
    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode($fornecedores);
    exit;
}


// O restante do seu código PHP original
$servername = "localhost";
$username = "root";
$password = "";
$database = "app_controle_contas";
include('../includes/header.php');

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Lógica de filtro de usuário
$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// NOVA SEÇÃO: Buscar categorias de despesa
$stmt_categorias = $conn->prepare("SELECT id, nome FROM categorias WHERE id_usuario = ? AND tipo = 'despesa' ORDER BY nome ASC");
$stmt_categorias->bind_param("i", $usuarioId);
$stmt_categorias->execute();
$result_categorias = $stmt_categorias->get_result();
$categorias_despesa = [];
while ($row_cat = $result_categorias->fetch_assoc()) {
    $categorias_despesa[] = $row_cat;
}
$stmt_categorias->close();


// Monta filtros SQL - **AJUSTADO COM ALIAS 'cp'**
$where = ["cp.status='pendente'"];
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";
    $where[] = "(cp.usuario_id = {$mainUserId} OR cp.usuario_id IN ({$subUsersQuery}))";
}
if(!empty($_GET['fornecedor'])) $where[] = "cp.fornecedor LIKE '%".$conn->real_escape_string($_GET['fornecedor'])."%'";
if(!empty($_GET['numero'])) $where[] = "cp.numero LIKE '%".$conn->real_escape_string($_GET['numero'])."%'";
if(!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $where[] = "cp.data_vencimento BETWEEN '".$conn->real_escape_string($_GET['data_inicio'])."' AND '".$conn->real_escape_string($_GET['data_fim'])."'";
} elseif(!empty($_GET['data_inicio'])) $where[] = "cp.data_vencimento >= '".$conn->real_escape_string($_GET['data_inicio'])."'";
elseif(!empty($_GET['data_fim'])) $where[] = "cp.data_vencimento <= '".$conn->real_escape_string($_GET['data_fim'])."'";

// SQL ATUALIZADA COM JOIN para buscar o nome da categoria
$sql = "SELECT cp.*, c.nome as nome_categoria 
        FROM contas_pagar AS cp
        LEFT JOIN categorias AS c ON cp.id_categoria = c.id
        WHERE ".implode(" AND ",$where)." 
        ORDER BY cp.data_vencimento ASC";
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

    /* Estilos para o autocompletar */
    .autocomplete-container {
        position: relative;
        width: 100%;
    }
    .autocomplete-items {
        position: absolute;
        border: 1px solid #444;
        border-top: none;
        z-index: 99;
        top: 100%;
        left: 0;
        right: 0;
        background-color: #333;
        max-height: 150px;
        overflow-y: auto;
    }
    .autocomplete-items div {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #444;
        color: #eee;
    }
    .autocomplete-items div:hover {
        background-color: #555;
    }

    /* MENSAGENS DE SUCESSO/ERRO */
    .success-message {
      background-color: #27ae60;
      color: white; padding: 15px; margin-bottom: 20px;
      border-radius: 5px; text-align: center;
      position: relative; font-weight: bold;
    }
    .close-msg-btn {
      position: absolute; top: 50%; right: 15px;
      transform: translateY(-50%); font-size: 22px;
      line-height: 1; cursor: pointer; transition: color 0.2s;
    }
    .close-msg-btn:hover { color: #ddd; }
    
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
    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222;  }
    td[data-label='Ações'] { text-align: center; }
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
    .btn-repetir { background-color: #f39c12; }
    .btn-repetir:hover { background-color: #d35400; }
    
    /* MODAL */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 800px; position: relative; }
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content .close-btn:hover { color: #00bfff; }
    .modal-content form { display: flex; flex-direction: column; gap: 15px; }
    .modal-content form input, .modal-content form select { width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
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

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
    unset($_SESSION['success_message']);
}
?>

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
        <div class="autocomplete-container">
            <input type="text" id="pesquisar_fornecedor" name="fornecedor_nome" placeholder="Pesquisar fornecedor..." autocomplete="off" required>
            <div id="fornecedor_autocomplete_list" class="autocomplete-items"></div>
        </div>
        <input type="hidden" name="fornecedor_id" id="fornecedor_id_hidden">
        <input type="text" name="numero" placeholder="Número" required>
        <input type="text" name="valor" placeholder="Valor (ex: 123,45)" required oninput="this.value=this.value.replace(/[^0-9.,]/g,'')">
        <input type="date" name="data_vencimento" required>
        
        <select name="id_categoria" required>
            <option value="">-- Selecione uma Categoria --</option>
            <?php foreach ($categorias_despesa as $categoria): ?>
                <option value="<?= $categoria['id'] ?>">
                    <?= htmlspecialchars($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div style="display: flex; align-items: center; width: 100%;">
            <input type="checkbox" id="enviar_email" name="enviar_email" value="S" checked>
            <label for="enviar_email" style="margin-left: 5px;">Enviar email de lembrete</label>
        </div>
        <button type="submit">Adicionar Conta</button>
    </form>
  </div>
</div>

<?php
if ($result->num_rows > 0) {
    echo "<table>";
    // ATUALIZADO: Adicionada coluna Categoria
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Categoria</th><th>Valor</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    $hoje = date('Y-m-d');
    while($row = $result->fetch_assoc()){
        $vencidoClass = ($row['data_vencimento'] < $hoje) ? "vencido" : "";
        echo "<tr class='$vencidoClass'>";
        echo "<td data-label='Fornecedor'>".htmlspecialchars($row['fornecedor'])."</td>";
        echo "<td data-label='Vencimento'>".date('d/m/Y',strtotime($row['data_vencimento']))."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        // ATUALIZADO: Exibindo o nome da categoria
        echo "<td data-label='Categoria'>".htmlspecialchars($row['nome_categoria'] ?? 'N/A')."</td>";
        echo "<td data-label='Valor'>R$ ".number_format($row['valor'],2,',','.')."</td>";
       echo "<td data-label='Ações'>
        <a href='../actions/baixar_conta.php?id={$row['id']}' class='btn-action btn-baixar'><i class='fa-solid fa-check'></i> Baixar</a>
        <a href='editar_conta_pagar.php?id={$row['id']}' class='btn-action btn-editar'><i class='fa-solid fa-pen'></i> Editar</a>
        <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($row['fornecedor']))."'); return false;\" class='btn-action btn-excluir'><i class='fa-solid fa-trash'></i> Excluir</a>
         <a href='#' onclick=\"openRepetirModal({$row['id']}, '".htmlspecialchars(addslashes($row['fornecedor']))."'); return false;\" class='btn-action btn-repetir'><i class='fa-solid fa-clone'></i> Repetir</a>
      </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta pendente encontrada.</p>";
}
?>

<div id="exportModal" class="modal"></div>
<div id="deleteModal" class="modal"><div class="modal-content"></div></div>
<div id="repetirModal" class="modal"></div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
  $(document).ready(function() {
      $("#pesquisar_fornecedor").on("keyup", function() {
          var term = $(this).val();
          if (term.length < 2) {
              $("#fornecedor_autocomplete_list").empty();
              $("#fornecedor_id_hidden").val('');
              return;
          }

          $.ajax({
              url: 'contas_pagar.php',
              type: 'GET',
              data: { action: 'search_fornecedor', term: term },
              dataType: 'json',
              success: function(data) {
                  var items = "";
                  $.each(data, function(index, item) {
                      items += `<div data-id="${item.id}">${item.nome}</div>`;
                  });
                  $("#fornecedor_autocomplete_list").html(items);
              }
          });
      });

      $(document).on("click", "#fornecedor_autocomplete_list div", function() {
          var id = $(this).data("id");
          var name = $(this).text();
          $("#pesquisar_fornecedor").val(name);
          $("#fornecedor_id_hidden").val(id);
          $("#fornecedor_autocomplete_list").empty();
      });

      $(document).on("click", function(e) {
          if (!$(e.target).closest('.autocomplete-container').length) {
              $(".autocomplete-items").empty();
          }
      });
  });

  function toggleForm() {
    const modal = document.getElementById('addContaModal');
    modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
  }

  function openDeleteModal(id, fornecedor) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');

    modalContent.innerHTML = `
      <h3>Confirmar Exclusão</h3>
      <p>Tem certeza de que deseja excluir a seguinte conta a pagar?</p>
      <p><strong>Fornecedor:</strong> ${fornecedor}</p>
      <div class="modal-actions" style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
        <a href="../actions/excluir_conta_pagar.php?id=${id}" class="btn btn-excluir">Sim, Excluir</a>
        <button class="btn" onclick="document.getElementById('deleteModal').style.display='none'">Cancelar</button>
      </div>
    `;
    
    modal.style.display = 'flex';
  }

  function openRepetirModal(id, fornecedor) {
    document.getElementById('modalRepetirContaId').value = id;
    document.getElementById('modalRepetirFornecedor').innerText = fornecedor;
    document.getElementById('repetirModal').style.display = 'flex';
  }

  window.onclick = function(event) {
    const addModal = document.getElementById('addContaModal');
    const exportModal = document.getElementById('exportModal');
    const deleteModal = document.getElementById('deleteModal');
    const repetirModal = document.getElementById('repetirModal');
    if (event.target == addModal) {
        addModal.style.display = 'none';
    }
    if (event.target == exportModal) {
        exportModal.style.display = 'none';
    }
    if (event.target == deleteModal) {
        deleteModal.style.display = 'none';
    }
    if (event.target == repetirModal) {
        repetirModal.style.display = 'none';
    }
  };
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>