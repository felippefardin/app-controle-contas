<?php
require_once '../includes/session_init.php';

// Bloco para lidar com a pesquisa de responsáveis/clientes via AJAX
if (isset($_GET['action']) && in_array($_GET['action'], ['search_responsavel', 'search_cliente'])) {
    include '../database.php';
    if (!isset($_SESSION['usuario']['id'])) {
        echo json_encode([]);
        exit;
    }

    $usuarioId = $_SESSION['usuario']['id'];
    $term = $_GET['term'] ?? '';

    $stmt = $conn->prepare("SELECT id, nome, email FROM pessoas_fornecedores WHERE id_usuario = ? AND nome LIKE ? ORDER BY nome ASC LIMIT 10");
    $searchTerm = "%{$term}%";
    $stmt->bind_param("is", $usuarioId, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $pessoas = [];
    while ($row = $result->fetch_assoc()) {
        $pessoas[] = $row;
    }
    $stmt->close();

    header('Content-Type: application/json');
    echo json_encode($pessoas);
    exit;
}

include '../database.php';
include('../includes/header.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Buscar contas bancárias
$stmt_bancos = $conn->prepare("SELECT id, nome_banco, chave_pix FROM contas_bancarias WHERE id_usuario = ? ORDER BY nome_banco ASC");
$stmt_bancos->bind_param("i", $usuarioId);
$stmt_bancos->execute();
$result_bancos = $stmt_bancos->get_result();
$bancos = [];
while ($row_banco = $result_bancos->fetch_assoc()) {
    $bancos[] = $row_banco;
}
$stmt_bancos->close();

// Buscar categorias de receita
$stmt_categorias = $conn->prepare("SELECT id, nome FROM categorias WHERE id_usuario = ? AND tipo = 'receita' ORDER BY nome ASC");
$stmt_categorias->bind_param("i", $usuarioId);
$stmt_categorias->execute();
$result_categorias = $stmt_categorias->get_result();
$categorias_receita = [];
while ($row_cat = $result_categorias->fetch_assoc()) {
    $categorias_receita[] = $row_cat;
}
$stmt_categorias->close();

// Monta filtros SQL
$where = ["cr.status='pendente'"];
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";
    $where[] = "(cr.usuario_id = {$mainUserId} OR cr.usuario_id IN ({$subUsersQuery}))";
}
if (!empty($_GET['responsavel'])) {
    $where[] = "cr.responsavel LIKE '%".$conn->real_escape_string($_GET['responsavel'])."%'";
}
if (!empty($_GET['numero'])) {
    $where[] = "cr.numero LIKE '%".$conn->real_escape_string($_GET['numero'])."%'";
}
if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $where[] = "cr.data_vencimento BETWEEN '".$conn->real_escape_string($_GET['data_inicio'])."' AND '".$conn->real_escape_string($_GET['data_fim'])."'";
} elseif (!empty($_GET['data_inicio'])) {
    $where[] = "cr.data_vencimento >= '".$conn->real_escape_string($_GET['data_inicio'])."'";
} elseif (!empty($_GET['data_fim'])) {
    $where[] = "cr.data_vencimento <= '".$conn->real_escape_string($_GET['data_fim'])."'";
}

