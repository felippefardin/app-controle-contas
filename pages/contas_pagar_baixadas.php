<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ✅ 1. VERIFICA LOGIN E PERMISSÃO
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.php");
    exit();
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. DADOS DO USUÁRIO
$usuarioId = $_SESSION['usuario_id'];
$perfil = $_SESSION['nivel_acesso'];

include('../includes/header.php');

// ✅ 3. QUERY ATUALIZADA (BAIXADAS)
$where = ["cp.status='baixada'"];
$where[] = "cp.usuario_id = " . intval($usuarioId);

if (!empty($_GET['fornecedor'])) {
    $where[] = "cp.fornecedor LIKE '%" . $conn->real_escape_string($_GET['fornecedor']) . "%'";
}

if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $where[] = "cp.data_vencimento BETWEEN '" . $conn->real_escape_string($_GET['data_inicio']) . "' AND '" . $conn->real_escape_string($_GET['data_fim']) . "'";
}

$sql = "SELECT cp.*, c.nome as nome_categoria, pf.nome as nome_pessoa_fornecedor, u.nome as nome_quem_baixou
        FROM contas_pagar AS cp
        LEFT JOIN categorias AS c ON cp.id_categoria = c.id
        LEFT JOIN pessoas_fornecedores AS pf ON cp.id_pessoa_fornecedor = pf.id
        LEFT JOIN usuarios AS u ON cp.baixado_por = u.id 
        WHERE " . implode(" AND ", $where) . "
        ORDER BY cp.data_baixa DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Contas a Pagar - Baixadas</title>
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
    h2 { text-align: center; color: #00bfff; }
    
    .success-message {
      background-color: #27ae60;
      color: white; padding: 15px; margin-bottom: 20px;
      border-radius: 5px; text-align: center;
      position: relative; font-weight: bold;
    }
    .close-msg-btn {
      position: absolute; top: 50%; right: 15px;
      transform: translateY(-50%); font-size: 22px;
      cursor: pointer;
    }

    form.search-form {
      display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;
      margin-bottom: 25px; max-width: 900px; margin-left:auto; margin-right:auto;
    }
    form.search-form input {
      padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #444;
      background-color: #333; color: #eee; min-width: 180px;
    }
    form.search-form button, form.search-form a.clear-filters {
      color: white; border: none; padding: 10px 22px; font-weight: bold;
      border-radius: 5px; cursor: pointer; text-decoration: none;
    }
    form.search-form button { background-color: #27ae60; }
    form.search-form a.clear-filters { background-color: #cc3333; }

    table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    tr:nth-child(even) { background-color: #2a2a2a; }
    tr:hover { background-color: #333; }

    .btn-action { 
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; 
        border-radius: 4px; font-size: 13px; font-weight: bold; 
        text-decoration: none; color: white; cursor: pointer; margin: 2px; 
    }
    .btn-excluir { background-color: #cc3333; }
    .btn-comprovante { background-color: #f39c12; }
    .btn-estornar { background-color: #3498db; }
    
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; text-align: center; position: relative; }
    .close-btn { position: absolute; top: 10px; right: 20px; font-size: 28px; cursor: pointer; color: #aaa; }
  </style>
</head>
<body>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="success-message" style="background-color: #cc3333;">' . htmlspecialchars($_SESSION['error_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
    unset($_SESSION['error_message']);
}
?>

<h2>Contas a Pagar - Baixadas</h2>

<form class="search-form" method="GET" action="">
  <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?php echo htmlspecialchars($_GET['fornecedor'] ?? ''); ?>">
  <input type="date" name="data_inicio" placeholder="Data Início" value="<?php echo htmlspecialchars($_GET['data_inicio'] ?? ''); ?>">
  <input type="date" name="data_fim" placeholder="Data Fim" value="<?php echo htmlspecialchars($_GET['data_fim'] ?? ''); ?>">
  <button type="submit"><i class="fa fa-search"></i> Buscar</button>
  <a href="contas_pagar_baixadas.php" class="clear-filters">Limpar</a>
</form>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr>
            <th>Fornecedor</th>
            <th>Número</th> <th>Descrição</th>
            <th>Valor</th>
            <th>Baixado Por</th>
            <th>Data Baixa</th>
            <th>Categoria</th>
            <th>Comprovante</th>
            <th>Ações</th>
          </tr></thead>";
    echo "<tbody>";
    
    while($row = $result->fetch_assoc()){
        $data_vencimento = $row['data_vencimento'] ?? null;
        $data_baixa = $row['data_baixa'] ?? null;
        
        $data_baixa_formatada = $data_baixa ? date('d/m/Y', strtotime($data_baixa)) : '-';
        $fornecedorDisplay = !empty($row['nome_pessoa_fornecedor']) ? $row['nome_pessoa_fornecedor'] : ($row['fornecedor'] ?? 'N/D');
        $quemBaixou = !empty($row['nome_quem_baixou']) ? $row['nome_quem_baixou'] : 'Sistema/N/D';

        echo "<tr>";
        echo "<td>".htmlspecialchars($fornecedorDisplay)."</td>";
        
        // ✅ Exibição do Número
        echo "<td>".htmlspecialchars($row['numero'] ?? '-')."</td>";
        
        echo "<td>".htmlspecialchars($row['descricao'] ?? '')."</td>";
        echo "<td>R$ ".number_format($row['valor'], 2, ',', '.')."</td>";
        echo "<td>".htmlspecialchars($quemBaixou)."</td>";
        echo "<td>".$data_baixa_formatada."</td>";
        echo "<td>".htmlspecialchars($row['nome_categoria'] ?? 'N/A')."</td>";
        
        $linkComprovante = '--';
        if (!empty($row['comprovante'])) {
            $linkComprovante = "<a href='../{$row['comprovante']}' target='_blank' class='btn-action btn-comprovante'><i class='fa fa-file'></i> Ver</a>";
        }
        echo "<td>{$linkComprovante}</td>";
        
        echo "<td>
            <div style='display:flex; gap:5px;'>
                <a href='../actions/estornar_conta_pagar.php?id={$row['id']}' class='btn-action btn-estornar' onclick=\"return confirm('Tem certeza que deseja estornar esta conta?');\"><i class='fa-solid fa-undo'></i> Estornar</a>
                <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($fornecedorDisplay))."'); return false;\" class='btn-action btn-excluir'><i class='fa-solid fa-trash'></i> Excluir</a>
            </div>
        </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    if (!$result) {
        echo "<p style='color: #ff6b6b;'>Erro na consulta: " . htmlspecialchars($conn->error) . "</p>";
    } else {
        echo "<p style='text-align:center;'>Nenhuma conta baixada encontrada.</p>";
    }
}
?>

<div id="deleteModal" class="modal">
    <div class="modal-content"></div>
</div>

<script>
function openDeleteModal(id, fornecedor) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');

    modalContent.innerHTML = `
      <span class="close-btn" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
      <h3>Confirmar Exclusão</h3>
      <p>Tem certeza de que deseja excluir esta conta baixada?</p>
      <p><strong>Fornecedor:</strong> ${fornecedor}</p>
      <div style="margin-top: 20px;">
        <a href="../actions/excluir_conta_pagar.php?id=${id}&redirect=baixadas" class='btn-action btn-excluir' style='padding: 10px 20px; font-size:16px;'>Sim, Excluir</a>
        <button onclick="document.getElementById('deleteModal').style.display='none'" class='btn-action' style='background-color: #555; padding: 10px 20px; font-size:16px; border:none;'>Cancelar</button>
      </div>
    `;
    
    modal.style.display = 'flex';
}

window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    if (event.target == deleteModal) { deleteModal.style.display = 'none'; }
};
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>