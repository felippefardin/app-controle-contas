<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php");
    exit();
}
$conn = getTenantConnection();
$usuarioId = $_SESSION['usuario_id'];

include('../includes/header.php');

$where = ["cr.status='baixada'", "cr.usuario_id = " . intval($usuarioId)];

if (!empty($_GET['cliente'])) {
    $where[] = "pf.nome LIKE '%" . $conn->real_escape_string($_GET['cliente']) . "%'";
}
if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $where[] = "cr.data_vencimento BETWEEN '" . $conn->real_escape_string($_GET['data_inicio']) . "' AND '" . $conn->real_escape_string($_GET['data_fim']) . "'";
}

$sql = "SELECT cr.*, c.nome as nome_categoria, pf.nome as nome_pessoa, u.nome as nome_quem_baixou
        FROM contas_receber AS cr
        LEFT JOIN categorias AS c ON cr.id_categoria = c.id
        LEFT JOIN pessoas_fornecedores AS pf ON cr.id_pessoa_fornecedor = pf.id
        LEFT JOIN usuarios AS u ON cr.baixado_por = u.id 
        WHERE " . implode(" AND ", $where) . "
        ORDER BY cr.data_baixa DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Contas Recebidas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Mesmos estilos de contas_receber.php */
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #00bfff; }
    .success-message { background-color: #27ae60; color: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
    
    form.search-form { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 25px; }
    input { padding: 10px; background: #333; border: 1px solid #444; color: #eee; border-radius: 5px; }
    button { padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: bold; background-color: #27ae60; color: white; }
    
    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }

    .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; text-decoration: none; color: white; cursor: pointer; margin: 2px; }
    .btn-excluir { background-color: #cc3333; }
    .btn-comprovante { background-color: #f39c12; }
    .btn-estornar { background-color: #3498db; }
    
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; text-align: center; position: relative; }
    .close-btn { position: absolute; top: 10px; right: 20px; font-size: 28px; cursor: pointer; color: #aaa; }
  </style>
</head>
<body>

<?php
if (isset($_SESSION['success_message'])) echo "<div class='success-message'>{$_SESSION['success_message']}</div>";
if (isset($_SESSION['error_message'])) echo "<div class='success-message' style='background-color:#cc3333;'>{$_SESSION['error_message']}</div>";
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<h2>Contas Recebidas (Baixadas)</h2>

<form class="search-form" method="GET">
  <input type="text" name="cliente" placeholder="Cliente" value="<?= htmlspecialchars($_GET['cliente'] ?? '') ?>">
  <input type="date" name="data_inicio" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
  <input type="date" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
  <button type="submit">Buscar</button>
</form>

<table>
    <thead><tr>
        <th>Cliente</th>
        <th>Número</th>
        <th>Descrição</th>
        <th>Valor</th>
        <th>Recebido Por</th>
        <th>Data Receb.</th>
        <th>Categoria</th>
        <th>Comprovante</th>
        <th>Ações</th>
    </tr></thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0):
        while($row = $result->fetch_assoc()):
            $data_baixa = $row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-';
            $quemBaixou = !empty($row['nome_quem_baixou']) ? $row['nome_quem_baixou'] : 'Sistema/N/D';
    ?>
        <tr>
            <td><?= htmlspecialchars($row['nome_pessoa'] ?? 'N/D') ?></td>
            <td><?= htmlspecialchars($row['numero'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['descricao'] ?? '') ?></td>
            <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($quemBaixou) ?></td>
            <td><?= $data_baixa ?></td>
            <td><?= htmlspecialchars($row['nome_categoria'] ?? '-') ?></td>
            <td>
                <?= !empty($row['comprovante']) ? "<a href='../{$row['comprovante']}' target='_blank' class='btn-action btn-comprovante'>Ver</a>" : '--' ?>
            </td>
            <td>
                <div style='display:flex; gap:5px;'>
                    <a href='#' onclick="openEstornarModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nome_pessoa'])) ?>'); return false;" class='btn-action btn-estornar'><i class='fa-solid fa-undo'></i> Estornar</a>
                    
                    <a href='#' onclick="openDeleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nome_pessoa'])) ?>'); return false;" class='btn-action btn-excluir'><i class='fa-solid fa-trash'></i> Excluir</a>
                </div>
            </td>
        </tr>
    <?php endwhile; else: ?>
        <tr><td colspan="9" style="text-align:center;">Nenhuma conta recebida.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
      <h3>Confirmar Exclusão</h3>
      <p>Deseja excluir este registro de recebimento?</p>
      <p><strong>Cliente:</strong> <span id="delete-nome"></span></p>
      <div style="margin-top: 20px;">
        <a id="btn-confirm-delete" href="#" class='btn-action btn-excluir' style='padding: 10px 20px; font-size:16px;'>Sim, Excluir</a>
        <button onclick="document.getElementById('deleteModal').style.display='none'" class='btn-action' style='background-color: #555; padding: 10px 20px; font-size:16px; border:none;'>Cancelar</button>
      </div>
    </div>
</div>

<div id="estornarModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="document.getElementById('estornarModal').style.display='none'">&times;</span>
      <h3>Confirmar Estorno</h3>
      <p>Tem certeza que deseja estornar o recebimento de <b id="estornar-nome"></b>?</p>
      <p style="color: #aaa; font-size: 0.9em;">A conta voltará para a lista de <strong>Contas a Receber (Pendentes)</strong>.</p>
      
      <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
        <a id="btn-confirm-estorno" href="#" class='btn-action btn-estornar' style='padding: 10px 20px; font-size:16px; text-decoration:none;'>Sim, Estornar</a>
        <button onclick="document.getElementById('estornarModal').style.display='none'" class='btn-action' style='background-color: #555; padding: 10px 20px; font-size:16px; border:none;'>Cancelar</button>
      </div>
    </div>
</div>

<script>
function openDeleteModal(id, nome) {
    document.getElementById('delete-nome').innerText = nome;
    document.getElementById('btn-confirm-delete').href = "../actions/excluir_conta_receber.php?id=" + id + "&redirect=baixadas";
    document.getElementById('deleteModal').style.display = 'flex';
}

// ✅ Função para abrir o modal de estorno
function openEstornarModal(id, nome) {
    document.getElementById('estornar-nome').innerText = nome;
    document.getElementById('btn-confirm-estorno').href = "../actions/estornar_conta_receber.php?id=" + id;
    document.getElementById('estornarModal').style.display = 'flex';
}

// Fecha qualquer modal ao clicar fora
window.onclick = function(e) { 
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
};
</script>
<?php include('../includes/footer.php'); ?>
</body>
</html>