$sql = "SELECT cr.*, c.nome as nome_categoria 
        FROM contas_receber AS cr
        LEFT JOIN categorias AS c ON cr.id_categoria = c.id
        WHERE ".implode(" AND ", $where)." 
        ORDER BY cr.data_vencimento ASC";
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
    * { box-sizing: border-box; }
    body {
        background-color: #121212;
        color: #eee;
        font-family: Arial, sans-serif;
        margin: 0; padding: 20px;
    }
    h2, h3 { text-align: center; color: #00bfff; margin-bottom: 20px; }
    a { color: #00bfff; text-decoration: none; font-weight: bold; }
    a:hover { text-decoration: underline; }
    p { text-align: center; margin-top: 20px; }

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

    .action-buttons-group { display: flex; justify-content: center; gap: 12px; margin: 20px 0; flex-wrap: wrap; }
    .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .btn-add { background-color: #00bfff; color: white; }
    .btn-add:hover { background-color: #0099cc; }
    .btn-export { background-color: #28a745; color: white; padding: 10px 14px; }
    .btn-export:hover { background-color: #218838; }

    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222;  }
    td[data-label='Ações'] {
      width: 600px;
      text-align: center;
    }
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
    .btn-gerar-cobranca { background-color: #28a745; }
    .btn-gerar-cobranca:hover { background-color: #218838; }
    .btn-repetir { background-color: #f39c12; }
    .btn-repetir:hover { background-color: #d35400; }

    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 800px; position: relative; }
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content .close-btn:hover { color: #00bfff; }
    .modal-content form { display: flex; flex-direction: column; gap: 15px; }
    .modal-content form input, .modal-content form select { width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
    .modal-content form button { width: 100%; background-color: #00bfff; color: white; border: none; padding: 12px 25px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .modal-content form button:hover { background-color: #0099cc; }

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

    @media (max-width: 768px) {
        table, thead, tbody, th, td, tr { display: block; }
        th { display: none; }
        tr { margin-bottom: 15px; border: 1px solid #333; border-radius: 8px; padding: 10px; }
        td { position: relative; padding-left: 50%; text-align: right; }
        td::before { content: attr(data-label); position: absolute; left: 10px; font-weight: bold; color: #999; text-align: left; }
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

<h2>Contas a Receber</h2>

<form class="search-form" method="GET" action="">
    <input type="text" name="responsavel" placeholder="Responsável" value="<?= htmlspecialchars($_GET['responsavel'] ?? '') ?>">
    <input type="text" name="numero" placeholder="Número" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
    <input type="date" name="data_inicio" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
    <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
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
        <div class="autocomplete-container">
            <input type="text" id="pesquisar_responsavel" name="responsavel_nome" placeholder="Pesquisar responsável..." autocomplete="off" required>
            <div id="responsavel_autocomplete_list" class="autocomplete-items"></div>
        </div>
        <input type="hidden" name="responsavel_id" id="responsavel_id_hidden">
        <input type="text" name="numero" placeholder="Número" required>
        <input type="text" name="valor" placeholder="Valor (ex: 123,45)" required oninput="this.value=this.value.replace(/[^0-9.,]/g,'')">
        <input type="date" name="data_vencimento" required>
        
        <select name="id_categoria" required>
            <option value="">-- Selecione uma Categoria --</option>
            <?php foreach ($categorias_receita as $categoria): ?>
                <option value="<?= $categoria['id'] ?>">
                    <?= htmlspecialchars($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Adicionar Conta</button>
    </form>
  </div>
</div>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Responsável</th><th>Vencimento</th><th>Número</th><th>Categoria</th><th>Valor</th><th>Status de Vencimento</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    $hoje = date('Y-m-d');
    while($row = $result->fetch_assoc()){
        $classeVencido = '';
        if ($row['data_vencimento'] < $hoje) {
            $classeVencido = 'vencido';
            $statusData = "Vencido";
        } elseif ($row['data_vencimento'] === $hoje) {
            $statusData = "Hoje";
        } else {
            $statusData = "Futuro";
        }
        echo "<tr class='{$classeVencido}'>";
        echo "<td data-label='Responsável'>".htmlspecialchars($row['responsavel'])."</td>";
        echo "<td data-label='Vencimento'>".($row['data_vencimento'] ? date('d/m/Y', strtotime($row['data_vencimento'])) : '-')."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        echo "<td data-label='Categoria'>".htmlspecialchars($row['nome_categoria'] ?? 'N/A')."</td>";
        echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
        echo "<td data-label='Status de Vencimento'>". $statusData ."</td>";
        echo "<td data-label='Ações'>
                  <a href='../actions/baixar_conta_receber.php?id={$row['id']}' class='btn-action btn-baixar'><i class='fa-solid fa-check'></i> Baixar</a>
                  <a href='editar_conta_receber.php?id={$row['id']}' class='btn-action btn-editar'><i class='fa-solid fa-pen'></i> Editar</a>
                  <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($row['responsavel']))."')\" class='btn-action btn-excluir'><i class='fa-solid fa-trash'></i> Excluir</a>
                  <button type='button' class='btn-action btn-gerar-cobranca' onclick=\"openCobrancaModal({$row['id']}, '".number_format((float)$row['valor'],2,',','.')."')\"><i class='fa-solid fa-envelope-open-text'></i> Gerar Cobrança</button>
                  <a href='#' onclick=\"openRepetirModal({$row['id']}, '".htmlspecialchars(addslashes($row['responsavel']))."'); return false;\" class='btn-action btn-repetir'><i class='fa-solid fa-clone'></i> Repetir</a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta a receber pendente encontrada.</p>";
}
?>

<div id="exportar_contas_receber" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('exportar_contas_receber').style.display='none'">&times;</span>
        <h3>Exportar Contas a Receber</h3>
        <form id="formExportarReceber" action="" method="GET" target="_blank">
            <label for="data_inicio_export">Data de Início:</label>
            <input type="date" id="data_inicio_export" name="data_inicio">
            <label for="data_fim_export">Data de Fim:</label>
            <input type="date" id="data_fim_export" name="data_fim">

            <label for="status_export">Status:</label>
            <select id="status_export" name="status">
                <option value="pendente">Em Aberto</option>
                <option value="baixada">Baixadas</option>
            </select>

            <p>Selecione o formato para exportação:</p>
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" name="formato" value="csv" class="btn btn-export">CSV</button>
                <button type="submit" name="formato" value="pdf" class="btn btn-export">PDF</button>
                <button type="submit" name="formato" value="excel" class="btn btn-export">Excel</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal"><div class="modal-content"></div></div>

<div id="cobrancaModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('cobrancaModal').style.display='none'">&times;</span>
        <h3>Gerar Cobrança</h3>
        <form action="../actions/enviar_cobranca_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="conta_id" id="modalContaId">
            <p>Cobrança no valor de R$ <strong id="modalValorConta"></strong></p>

            <div class="autocomplete-container">
                <input type="text" id="pesquisar_cliente_cobranca" name="cliente_nome" placeholder="Pesquisar cliente..." autocomplete="off" required>
                <div id="cliente_cobranca_autocomplete_list" class="autocomplete-items"></div>
            </div>
            <input type="hidden" name="pessoa_id" id="pessoa_id_hidden">
            <input type="email" id="email_destinatario" name="email_destinatario" placeholder="Email do destinatário" required>

            <select name="banco_id" required>
                <option value="">-- Selecione uma Conta Bancária para Pagamento --</option>
                <?php foreach ($bancos as $banco): ?>
                    <option value="<?= $banco['id'] ?>">
                        <?= htmlspecialchars($banco['nome_banco']) ?> (PIX: <?= htmlspecialchars($banco['chave_pix']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="anexo">Anexar arquivo:</label>
            <input type="file" name="anexo" id="anexo">

            <button type="submit">Enviar Cobrança por Email</button>
        </form>
    </div>
</div>

<div id="repetirModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="document.getElementById('repetirModal').style.display='none'">&times;</span>
    <h3>Repetir Conta a Receber</h3>
    <form action="../actions/repetir_conta_receber.php" method="POST">
      <input type="hidden" name="conta_id" id="modalRepetirContaId">
      <p>Repetir conta de <strong id="modalRepetirResponsavel"></strong>?</p>
      <label for="repetir_vezes">Repetir quantas vezes?</label>
      <input type="number" name="repetir_vezes" value="1" min="1" required>
      <label for="repetir_intervalo">A cada quantos dias?</label>
      <input type="number" name="repetir_intervalo" value="30" min="1" required>
      <button type="submit">Repetir</button>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    document.getElementById('formExportarReceber').addEventListener('submit', function(e) {
        let formato = e.submitter.value;
        this.action = `../actions/exportar_contas_receber.php?formato=${formato}`;
    });

    $(document).ready(function() {
        $("#pesquisar_responsavel").on("keyup", function() {
            var term = $(this).val();
            if (term.length < 2) {
              $("#responsavel_autocomplete_list").empty();
              $("#responsavel_id_hidden").val('');
              return;
            }
            $.ajax({
                url: 'contas_receber.php',
                type: 'GET',
                data: { action: 'search_responsavel', term: term },
                dataType: 'json',
                success: function(data) {
                  var items = "";
                  $.each(data, function(index, item) {
                    items += `<div data-id="${item.id}">${item.nome}</div>`;
                  });
                  $("#responsavel_autocomplete_list").html(items);
                }
            });
        });

        $(document).on("click", "#responsavel_autocomplete_list div", function() {
            var id = $(this).data("id");
            var name = $(this).text();
            $("#pesquisar_responsavel").val(name);
            $("#responsavel_id_hidden").val(id);
            $("#responsavel_autocomplete_list").empty();
        });

        $("#pesquisar_cliente_cobranca").on("keyup", function() {
            var term = $(this).val();
            if (term.length < 2) {
              $("#cliente_cobranca_autocomplete_list").empty();
              $("#pessoa_id_hidden").val('');
              $("#email_destinatario").val('');
              return;
            }
            $.ajax({
                url: 'contas_receber.php',
                type: 'GET',
                data: { action: 'search_cliente', term: term },
                dataType: 'json',
                success: function(data) {
                  var items = "";
                  $.each(data, function(index, item) {
                    items += `<div data-id="${item.id}" data-email="${item.email}">${item.nome}</div>`;
                  });
                  $("#cliente_cobranca_autocomplete_list").html(items);
                }
            });
        });

        $(document).on("click", "#cliente_cobranca_autocomplete_list div", function() {
            var id = $(this).data("id");
            var name = $(this).text();
            var email = $(this).data("email");
            $("#pesquisar_cliente_cobranca").val(name);
            $("#pessoa_id_hidden").val(id);
            $("#email_destinatario").val(email);
            $("#cliente_cobranca_autocomplete_list").empty();
        });

        $(document).on("click", function(e) {
          if (!$(e.target).closest('.autocomplete-container').length) {
            $(".autocomplete-items").empty();
          }
        });
    });

    function toggleForm(){ 
      const modal = document.getElementById('addContaModal'); 
      modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex'; 
    }

    function openDeleteModal(id, responsavel) {
      const modal = document.getElementById('deleteModal');
      const modalContent = modal.querySelector('.modal-content');
      modalContent.innerHTML = `
          <span class="close-btn" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
          <h3>Confirmar Exclusão</h3>
          <p>Tem certeza que deseja excluir a conta de <strong>${responsavel}</strong>?</p>
          <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
              <a href="../actions/excluir_conta_receber.php?id=${id}" class='btn-action btn-excluir'>Sim, Excluir</a>
              <button type="button" class='btn' onclick="document.getElementById('deleteModal').style.display='none'">Cancelar</button>
          </div>
      `;
      modal.style.display = 'flex';
    }

    function openCobrancaModal(contaId, valor) {
      document.getElementById('modalContaId').value = contaId;
      document.getElementById('modalValorConta').innerText = valor;
      document.getElementById('pesquisar_cliente_cobranca').value = '';
      document.getElementById('pessoa_id_hidden').value = '';
      document.getElementById('email_destinatario').value = '';
      document.getElementById('cobrancaModal').style.display = 'flex';
    }

    function openRepetirModal(id, responsavel) {
      document.getElementById('modalRepetirContaId').value = id;
      document.getElementById('modalRepetirResponsavel').innerText = responsavel;
      document.getElementById('repetirModal').style.display = 'flex';
    }

    window.addEventListener('click', e => {
      const modals = document.querySelectorAll('.modal');
      modals.forEach(modal => {
        if (e.target === modal) {
          modal.style.display = 'none';
        }
      });
    });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
