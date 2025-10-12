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
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// --- BUSCANDO DADOS PARA O MODAL DE COBRANÇA ---
// Buscar pessoas/fornecedores
$stmt_pessoas = $conn->prepare("SELECT id, nome, email FROM pessoas_fornecedores WHERE id_usuario = ? ORDER BY nome ASC");
$stmt_pessoas->bind_param("i", $usuarioId);
$stmt_pessoas->execute();
$result_pessoas = $stmt_pessoas->get_result();
$pessoas = [];
while ($row_pessoa = $result_pessoas->fetch_assoc()) {
    $pessoas[] = $row_pessoa;
}
$stmt_pessoas->close();

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
// --- FIM DA BUSCA DE DADOS ---

// Monta filtros SQL (Sua lógica original)
$where = ["status='pendente'"];
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";
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
    /* SEU CSS ORIGINAL COM AJUSTES */
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
    /* Estilo para o novo botão */
    .btn-gerar-cobranca { background-color: #28a745; }
    .btn-gerar-cobranca:hover { background-color: #218838; }
    
    /* MODAL (Original e Novo) */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 800px; position: relative; }
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content .close-btn:hover { color: #00bfff; }
    .modal-content form { display: flex; flex-direction: column; gap: 15px; } /* Ajustado para empilhar */
    .modal-content form input, .modal-content form select { width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
    .modal-content form button { width: 100%; background-color: #00bfff; color: white; border: none; padding: 12px 25px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .modal-content form button:hover { background-color: #0099cc; }

    @media (max-width: 768px) {
        table, thead, tbody, th, td, tr { display: block; }
        th { display: none; }
        tr { margin-bottom: 15px; border: 1px solid #333; border-radius: 8px; padding: 10px; }
        td { position: relative; padding-left: 50%; text-align: right; }
        td::before { content: attr(data-label); position: absolute; left: 10px; font-weight: bold; color: #999; text-align: left; }
    }
    /* NOVO: Estilo para o botão de repetir */
.btn-repetir { 
  background-color: #f39c12; /* Cor de fundo laranja */
}
.btn-repetir:hover { 
  background-color: #d35400; /* Cor mais escura ao passar o mouse */
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
        echo "<td data-label='Ações'>
                  <a href='../actions/baixar_conta_receber.php?id={$row['id']}' class='btn-action btn-baixar'><i class='fa-solid fa-check'></i> Baixar</a>
                  <a href='editar_conta_receber.php?id={$row['id']}' class='btn-action btn-editar'><i class='fa-solid fa-pen'></i> Editar</a>
                  <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($row['responsavel']))."')\" class='btn-action btn-excluir'><i class='fa-solid fa-trash'></i> Excluir</a>
                  <button type='button' class='btn-action btn-gerar-cobranca' onclick=\"openCobrancaModal({$row['id']}, '".number_format((float)$row['valor'],2,',','.')."')\"class='btn-action btn-excluir'><i class='fa-solid fa-envelope-open-text'></i> Gerar Cobrança</a>
                  </button>
                  <a href='#' onclick=\"openRepetirModal({$row['id']}, '".htmlspecialchars(addslashes($row['responsavel']))."'); return false;\" class='btn-action btn-repetir'>
                      <i class='fa-solid fa-clone'></i> Repetir
                  </a>
              </td></tr>";
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
        <form action="../actions/exportar_contas_receber.php" method="POST" target="_blank">
            <div class="form-group">
                <label for="status">Status da Conta:</label>
                <select name="status" id="exportStatusReceber">
                    <option value="pendente">Pendentes</option>
                    <option value="baixada">Baixadas</option>
                    <option value="todos">Todas</option>
                </select>
            </div>
            <div class="form-group">
                <label for="data_inicio">De (Data):</label>
                <input type="date" name="data_inicio" required>
            </div>
            <div class="form-group">
                <label for="data_fim">Até (Data):</label>
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
    <div class="modal-content"></div>
</div>

<div id="cobrancaModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('cobrancaModal').style.display='none'">&times;</span>
        <h3>Gerar Nova Cobrança</h3>
        <form action="../actions/enviar_cobranca_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_conta" id="modalContaId">
            <p style="text-align: left;"><strong>Valor da Conta:</strong> R$ <span id="modalValorConta"></span></p>
            <hr style="border-top: 1px solid #444; width:100%;">
            
            <div>
                <label for="pessoa_id">Selecione o Cliente</label>
                <select name="pessoa_id" id="pessoa_id" required>
                    <option value="">-- Selecione um cliente --</option>
                    <?php foreach ($pessoas as $pessoa): ?>
                        <option value="<?= $pessoa['id'] ?>" data-email="<?= htmlspecialchars($pessoa['email']) ?>">
                            <?= htmlspecialchars($pessoa['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="email_destinatario">E-mail para Envio</label>
                <input type="email" name="email_destinatario" id="email_destinatario" readonly required>
            </div>

            <div>
                <label for="conta_bancaria_id">Selecione a Conta/PIX</label>
                <select name="conta_bancaria_id" required>
                     <option value="">-- Selecione uma conta --</option>
                    <?php foreach ($bancos as $banco): ?>
                        <option value="<?= $banco['id'] ?>">
                            <?= htmlspecialchars($banco['nome_banco']) ?> (PIX: <?= htmlspecialchars($banco['chave_pix'] ?? 'N/A') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="boleto_anexo">Anexar Boleto (Opcional)</label>
                <input type="file" name="boleto_anexo" style="border:none; background:none;">
            </div>
            
            <button type="submit">Enviar Cobrança por E-mail</button>
        </form>
    </div>
</div>

<div id="repetirModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('repetirModal').style.display='none'">&times;</span>
        <h3>Repetir / Parcelar Conta</h3>
        <form action="../actions/repetir_conta_receber.php" method="POST">
            <input type="hidden" name="id_conta" id="modalRepetirContaId">
            <p style="text-align: left;">Você está repetindo a conta do responsável: <br><strong><span id="modalRepetirResponsavel"></span></strong></p>
            <hr style="border-top: 1px solid #444; width:100%; border-bottom: none;">
            
            <div class="form-group">
                <label for="quantidade">Repetir mais quantas vezes?</label>
                <input type="number" id="quantidade" name="quantidade" min="1" max="60" value="1" required>
                <small style="color: #999;">Ex: Se esta é a parcela 1 de 12, digite 11.</small>
            </div>

            <div class="form-group">
                <label for="manter_nome">Como nomear as próximas contas?</label>
                <select name="manter_nome" id="manter_nome">                    
                    <option value="0">Manter o nome original</option>
                </select>
            </div>
            
            <button type="submit">Criar Repetições</button>
        </form>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    // Suas funções JS originais
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
                <a href="../actions/excluir_conta_receber.php?id=${id}" class="btn-action btn-excluir">Sim, Excluir</a>
                <button type="button" class="btn" onclick="document.getElementById('deleteModal').style.display='none'">Cancelar</button>
            </div>
        `;
        modal.style.display = 'flex';
    }

    // --- ADIÇÃO: FUNÇÃO PARA ABRIR O MODAL DE COBRANÇA ---
    function openCobrancaModal(contaId, valor) {
        // Preenche os campos do modal
        document.getElementById('modalContaId').value = contaId;
        document.getElementById('modalValorConta').innerText = valor;
        
        // Reseta os selects e o campo de email
        document.getElementById('pessoa_id').selectedIndex = 0;
        document.getElementById('email_destinatario').value = '';

        // Mostra o modal
        document.getElementById('cobrancaModal').style.display = 'flex';
    }

    // Adiciona o evento para preencher o e-mail
    document.getElementById('pessoa_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const email = selectedOption.getAttribute('data-email');
        document.getElementById('email_destinatario').value = email || '';
    });


    // Fecha modais ao clicar fora
    window.addEventListener('click', e => {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
     // --- NOVO: FUNÇÃO PARA ABRIR O MODAL DE REPETIÇÃO ---
    function openRepetirModal(id, responsavel) {
        document.getElementById('modalRepetirContaId').value = id;
        document.getElementById('modalRepetirResponsavel').innerText = responsavel;
        document.getElementById('repetirModal').style.display = 'flex';
    }

    // Fecha modais ao clicar fora
    window.addEventListener('click', e => {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (e.target === modal) {
                // Adiciona o novo modal à lista de verificação
                if (e.target.id !== 'repetirModal' && e.target.id !== 'deleteModal' && e.target.id !== 'addContaModal' && e.target.id !== 'cobrancaModal') {
                    modal.style.display = 'none';
                }
            }
        });
        // Lógica específica para fechar cada modal
        if (e.target == document.getElementById('repetirModal')) document.getElementById('repetirModal').style.display = 'none';
        if (e.target == document.getElementById('deleteModal')) document.getElementById('deleteModal').style.display = 'none';
        if (e.target == document.getElementById('addContaModal')) document.getElementById('addContaModal').style.display = 'none';
        if (e.target == document.getElementById('cobrancaModal')) document.getElementById('cobrancaModal').style.display = 'none';
        if (e.target == document.getElementById('exportar_contas_receber')) document.getElementById('exportar_contas_receber').style.display = 'none';
    });
</script>

</body>
</html>
<?php include('../includes/footer.php'); ?>