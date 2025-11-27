<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php");
    exit();
}
$conn = getTenantConnection();
if ($conn === null) die("Falha de conexão.");

$usuarioId = $_SESSION['usuario_id'];

// AJAX Search
if (isset($_GET['action']) && $_GET['action'] === 'search_pessoa') {
    $term = $_GET['term'] ?? '';
    $stmt = $conn->prepare("SELECT id, nome FROM pessoas_fornecedores WHERE id_usuario = ? AND nome LIKE ? AND (tipo = 'pessoa' OR tipo = 'ambos') ORDER BY nome ASC LIMIT 10");
    $searchTerm = "%{$term}%";
    $stmt->bind_param("is", $usuarioId, $searchTerm);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

include('../includes/header.php');

// --- EXIBE O FLASH MESSAGE CENTRALIZADO ---
display_flash_message();
// -----------------------------------------

// Categorias
$stmt = $conn->prepare("SELECT id, nome FROM categorias WHERE id_usuario = ? AND tipo = 'receita' ORDER BY nome ASC");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$categorias_receita = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Bancos
$stmt_bancos = $conn->prepare("SELECT nome_banco, chave_pix FROM contas_bancarias WHERE id_usuario = ? AND chave_pix IS NOT NULL AND chave_pix != ''");
$stmt_bancos->bind_param("i", $usuarioId);
$stmt_bancos->execute();
$lista_bancos_pix = $stmt_bancos->get_result()->fetch_all(MYSQLI_ASSOC);

// Query Principal
$where = ["cr.status='pendente'", "cr.usuario_id = " . intval($usuarioId)];
if (!empty($_GET['data_inicio'])) $where[] = "cr.data_vencimento >= '" . $conn->real_escape_string($_GET['data_inicio']) . "'";
if (!empty($_GET['data_fim'])) $where[] = "cr.data_vencimento <= '" . $conn->real_escape_string($_GET['data_fim']) . "'";

$sql = "SELECT cr.*, c.nome as nome_categoria, pf.nome as nome_pessoa
        FROM contas_receber AS cr
        LEFT JOIN categorias AS c ON cr.id_categoria = c.id
        LEFT JOIN pessoas_fornecedores AS pf ON cr.id_pessoa_fornecedor = pf.id
        WHERE " . implode(" AND ", $where) . " ORDER BY cr.data_vencimento ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas a Receber</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #00bfff; }
    
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; border: 1px solid #444; position: relative; text-align:center; }
    .modal-content form { text-align:left; }
    .close-btn { position: absolute; top: 10px; right: 15px; font-size: 28px; cursor: pointer; color: #aaa; }
    
    form.search-form { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 25px; align-items: center; }
    input, select, textarea { padding: 8px; background: #333; border: 1px solid #444; color: #eee; border-radius: 5px; }
    .modal-content input, .modal-content select, .modal-content textarea { width: 100%; box-sizing: border-box; margin-bottom: 10px; }
    label { display: block; margin-bottom: 5px; color: #ccc; font-size: 0.9em; }

    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }
    tr.vencido { background-color: #622 !important; }

    .btn { padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; color: white; text-decoration: none; display: inline-block; font-size: 14px; }
    .btn-add { background-color: #00bfff; }
    .btn-search { background-color: #27ae60; }
    .btn-clear { background-color: #c0392b; }
    .btn-export { background-color: #f39c12; }
    .btn-action { padding: 6px 10px; margin: 2px; font-size: 13px; }
    .btn-receber { background: #27ae60; }
    .btn-editar { background: #00bfff; }
    .btn-excluir { background: #c0392b; }
    .btn-repetir { background: #f39c12; }
    .btn-cobranca { background-color: #ffc107; color: #121212; }
    
    .autocomplete-container { position: relative; margin-bottom: 10px; }
    .autocomplete-items { position: absolute; border: 1px solid #444; z-index: 99; top: 100%; left: 0; right: 0; background-color: #333; max-height: 150px; overflow-y: auto; }
    .autocomplete-items div { padding: 10px; cursor: pointer; border-bottom: 1px solid #444; }
    .autocomplete-items div:hover { background-color: #555; }
  </style>
</head>
<body>

<h2>Contas a Receber</h2>

<form class="search-form" method="GET">
  <input type="date" name="data_inicio" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>" title="Data Início">
  <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>" title="Data Fim">
  <button type="submit" class="btn btn-search" title="Filtrar"><i class="fa fa-search"></i> Buscar</button>
  <a href="contas_receber.php" class="btn btn-clear" title="Limpar Filtros"><i class="fa fa-eraser"></i> Limpar</a>
  <button type="button" class="btn btn-add" onclick="document.getElementById('addContaModal').style.display='flex'">➕ Nova</button>
  <button type="button" class="btn btn-export" onclick="document.getElementById('exportModal').style.display='flex'"><i class="fa fa-download"></i> Exportar</button>
</form>

<?php if ($result && $result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Cliente</th>
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
        $nome = !empty($row['nome_pessoa']) ? $row['nome_pessoa'] : 'N/D';
    ?>
        <tr class="<?= $vencido ?>">
            <td><?= htmlspecialchars($nome) ?></td>
            <td><?= htmlspecialchars($row['numero'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['descricao'] ?? '') ?></td>
            <td><?= date('d/m/Y', strtotime($row['data_vencimento'])) ?></td>
            <td><?= htmlspecialchars($row['nome_categoria'] ?? '-') ?></td>
            <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
            <td><?= $vencido ? 'Atrasado' : 'Em dia' ?></td>
            <td style="white-space: nowrap;">
                <button onclick="abrirModalReceber(<?= $row['id'] ?>, '<?= addslashes($nome) ?>', '<?= $row['valor'] ?>')" class="btn btn-action btn-receber" title="Receber"><i class="fa fa-check"></i></button>
                <button onclick="abrirModalCobranca(<?= $row['id'] ?>, '<?= addslashes($nome) ?>', '<?= $row['valor'] ?>')" class="btn btn-action btn-cobranca" title="Enviar Cobrança"><i class="fa fa-envelope"></i></button>
                <a href="editar_conta_receber.php?id=<?= $row['id'] ?>" class="btn btn-action btn-editar"><i class="fa fa-pen"></i></a>
                <button onclick="abrirModalRepetir(<?= $row['id'] ?>)" class="btn btn-action btn-repetir"><i class="fa-solid fa-repeat"></i></button>
                
                <button 
                    type="button"
                    class="btn btn-action btn-excluir" 
                    data-id="<?= $row['id'] ?>" 
                    data-nome="<?= htmlspecialchars($nome) ?>"
                    onclick="openDeleteModal(this)"
                    title="Excluir">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center; margin-top:20px;">Nenhuma conta a receber encontrada.</p>
<?php endif; ?>

<div id="cobrancaModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="document.getElementById('cobrancaModal').style.display='none'">&times;</span>
    <h3>Enviar Cobrança</h3>
    <p id="txt-cobranca" style="color:#aaa; font-size:14px; margin-bottom:15px;"></p>
    <form action="../actions/enviar_cobranca_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_conta" id="cobranca_id_conta">
        <label>Chave Pix (Opcional):</label>
        <select name="chave_pix">
            <option value="">-- Não incluir Pix --</option>
            <?php foreach ($lista_bancos_pix as $banco): ?>
                <option value="<?= htmlspecialchars($banco['chave_pix']) ?>"><?= htmlspecialchars($banco['nome_banco']) ?> - <?= htmlspecialchars($banco['chave_pix']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Anexar Arquivo:</label>
        <input type="file" name="arquivo" accept=".pdf,.jpg,.jpeg,.png">
        <label>Mensagem:</label>
        <textarea name="mensagem" rows="3"></textarea>
        <button type="submit" class="btn btn-cobranca" style="margin-top:10px; width:100%;">Enviar E-mail</button>
    </form>
  </div>
</div>

<div id="addContaModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
    <h3>Nova Receita</h3>
    <form action="../actions/add_conta_receber.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="autocomplete-container">
            <input type="text" id="pesquisar_pessoa" name="pessoa_nome" placeholder="Cliente/Pagador..." required>
            <div id="pessoa_list" class="autocomplete-items"></div>
        </div>
        <input type="hidden" name="pessoa_id" id="pessoa_id_hidden">
        <input type="text" name="numero" placeholder="Número do Documento">
        <input type="text" name="descricao" placeholder="Descrição" required>
        <input type="text" name="valor" placeholder="Valor (Ex: 1.000,00)" required>
        <input type="date" name="data_vencimento" required>
        <select name="id_categoria" required>
            <option value="">Categoria...</option>
            <?php foreach ($categorias_receita as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-add" style="width:100%">Salvar</button>
    </form>
  </div>
</div>

<div id="receberModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
    <h3>Confirmar Recebimento</h3>
    <p id="texto-receber" style="color:#aaa; margin-bottom:15px;"></p>
    <form action="../actions/baixar_conta_receber.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_conta" id="id_conta_receber">
        <label>Data do Recebimento:</label>
        <input type="date" name="data_baixa" value="<?= date('Y-m-d') ?>" required>
        <label>Forma:</label>
        <select name="forma_pagamento" required>
            <option value="dinheiro">Dinheiro</option>
            <option value="pix">Pix</option>
            <option value="cartao_credito">Cartão de Crédito</option>
            <option value="cartao_debito">Cartão de Débito</option>
            <option value="transferencia">Transferência</option>
            <option value="boleto">Boleto</option>
        </select>
        <label>Comprovante:</label>
        <input type="file" name="comprovante" accept="image/*,.pdf">
        <button type="submit" class="btn btn-receber" style="margin-top:10px; width:100%">Confirmar</button>
    </form>
  </div>
</div>

<div id="repetirModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
    <h3>Repetir Conta</h3>
    <form action="../actions/repetir_conta_receber.php" method="POST">
        <input type="hidden" name="conta_id" id="repetir_conta_id">
        <label>Quantas vezes?</label>
        <input type="number" name="repetir_vezes" required>
        <label>Intervalo (dias):</label>
        <input type="number" name="repetir_intervalo" value="30">
        <button type="submit" class="btn btn-repetir" style="width:100%; margin-top:10px;">Repetir</button>
    </form>
  </div>
</div>

<div id="exportModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('exportModal').style.display='none'">&times;</span>
        <h3>Exportar Relatório</h3>
        <form action="../actions/exportar_contas_receber.php" method="GET" target="_blank">
            <label>Tipo:</label>
            <select name="status" required>
                <option value="pendente">A Receber (Pendentes)</option>
                <option value="baixada">Recebidas (Baixadas)</option>
            </select>
            <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                    <label>De:</label>
                    <input type="date" name="data_inicio" value="<?= date('Y-m-01') ?>" required>
                </div>
                <div style="flex:1;">
                    <label>Até:</label>
                    <input type="date" name="data_fim" value="<?= date('Y-m-t') ?>" required>
                </div>
            </div>
            <label>Formato:</label>
            <select name="formato" required>
                <option value="excel">Excel (.xlsx)</option>
                <option value="pdf">PDF (.pdf)</option>
                <option value="csv">CSV (.csv)</option>
            </select>
            <button type="submit" class="btn btn-export" style="margin-top:10px; width:100%">Baixar</button>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza que deseja excluir a conta de <b id="delete-nome"></b>?</p>
        <form action="../actions/excluir_conta_receber.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id" id="delete-id">
            <input type="hidden" name="redirect" value="">
            <div style="margin-top:20px; display:flex; justify-content:center; gap:10px;">
                <button type="submit" class="btn btn-excluir">Sim, Excluir</button>
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'" class="btn" style="background-color:#555;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function abrirModalCobranca(id, nome, valor) {
    document.getElementById('cobranca_id_conta').value = id;
    document.getElementById('txt-cobranca').innerText = `Enviar cobrança para: ${nome} (R$ ${valor})`;
    document.getElementById('cobrancaModal').style.display = 'flex';
}
function abrirModalReceber(id, nome, valor) {
    document.getElementById('id_conta_receber').value = id;
    document.getElementById('texto-receber').innerText = `${nome} - R$ ${valor}`;
    document.getElementById('receberModal').style.display = 'flex';
}
function abrirModalRepetir(id) {
    document.getElementById('repetir_conta_id').value = id;
    document.getElementById('repetirModal').style.display = 'flex';
}
function openDeleteModal(button) {
    let id = button.getAttribute('data-id');
    let nome = button.getAttribute('data-nome');
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-nome').innerText = nome;
    document.getElementById('deleteModal').style.display = 'flex';
}
$("#pesquisar_pessoa").on("keyup", function() {
    let term = $(this).val();
    if (term.length < 2) return $("#pessoa_list").empty();
    $.getJSON("contas_receber.php", { action: 'search_pessoa', term: term }, function(data) {
        let html = data.map(i => `<div onclick="selectPessoa(${i.id}, '${i.nome}')">${i.nome}</div>`).join('');
        $("#pessoa_list").html(html);
    });
});
function selectPessoa(id, nome) {
    $("#pesquisar_pessoa").val(nome);
    $("#pessoa_id_hidden").val(id);
    $("#pessoa_list").empty();
}
window.onclick = e => { if(e.target.className === 'modal') e.target.style.display = 'none'; }
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>