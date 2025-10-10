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

$where = ["c.status='baixada'"];

if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    // Garante que o usuário principal e seus sub-usuários vejam as mesmas contas
    $subUsersQuery = "SELECT id FROM usuarios WHERE id = {$mainUserId} OR id_criador = {$mainUserId}";
    $where[] = "c.usuario_id IN ({$subUsersQuery})";
}

if (!empty($_GET['responsavel'])) $where[] = "responsavel LIKE '%" . $conn->real_escape_string($_GET['responsavel']) . "%'";
if (!empty($_GET['numero'])) $where[] = "numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['data_vencimento'])) $where[] = "data_vencimento='" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

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
        /* Seus estilos existentes */
        body { background-color:#121212; color:#eee; font-family:Arial,sans-serif; margin:0; padding:20px; }
        h2, h3 { text-align:center; color:#00bfff; margin-bottom: 20px; }
        table { width:100%; border-collapse:collapse; background:#1f1f1f; border-radius:8px; overflow:hidden; margin-top:10px; }
        th, td { padding:12px 10px; border-bottom:1px solid #333; text-align:left; }
        th { background:#222; color:#00bfff; }
        a { color: #00bfff; text-decoration: none; }
        .btn-action.btn-excluir { color: #ff4d4d; }

        /* Estilos para o modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7); justify-content: center; align-items: center; }
        .modal-content { background-color: #1f1f1f; padding: 25px 35px; border-radius: 10px; box-shadow: 0 0 20px rgba(255, 77, 77, 0.4); width: 90%; max-width: 500px; position: relative; border: 1px solid #333; text-align: center;}
        .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
        .btn-excluir-confirm { background-color: #cc3333; color: white; }
        .btn-excluir-confirm:hover { background-color: #a02a2a; }
        .btn-cancelar { background-color: #555; color: white; }
        .btn-cancelar:hover { background-color: #777; }
    </style>
</head>
<body>

<h2>Contas a Pagar - Baixadas</h2>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    // Adicionada a coluna "Ações"
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while($row = $result->fetch_assoc()){
        echo "<tr>";
        echo "<td data-label='Fornecedor'>".htmlspecialchars($row['fornecedor'])."</td>";
        echo "<td data-label='Vencimento'>".date('d/m/Y', strtotime($row['data_vencimento']))."</td>";
        echo "<td data-label='Número'>".htmlspecialchars($row['numero'])."</td>";
        echo "<td data-label='Valor'>R$ ".number_format($row['valor'], 2, ',', '.')."</td>";
        echo "<td data-label='Juros'>R$ ".number_format($row['juros'] ?? 0, 2, ',', '.')."</td>";
        echo "<td data-label='Forma de Pagamento'>".htmlspecialchars($row['forma_pagamento'] ?? '-')."</td>";
        echo "<td data-label='Data de Baixa'>".($row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-')."</td>";
        echo "<td data-label='Usuário'>".htmlspecialchars($row['usuario_baixou'] ?? '-')."</td>";

        // Adicionado o botão/link de exclusão
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
// Função para abrir e configurar o modal de exclusão
function openDeleteModal(id, fornecedor) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');
    
    // Conteúdo do modal
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

// Evento para fechar o modal ao clicar fora dele
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