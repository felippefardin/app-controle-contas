<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Monta filtros SQL
$where = ["cr.status = 'baixada'"];

if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id = {$mainUserId} OR id_criador = {$mainUserId}";
    $where[] = "(cr.usuario_id IN ({$subUsersQuery}))";
}

if (!empty($_GET['responsavel'])) $where[] = "cr.responsavel LIKE '%" . $conn->real_escape_string($_GET['responsavel']) . "%'";
if (!empty($_GET['numero'])) $where[] = "cr.numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['data_vencimento'])) $where[] = "cr.data_vencimento = '" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

$sql = "SELECT cr.*, u.nome AS baixado_por_nome 
        FROM contas_receber cr 
        LEFT JOIN usuarios u ON cr.baixado_por_usuario_id = u.id 
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY cr.data_baixa DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Erro na consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Contas a Receber Baixadas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Seus estilos CSS originais com adições para o modal */
        * { box-sizing: border-box; }
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0; padding: 20px;
        }
        h2, h3 { text-align: center; color: #00bfff; }
        p { text-align: center; margin-top: 20px;}
        .success-message {
            background-color: #27ae60; color: white; padding: 15px; margin-bottom: 20px;
            border-radius: 5px; text-align: center; position: relative; font-weight: bold;
        }
        .close-msg-btn {
            position: absolute; top: 50%; right: 15px;
            transform: translateY(-50%); font-size: 22px;
            line-height: 1; cursor: pointer; transition: color 0.2s;
        }
        .close-msg-btn:hover { color: #ddd; }
        
        table { width: 100%;  background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #222; }
        tr:nth-child(even) { background-color: #2a2a2a; }
        tr:hover { background-color: #333; }
        
        .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 14px; font-weight: bold; text-decoration: none; color: white; cursor: pointer; transition: background-color 0.3s ease; margin: 2px; }
        .btn-excluir { background-color: #cc3333; }
        .btn-excluir:hover { background-color: #a02a2a; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
        .modal-content { background-color: #1f1f1f; padding: 25px 35px; border-radius: 10px; box-shadow: 0 0 20px rgba(255, 77, 77, 0.4); width: 90%; max-width: 500px; position: relative; border: 1px solid #333; text-align: center;}
        .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
        .btn-excluir-confirm { background-color: #cc3333; color: white; }
        .btn-excluir-confirm:hover { background-color: #a02a2a; }
        .btn-cancelar { background-color: #555; color: white; }
        .btn-cancelar:hover { background-color: #777; }

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

<h2>Contas a Receber Baixadas</h2>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    // --- CABEÇALHO ATUALIZADO ---
    echo "<thead><tr><th>Responsável</th><th>Vencimento</th><th>Data Baixa</th><th>Valor</th><th>Baixado por</th><th>Comprovante</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while($row = $result->fetch_assoc()){
        echo "<tr>";
        echo "<td data-label='Responsável'>".htmlspecialchars($row['responsavel'])."</td>";
        echo "<td data-label='Vencimento'>".($row['data_vencimento'] ? date('d/m/Y', strtotime($row['data_vencimento'])) : '-')."</td>";
        echo "<td data-label='Data Baixa'>".($row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-')."</td>";
        echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
        echo "<td data-label='Baixado por'>".htmlspecialchars($row['baixado_por_nome'] ?? 'N/A')."</td>";
        
        // --- ADICIONADO CAMPO COMPROVANTE ---
        if (!empty($row['comprovante'])) {
            echo "<td data-label='Comprovante'><a href='../".htmlspecialchars($row['comprovante'])."' target='_blank' class='btn-action'>Ver</a></td>";
        } else {
            echo "<td data-label='Comprovante'>-</td>";
        }

        // --- BOTÃO EXCLUIR ADICIONADO ---
        echo "<td data-label='Ações'>
                  <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($row['responsavel']))."')\" class='btn-action btn-excluir'>Excluir</a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta a receber baixada encontrada.</p>";
}
?>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        </div>
</div>

<script>
function openDeleteModal(id, responsavel) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.innerHTML = `
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza de que deseja excluir permanentemente este registro?</p>
        <p><strong>Responsável:</strong> ${responsavel}</p>
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <a href="../actions/excluir_conta_receber.php?id=${id}&origem=baixadas" class="btn btn-excluir-confirm">Sim, Excluir</a>
            <button type="button" class="btn btn-cancelar" onclick="document.getElementById('deleteModal').style.display='none'">Cancelar</button>
        </div>
    `;

    modal.style.display = 'flex';
}

window.addEventListener('click', e => {
    const deleteModal = document.getElementById('deleteModal');
    if (e.target === deleteModal) {
        deleteModal.style.display = 'none';
    }
});
</script>

</body>
</html>
<?php include('../includes/footer.php'); ?>