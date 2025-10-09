<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include('../includes/header.php');
include('../database.php');

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
// Assumindo que o id_criador está na sessão. Use 0 ou null como padrão.
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// --- CORREÇÃO: Especificado "c.status" para remover a ambiguidade ---
$where = ["c.status = 'baixada'"];

if ($perfil !== 'admin') {
    // Se id_criador for maior que 0, o usuário é secundário.
    // O ID principal é o id_criador. Caso contrário, é o próprio ID do usuário.
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;

    // Subconsulta para obter todos os IDs de usuários associados à conta principal
    $subUsersQuery = "SELECT id FROM usuarios WHERE id = {$mainUserId} OR id_criador = {$mainUserId}";

    // A cláusula WHERE agora inclui o ID do usuário principal e todos os seus usuários secundários
    $where[] = "(c.usuario_id IN ({$subUsersQuery}))";
}


if (!empty($_GET['fornecedor'])) $where[] = "fornecedor LIKE '%" . $conn->real_escape_string($_GET['fornecedor']) . "%'";
if (!empty($_GET['numero'])) $where[] = "numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
if (!empty($_GET['valor'])) $where[] = "valor = " . floatval($_GET['valor']);
if (!empty($_GET['data_vencimento'])) $where[] = "data_vencimento = '" . $conn->real_escape_string($_GET['data_vencimento']) . "'";

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    /* SEUS ESTILOS CSS PERMANECEM AQUI */
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h2, h3 { text-align: center; color: #00bfff; margin-bottom: 20px; }
    .action-buttons-group { display: flex; justify-content: center; gap: 12px; margin: 20px 0; flex-wrap: wrap; }
    .btn-export { background-color: #28a745; color: white; padding: 10px 14px; border:none; border-radius: 5px; cursor:pointer; font-weight:bold; }
    .btn-export:hover { background-color: #218838; }
    table { width: 100%; border-collapse: collapse; background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 10px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
    th { background-color: #222; color: #00bfff; }
    .btn-delete { background-color: transparent; border: none; color: #cc3333; cursor: pointer; font-weight: bold; text-decoration: underline; font-size: 1em; padding: 0; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 500px; position: relative; }
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content form { display: flex; flex-direction: column; gap: 15px; }
    .modal-content .form-group { display: flex; flex-direction: column; gap: 5px; }
    .modal-content label { font-weight: bold; }
    .modal-content input, .modal-content select, .modal-content button { padding: 10px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
    .modal-content button { background-color: #00bfff; cursor: pointer; font-weight: bold; }
    .modal-content button:hover { background-color: #0099cc; }
    
  </style>
</head>
<body>

<h2>Contas a Pagar - Baixadas</h2>

<?php
if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
} else {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma de Pagamento</th><th>Data de Baixa</th><th>Usuário</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td data-label='Fornecedor'>" . htmlspecialchars($row['fornecedor']) . "</td>";
        echo "<td data-label='Vencimento'>" . date('d/m/Y', strtotime($row['data_vencimento'])) . "</td>";
        echo "<td data-label='Número'>" . htmlspecialchars($row['numero']) . "</td>";
        echo "<td data-label='Valor'>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
        echo "<td data-label='Juros'>R$ " . number_format($row['juros'] ?? 0, 2, ',', '.') . "</td>";
        echo "<td data-label='Forma de Pagamento'>" . htmlspecialchars($row['forma_pagamento'] ?? '-') . "</td>";
        echo "<td data-label='Data de Baixa'>" . ($row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-') . "</td>";
        echo "<td data-label='Usuário'>" . htmlspecialchars($row['usuario_baixou'] ?? '-') . "</td>";
        echo "<td data-label='Ações'>
                <button class='btn-delete' onclick='openDeleteModal({$row['id']})'>Excluir</button>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>

<div id="deleteModal" class="modal" style="display: none;">
</div>
<script>
    function openDeleteModal(id) {
        // Sua função de abrir modal aqui
    }

    // Lógica para fechar modal de exportação
    window.onclick = function(event) {
        const exportModal = document.getElementById('exportModal');
        if (event.target == exportModal) {
            exportModal.style.display = 'none';
        }
    };
</script>

</body>
</html>
<?php include('../includes/footer.php'); ?>