<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php");
    exit();
}
$conn = getTenantConnection();
if ($conn === null) die("Falha de conexão.");

$usuarioId = $_SESSION['usuario_id'];

// AJAX Search
if (isset($_GET['action']) && $_GET['action'] === 'search_fornecedor') {
    $term = $_GET['term'] ?? '';
    $stmt = $conn->prepare("SELECT id, nome FROM pessoas_fornecedores WHERE id_usuario = ? AND nome LIKE ? AND tipo = 'fornecedor' ORDER BY nome ASC LIMIT 10");
    $searchTerm = "%{$term}%";
    $stmt->bind_param("is", $usuarioId, $searchTerm);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

include('../includes/header.php');

// Categorias
$stmt = $conn->prepare("SELECT id, nome FROM categorias WHERE id_usuario = ? AND tipo = 'despesa' ORDER BY nome ASC");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$categorias_despesa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Query Principal
$where = ["cp.status='pendente'", "cp.usuario_id = " . intval($usuarioId)];
if (!empty($_GET['data_inicio'])) $where[] = "cp.data_vencimento >= '" . $conn->real_escape_string($_GET['data_inicio']) . "'";
if (!empty($_GET['data_fim'])) $where[] = "cp.data_vencimento <= '" . $conn->real_escape_string($_GET['data_fim']) . "'";

$sql = "SELECT cp.*, c.nome as nome_categoria, pf.nome as nome_pessoa_fornecedor
        FROM contas_pagar AS cp
        LEFT JOIN categorias AS c ON cp.id_categoria = c.id
        LEFT JOIN pessoas_fornecedores AS pf ON cp.id_pessoa_fornecedor = pf.id
        WHERE " . implode(" AND ", $where) . " ORDER BY cp.data_vencimento ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Pagar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #00bfff; }
    
    /* Estilos Gerais */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; border: 1px solid #444; position: relative; }
    .close-btn { position: absolute; top: 10px; right: 15px; font-size: 28px; cursor: pointer; color: #aaa; }
    
    form.search-form { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 25px; align-items: center; }
    input, select { padding: 8px; background: #333; border: 1px solid #444; color: #eee; border-radius: 5px; }
    
    /* Tabela */
    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }
    tr.vencido { background-color: #622 !important; }

    /* Botões */
    .btn { padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; color: white; text-decoration: none; display: inline-block; font-size: 14px; }
    .btn-add { background-color: #00bfff; }
    .btn-search { background-color: #27ae60; }
    .btn-clear { background-color: #c0392b; }
    .btn-export { background-color: #f39c12; }
    
    .btn-action { padding: 6px 10px; margin: 2px; font-size: 13px; }
    .btn-baixar { background: #27ae60; }
    .btn-editar { background: #00bfff; }
    .btn-excluir { background: #c0392b; }
    .btn-repetir { background: #f39c12; }
    
    /* Autocomplete */
    .autocomplete-container { position: relative; }
    .autocomplete-items { position: absolute; border: 1px solid #444; z-index: 99; top: 100%; left: 0; right: 0; background-color: #333; max-height: 150px; overflow-y: auto; }
    .autocomplete-items div { padding: 10px; cursor: pointer; border-bottom: 1px solid #444; }
    .autocomplete-items div:hover { background-color: #555; }
    
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
    .alert-success { background-color: #27ae60; color: white; }
    .alert-danger { background-color: #c0392b; color: white; }
  </style>
</head>
<body>

<?php
if (isset($_SESSION['success_message'])) { echo "<div class='alert alert-success'>{$_SESSION['success_message']}</div>"; unset($_SESSION['success_message']); }
if (isset($_SESSION['error_message'])) { echo "<div class='alert alert-danger'>{$_SESSION['error_message']}</div>"; unset($_SESSION['error_message']); }
?>

<h2>Contas a Pagar</h2>

<form class="search-form" method="GET">
  <input type="date" name="data_inicio" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>" title="Data Início">
  <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>" title="Data Fim">
  
  <button type="submit" class="btn btn-search" title="Filtrar"><i class="fa fa-search"></i> Buscar</button>
  <a href="contas_pagar.php" class="btn btn-clear" title="Limpar Filtros"><i class="fa fa-eraser"></i> Limpar</a>

  <button type="button" class="btn btn-add" onclick="document.getElementById('addContaModal').style.display='flex'">➕ Nova</button>
  <button type="button" class="btn btn-export" onclick="document.getElementById('exportModal').style.display='flex'"><i class="fa fa-download"></i> Exportar</button>
</form>

<?php if ($result && $result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Fornecedor</th>
            <th>Número</th>
            <th>Descrição</th>
            <th>Vencimento</th>
            <th>Categoria</th>
            <th>Valor</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php $hoje = date('Y-m-d'); 
    while($row = $result->fetch_assoc()): 
        $vencido = ($row['data_vencimento'] < $hoje) ? 'vencido' : '';
        $nome = !empty($row['nome_pessoa_fornecedor']) ? $row['nome_pessoa_fornecedor'] : 'N/D';
    ?>
        <tr class="<?= $vencido ?>">
            <td><?= htmlspecialchars($nome) ?></td>
            <td><?= htmlspecialchars($row['numero'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['descricao'] ?? '') ?></td>
            <td><?= date('d/m/Y', strtotime($row['data_vencimento'])) ?></td>
            <td><?= htmlspecialchars($row['nome_categoria'] ?? '-') ?></td>
            <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
            <td><?= $vencido ? 'Vencido' : 'Em dia' ?></td>
            <td>
                <button onclick="abrirModalBaixar(<?= $row['id'] ?>, '<?= addslashes($nome) ?>', '<?= $row['valor'] ?>')" class="btn btn-action btn-baixar" title="Dar Baixa"><i class="fa fa-check"></i></button>
                <a href="editar_conta_pagar.php?id=<?= $row['id'] ?>" class="btn btn-action btn-editar"><i class="fa fa-pen"></i></a>
                <button onclick="abrirModalRepetir(<?= $row['id'] ?>)" class="btn btn-action btn-repetir"><i class="fa-solid fa-repeat"></i></button>
                
                <button onclick="openDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($nome)) ?>')" class="btn btn-action btn-excluir" title="Excluir"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center; margin-top:20px;">Nenhuma conta pendente.</p>
<?php endif; ?>

<div id="addContaModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
    <h3>Nova Conta</h3>
    <form action="../actions/add_conta_pagar.php" method="POST" style="display:flex; flex-direction:column; gap:10px;">
        <div class="autocomplete-container">
            <input type="text" id="pesquisar_fornecedor" name="fornecedor_nome" placeholder="Fornecedor..." required style="width:100%;">
            <div id="fornecedor_list" class="autocomplete-items"></div>
        </div>
        <input type="hidden" name="fornecedor_id" id="fornecedor_id_hidden">
        
        <input type="text" name="numero" placeholder="Número do Documento">
        <input type="text" name="descricao" placeholder="Descrição" required>
        <input type="text" name="valor" placeholder="Valor (0,00)" required>
        <input type="date" name="data_vencimento" required>
        <select name="id_categoria" required>
            <option value="">Categoria...</option>
            <?php foreach ($categorias_despesa as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-add">Salvar</button>
    </form>
  </div>
</div>

<div id="baixarModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
    <h3>Dar Baixa na Conta</h3>
    <p id="texto-baixa" style="color:#aaa; margin-bottom:15px;"></p>
    
    <form action="../actions/baixar_conta.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
        <input type="hidden" name="id_conta" id="id_conta_baixa">
        <label>Data do Pagamento:</label>
        <input type="date" name="data_baixa" value="<?= date('Y-m-d') ?>" required>
        <label>Forma de Pagamento:</label>
        <select name="forma_pagamento" required>
            <option value="dinheiro">Dinheiro</option>
            <option value="pix">Pix</option>
            <option value="cartao_credito">Cartão de Crédito</option>
            <option value="cartao_debito">Cartão de Débito</option>
            <option value="transferencia">Transferência</option>
            <option value="boleto">Boleto</option>
        </select>
        <label>Anexar Comprovante (Opcional):</label>
        <input type="file" name="comprovante" accept="image/*,.pdf">
        <button type="submit" class="btn btn-baixar" style="margin-top:10px;">Confirmar Baixa</button>
    </form>
  </div>
</div>

<div id="repetirModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
    <h3>Repetir Conta</h3>
    <form action="../actions/repetir_conta_pagar.php" method="POST" style="display:flex; flex-direction:column; gap:10px;">
        <input type="hidden" name="conta_id" id="repetir_conta_id">
        <input type="number" name="repetir_vezes" placeholder="Quantas vezes?" required>
        <input type="number" name="repetir_intervalo" value="30" placeholder="Intervalo dias">
        <button type="submit" class="btn btn-repetir">Repetir</button>
    </form>
  </div>
</div>

<div id="exportModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('exportModal').style.display='none'">&times;</span>
        <h3>Exportar Relatório</h3>
        <p style="color:#aaa; margin-bottom:15px; font-size:14px;">Selecione o tipo, período e formato.</p>
        
        <form action="../actions/exportar_contas_pagar.php" method="GET" target="_blank" style="display:flex; flex-direction:column; gap:10px;">
            <label style="text-align:left; color:#ccc; font-size:12px;">Tipo de Relatório:</label>
            <select name="status" required>
                <option value="pendente">Contas a Pagar (Pendentes)</option>
                <option value="baixada">Contas Pagas (Baixadas)</option>
            </select>

            <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                    <label style="display:block; text-align:left; color:#ccc; font-size:12px;">De:</label>
                    <input type="date" name="data_inicio" value="<?= date('Y-m-01') ?>" required style="width:100%;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; text-align:left; color:#ccc; font-size:12px;">Até:</label>
                    <input type="date" name="data_fim" value="<?= date('Y-m-t') ?>" required style="width:100%;">
                </div>
            </div>

            <label style="text-align:left; color:#ccc; font-size:12px;">Formato:</label>
            <select name="formato" required>
                <option value="excel">Excel (.xlsx)</option>
                <option value="pdf">PDF (.pdf)</option>
                <option value="csv">CSV (.csv)</option>
            </select>

            <button type="submit" class="btn btn-export" style="margin-top:10px;">
                <i class="fa fa-download"></i> Baixar Arquivo
            </button>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza que deseja excluir a conta de <b id="delete-nome"></b>?</p>
        <div style="margin-top:20px; display:flex; justify-content:center; gap:10px;">
            <a id="btn-confirm-delete" href="#" class="btn btn-excluir" style="text-decoration:none;">Sim, Excluir</a>
            <button onclick="document.getElementById('deleteModal').style.display='none'" class="btn" style="background-color:#555;">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function abrirModalBaixar(id, nome, valor) {
    document.getElementById('id_conta_baixa').value = id;
    document.getElementById('texto-baixa').innerText = `${nome} - R$ ${valor}`;
    document.getElementById('baixarModal').style.display = 'flex';
}

function abrirModalRepetir(id) {
    document.getElementById('repetir_conta_id').value = id;
    document.getElementById('repetirModal').style.display = 'flex';
}

// ✅ Função para abrir o modal de exclusão
function openDeleteModal(id, nome) {
    document.getElementById('delete-nome').innerText = nome;
    document.getElementById('btn-confirm-delete').href = '../actions/excluir_conta_pagar.php?id=' + id;
    document.getElementById('deleteModal').style.display = 'flex';
}

// Autocomplete simples
$("#pesquisar_fornecedor").on("keyup", function() {
    let term = $(this).val();
    if (term.length < 2) return $("#fornecedor_list").empty();
    $.getJSON("contas_pagar.php", { action: 'search_fornecedor', term: term }, function(data) {
        let html = data.map(i => `<div onclick="selectForn(${i.id}, '${i.nome}')">${i.nome}</div>`).join('');
        $("#fornecedor_list").html(html);
    });
});
function selectForn(id, nome) {
    $("#pesquisar_fornecedor").val(nome);
    $("#fornecedor_id_hidden").val(id);
    $("#fornecedor_list").empty();
}

window.onclick = e => { if(e.target.className === 'modal') e.target.style.display = 'none'; }
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>