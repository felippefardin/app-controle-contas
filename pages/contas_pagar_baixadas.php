<?php
require_once '../includes/session_init.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

include('../includes/header.php');
include('../database.php');

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

$where = ["c.status='baixada'"];

if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id = {$mainUserId} OR id_criador = {$mainUserId}";
    $where[] = "c.usuario_id IN ({$subUsersQuery})";
}

$sql = "SELECT c.*, u.nome AS usuario_baixou
        FROM contas_pagar c
        LEFT JOIN usuarios u ON c.baixado_por = u.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.data_baixa DESC";

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

        /* Tabela */
        table { width: 100%; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #222; }
        tr:nth-child(even) { background-color: #2a2a2a; }
        tr:hover { background-color: #333; }
        
        .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 14px; font-weight: bold; text-decoration: none; color: white; cursor: pointer; transition: background-color 0.3s ease; margin: 2px; }
        .btn-excluir { background-color: #cc3333; }
        .btn-excluir:hover { background-color: #a02a2a; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
        .modal-content { background-color: #1f1f1f; padding: 25px 35px; border-radius: 10px; box-shadow: 0 0 20px rgba(255, 77, 77, 0.4); width: 90%; max-width: 500px; position: relative; border: 1px solid #333; text-align: center;}
        .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
        .btn-excluir-confirm { background-color: #cc3333; color: white; }
        .btn-excluir-confirm:hover { background-color: #a02a2a; }
        .btn-cancelar { background-color: #555; color: white; }
        .btn-cancelar:hover { background-color: #777; }
    </style>
</head>
<body>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
    unset($_SESSION['success_message']);
}
?>

<h2>Contas a Pagar - Baixadas</h2>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th><th>Categoria</th><th>Comprovante</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while($row = $result->fetch_assoc()){
        $categoria_nome = '-';
        if (!empty($row['categoria_id'])) {
            $stmtCat = $conn->prepare("SELECT nome FROM categorias WHERE id = ?");
            $stmtCat->bind_param("i", $row['categoria_id']);
            $stmtCat->execute();
            $resultCat = $stmtCat->get_result();
            if ($catRow = $resultCat->fetch_assoc()) {
                $categoria_nome = $catRow['nome'];
            }
            $stmtCat->close();
        }

        echo "<tr>";
        echo "<td data-label='Fornecedor'>".htmlspecialchars($row['fornecedor'])."</td>";
        echo "<td data-label='Vencimento'>".date('d/m/Y', strtotime($row['data_vencimento']))."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
        echo "<td data-label='Juros'>R$ ".number_format((float)($row['juros'] ?? 0),2,',','.')."</td>";
        echo "<td data-label='Forma de Pagamento'>".htmlspecialchars($row['forma_pagamento'] ?? '-')."</td>";
        echo "<td data-label='Data de Baixa'>".date('d/m/Y', strtotime($row['data_baixa']))."</td>";
        echo "<td data-label='Usuário'>".htmlspecialchars($row['usuario_baixou'] ?? '-')."</td>";
        echo "<td data-label='Categoria'>".htmlspecialchars($categoria_nome)."</td>";
        
        if (!empty($row['comprovante'])) {
            echo "<td data-label='Comprovante'><a href='../".htmlspecialchars($row['comprovante'])."' target='_blank' class='btn-action'>Ver</a></td>";
        } else {
            echo "<td data-label='Comprovante'>-</td>";
        }

        echo "<td data-label='Ações'>
                  <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($row['fornecedor']))."')\" class='btn-action btn-excluir'>Excluir</a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta baixada encontrada.</p>";
}
?>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        </div>
</div>

<script>
function openDeleteModal(id, fornecedor) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.innerHTML = `
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza de que deseja excluir permanentemente este registro?</p>
        <p><strong>Fornecedor:</strong> ${fornecedor}</p>
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <a href="../actions/excluir_conta_pagar.php?id=${id}&origem=baixadas" class="btn btn-excluir-confirm">Sim, Excluir</a>
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