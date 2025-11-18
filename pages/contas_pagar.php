<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ✅ 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php");
    exit();
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha de conexão.");
}

// ✅ 2. DADOS DA SESSÃO
$usuarioId = $_SESSION['usuario_id'];
$perfil = $_SESSION['nivel_acesso'];

// AJAX Search
if (isset($_GET['action']) && $_GET['action'] === 'search_fornecedor') {
    $term = $_GET['term'] ?? '';
    $stmt = $conn->prepare("SELECT id, nome FROM pessoas_fornecedores WHERE id_usuario = ? AND nome LIKE ? AND tipo = 'fornecedor' ORDER BY nome ASC LIMIT 10");
    $searchTerm = "%{$term}%";
    $stmt->bind_param("is", $usuarioId, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $fornecedores = [];
    while ($row = $result->fetch_assoc()) { $fornecedores[] = $row; }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($fornecedores);
    exit;
}

include('../includes/header.php');

// Categorias
$stmt_categorias = $conn->prepare("SELECT id, nome FROM categorias WHERE id_usuario = ? AND tipo = 'despesa' ORDER BY nome ASC");
$stmt_categorias->bind_param("i", $usuarioId);
$stmt_categorias->execute();
$result_categorias = $stmt_categorias->get_result();
$categorias_despesa = [];
while ($row_cat = $result_categorias->fetch_assoc()) { $categorias_despesa[] = $row_cat; }
$stmt_categorias->close();

// ✅ 3. QUERY PRINCIPAL
$where = ["cp.status='pendente'", "cp.usuario_id = " . intval($usuarioId)];

if (!empty($_GET['fornecedor'])) $where[] = "cp.fornecedor LIKE '%" . $conn->real_escape_string($_GET['fornecedor']) . "%'";
if (!empty($_GET['numero'])) $where[] = "cp.numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $where[] = "cp.data_vencimento BETWEEN '" . $conn->real_escape_string($_GET['data_inicio']) . "' AND '" . $conn->real_escape_string($_GET['data_fim']) . "'";
}

$sql = "SELECT cp.*, c.nome as nome_categoria, pf.nome as nome_pessoa_fornecedor
        FROM contas_pagar AS cp
        LEFT JOIN categorias AS c ON cp.id_categoria = c.id
        LEFT JOIN pessoas_fornecedores AS pf ON cp.id_pessoa_fornecedor = pf.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY cp.data_vencimento ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Contas a Pagar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #00bfff; }
    
    /* Autocomplete */
    .autocomplete-container { position: relative; width: 100%; }
    .autocomplete-items { position: absolute; border: 1px solid #444; border-top: none; z-index: 99; top: 100%; left: 0; right: 0; background-color: #333; max-height: 150px; overflow-y: auto; }
    .autocomplete-items div { padding: 10px; cursor: pointer; border-bottom: 1px solid #444; }
    .autocomplete-items div:hover { background-color: #555; }

    /* Mensagens */
    .success-message, .error-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; color: white; }
    .success-message { background-color: #27ae60; }
    .error-message { background-color: #cc3333; }
    .close-msg-btn { float: right; cursor: pointer; font-size: 20px; }

    /* Tabela e Form */
    form.search-form { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 25px; }
    form.search-form input { padding: 10px; background: #333; border: 1px solid #444; color: #eee; border-radius: 5px; }
    button, .btn { padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; color: white; text-decoration: none; display: inline-block; }
    .btn-search { background-color: #27ae60; }
    .btn-clear { background-color: #c0392b; }
    .btn-add { background-color: #00bfff; }
    .btn-export { background-color: #27ae60; }
    
    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; border-collapse: collapse; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }
    tr.vencido { background-color: #622 !important; }
    
    .btn-action { padding: 5px 10px; font-size: 12px; margin: 2px; }
    .btn-baixar { background: #27ae60; }
    .btn-editar { background: #00bfff; }
    .btn-excluir { background: #c0392b; }
    .btn-repetir { background: #f39c12; }

    /* Modal */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; border: 1px solid #444; }
    .modal-content form { display: flex; flex-direction: column; gap: 15px; }
    .modal-content input, .modal-content select { padding: 10px; background: #333; border: 1px solid #444; color: #fff; border-radius: 5px; }
    .close-btn { float: right; font-size: 28px; cursor: pointer; color: #aaa; }
  </style>
</head>
<body>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\'">&times;</span></div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\'">&times;</span></div>';
    unset($_SESSION['error_message']);
}
?>

<h2>Contas a Pagar</h2>

<form class="search-form" method="GET">
  <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?= htmlspecialchars($_GET['fornecedor'] ?? '') ?>">
  <input type="text" name="numero" placeholder="Número / Doc" value="<?= htmlspecialchars($_GET['numero'] ?? '') ?>">
  <input type="date" name="data_inicio" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
  <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
  <button type="submit" class="btn-search">Buscar</button>
  <a href="contas_pagar.php" class="btn btn-clear">Limpar</a>
</form>

<div style="text-align:center; margin-bottom:20px;">
  <button class="btn btn-add" onclick="document.getElementById('addContaModal').style.display='flex'">➕ Nova Conta</button>
  <button class="btn btn-export" onclick="document.getElementById('exportModal').style.display='flex'">Exportar</button>
</div>

<div id="addContaModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="document.getElementById('addContaModal').style.display='none'">&times;</span>
    <h3>Nova Conta</h3>
    <form action="../actions/add_conta_pagar.php" method="POST">
        <div class="autocomplete-container">
            <input type="text" id="pesquisar_fornecedor" name="fornecedor_nome" placeholder="Fornecedor..." required autocomplete="off">
            <div id="fornecedor_list" class="autocomplete-items"></div>
        </div>
        <input type="hidden" name="fornecedor_id" id="fornecedor_id_hidden">
        
        <input type="text" name="numero" placeholder="Número do Documento" required>
        
        <input type="text" name="descricao" placeholder="Descrição / Observação">
        <input type="text" name="valor" placeholder="Valor (ex: 123,45)" required oninput="this.value=this.value.replace(/[^0-9.,]/g,'')">
        <input type="date" name="data_vencimento" required>
        
        <select name="id_categoria" required>
            <option value="">-- Categoria --</option>
            <?php foreach ($categorias_despesa as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <label><input type="checkbox" name="enviar_email" value="S" checked> Enviar Lembrete</label>
        <button type="submit" class="btn btn-add">Salvar</button>
    </form>
  </div>
</div>

<?php if ($result && $result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Fornecedor</th>
            <th>Número</th>
            <th>Vencimento</th>
            <th>Categoria</th>
            <th>Valor</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $hoje = date('Y-m-d');
    while($row = $result->fetch_assoc()):
        $vencido = ($row['data_vencimento'] < $hoje) ? 'vencido' : '';
        $nomeFornecedor = !empty($row['nome_pessoa_fornecedor']) ? $row['nome_pessoa_fornecedor'] : $row['fornecedor'];
    ?>
        <tr class="<?= $vencido ?>">
            <td><?= htmlspecialchars($nomeFornecedor) ?></td>
            <td><?= htmlspecialchars($row['numero']) ?></td>
            <td><?= date('d/m/Y', strtotime($row['data_vencimento'])) ?></td>
            <td><?= htmlspecialchars($row['nome_categoria'] ?? '-') ?></td>
            <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
            <td><?= $vencido ? 'Vencido' : 'Em dia' ?></td>
            <td>
                <a href="../actions/baixar_conta.php?id=<?= $row['id'] ?>" class="btn btn-action btn-baixar" title="Baixar"><i class="fa fa-check"></i></a>
                <a href="editar_conta_pagar.php?id=<?= $row['id'] ?>" class="btn btn-action btn-editar"><i class="fa fa-pen"></i></a>
                <a href="#" onclick="confirmarExclusao(<?= $row['id'] ?>)" class="btn btn-action btn-excluir"><i class="fa fa-trash"></i></a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p class="error-message" style="background:#333;">Nenhuma conta encontrada.</p>
<?php endif; ?>

<div id="exportModal" class="modal">
    <div class="modal-content" style="text-align:center;">
        <span class="close-btn" onclick="document.getElementById('exportModal').style.display='none'">&times;</span>
        <h3>Exportar</h3>
        <a href="../actions/exportar_contas_pagar.php?formato=csv" class="btn btn-export" target="_blank">CSV</a>
        <a href="../actions/exportar_contas_pagar.php?formato=pdf" class="btn btn-export" target="_blank">PDF</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Autocomplete
$("#pesquisar_fornecedor").on("keyup", function() {
    let term = $(this).val();
    if (term.length < 2) return $("#fornecedor_list").empty();
    $.getJSON("contas_pagar.php", { action: 'search_fornecedor', term: term }, function(data) {
        let items = data.map(i => `<div onclick="selectFornecedor(${i.id}, '${i.nome}')">${i.nome}</div>`).join('');
        $("#fornecedor_list").html(items);
    });
});
function selectFornecedor(id, nome) {
    $("#pesquisar_fornecedor").val(nome);
    $("#fornecedor_id_hidden").val(id);
    $("#fornecedor_list").empty();
}
function confirmarExclusao(id) {
    if(confirm("Excluir esta conta?")) window.location.href = `../actions/excluir_conta_pagar.php?id=${id}`;
}
window.onclick = e => { if($(e.target).hasClass('modal')) $('.modal').hide(); }
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>