<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Incluído no início

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO CORRETA
$usuario_logado = $_SESSION['usuario_logado'];
$usuarioId = $usuario_logado['id'];
$perfil = $usuario_logado['nivel_acesso'];

include('../includes/header.php');

// ✅ 3. SIMPLIFICA A QUERY PARA O MODELO SAAS E FILTROS
$where = ["cr.status = 'baixada'"];
// Filtra apenas pelo ID do usuário logado
$where[] = "cr.usuario_id = " . intval($usuarioId);

// Parâmetros de busca
$responsavel_search = $_GET['responsavel'] ?? '';
$numero_search = $_GET['numero'] ?? '';
$data_vencimento_search = $_GET['data_vencimento'] ?? '';

if (!empty($responsavel_search)) $where[] = "(cr.responsavel LIKE '%" . $conn->real_escape_string($responsavel_search) . "%' OR pf.nome LIKE '%" . $conn->real_escape_string($responsavel_search) . "%')";
if (!empty($numero_search)) $where[] = "cr.numero LIKE '%" . $conn->real_escape_string($numero_search) . "%'";
if (!empty($data_vencimento_search)) $where[] = "cr.data_vencimento = '" . $conn->real_escape_string($data_vencimento_search) . "'";

// --- SQL CORRIGIDO ---
$sql = "SELECT cr.*,
               u.nome AS baixado_por_nome,
               pf.nome AS nome_pessoa_fornecedor,
               cat.nome AS categoria_nome
        FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por = u.id
        LEFT JOIN pessoas_fornecedores pf ON cr.id_pessoa_fornecedor = pf.id
        LEFT JOIN categorias cat ON cr.id_categoria = cat.id
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

        /* --- Barra de Busca --- */
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
        /* --- Fim Barra de Busca --- */

        table { width: 100%;  background-color: #1f1f1f; border-radius: 8px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #333; }
        th { background-color: #222; }
        tr:nth-child(even) { background-color: #2a2a2a; }
        tr:hover { background-color: #333; }

        .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 14px; font-weight: bold; text-decoration: none; color: white; cursor: pointer; transition: background-color 0.3s ease; margin: 2px; }
        .btn-excluir { background-color: #cc3333; }
        .btn-excluir:hover { background-color: #a02a2a; }
        .btn-estornar { background-color: #f0ad4e; } /* Cor do botão de estorno */
        .btn-estornar:hover { background-color: #df8a13; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
        .modal-content { background-color: #1f1f1f; padding: 25px 35px; border-radius: 10px; box-shadow: 0 0 20px rgba(255, 77, 77, 0.4); width: 90%; max-width: 500px; position: relative; border: 1px solid #333; text-align: center;}
        .btn { border: none; padding: 10px 22px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
        .btn-confirm { background-color: #f0ad4e; color: white; }
        .btn-confirm:hover { background-color: #df8a13; }
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
if (isset($_SESSION['error_message'])) {
    echo '<div class="success-message" style="background-color: #cc3333;">' . htmlspecialchars($_SESSION['error_message']) . '<span class="close-msg-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
    unset($_SESSION['error_message']);
}
?>

<h2>Contas a Receber Baixadas</h2>

<form class="search-form" method="GET" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?php echo htmlspecialchars($responsavel_search); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($numero_search); ?>">
  <input type="date" name="data_vencimento" placeholder="Data Vencimento" value="<?php echo htmlspecialchars($data_vencimento_search); ?>">
  <button type="submit"><i class="fa fa-search"></i> Buscar</button>
  <a href="contas_receber_baixadas.php" class="clear-filters">Limpar</a>
</form>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Responsável</th><th>Descrição</th><th>Vencimento</th><th>Data Baixa</th><th>Valor</th><th>Baixado por</th><th>Categoria</th><th>Comprovante</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while($row = $result->fetch_assoc()){
        $responsavelDisplay = !empty($row['nome_pessoa_fornecedor']) ? $row['nome_pessoa_fornecedor'] : ($row['responsavel'] ?? '');

        echo "<tr>";
        echo "<td data-label='Responsável'>".htmlspecialchars($responsavelDisplay)."</td>";
        echo "<td data-label='Descrição'>".htmlspecialchars($row['descricao'] ?? '-')."</td>";
        echo "<td data-label='Vencimento'>".($row['data_vencimento'] ? date('d/m/Y', strtotime($row['data_vencimento'])) : '-')."</td>";
        echo "<td data-label='Data Baixa'>".($row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '-')."</td>";
        echo "<td data-label='Valor'>R$ ".number_format((float)$row['valor'],2,',','.')."</td>";
        echo "<td data-label='Baixado por'>".htmlspecialchars($row['baixado_por_nome'] ?? 'N/A')."</td>";

        if (!empty($row['id_venda'])) {
            echo "<td data-label='Categoria'>#" . htmlspecialchars($row['id_venda']) . "</td>";
        } else {
            echo "<td data-label='Categoria'>" . htmlspecialchars($row['categoria_nome'] ?? 'N/A') . "</td>";
        }

        if (!empty($row['comprovante'])) {
            echo "<td data-label='Comprovante'><a href='../".htmlspecialchars($row['comprovante'])."' target='_blank' class='btn-action'>Ver</a></td>";
        } else {
            echo "<td data-label='Comprovante'>-</td>";
        }

        // --- BOTÕES DE AÇÃO ATUALIZADOS ---
        echo "<td data-label='Ações'>
                  <a href='#' onclick=\"openEstornarModal({$row['id']}, '".htmlspecialchars(addslashes($responsavelDisplay))."')\" class='btn-action btn-estornar'>Estornar</a>
                  <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($responsavelDisplay))."')\" class='btn-action btn-excluir'>Excluir</a>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>Nenhuma conta a receber baixada encontrada.</p>";
}
?>

<div id="deleteModal" class="modal">
    <div class="modal-content"></div>
</div>

<script>
// Abre o modal de confirmação para EXCLUIR
function openDeleteModal(id, responsavel) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');

    modalContent.innerHTML = `
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza de que deseja excluir permanentemente este registro?</p>
        <p><strong>Responsável:</strong> ${responsavel}</p>
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <a href="../actions/excluir_conta_receber.php?id=${id}&origem=baixadas" class="btn btn-excluir-confirm">Sim, Excluir</a>
            <button type="button" class="btn btn-cancelar" onclick="closeModal()">Cancelar</button>
        </div>
    `;

    modal.style.display = 'flex';
}

// Abre o modal de confirmação para ESTORNAR
function openEstornarModal(id, responsavel) {
    const modal = document.getElementById('deleteModal');
    const modalContent = modal.querySelector('.modal-content');

    modalContent.innerHTML = `
        <h3>Confirmar Estorno</h3>
        <p>Deseja realmente estornar esta conta? Ela voltará para a lista de contas a receber pendentes.</p>
        <p><strong>Responsável:</strong> ${responsavel}</p>
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <a href="../actions/estornar_conta_receber.php?id=${id}" class="btn btn-confirm">Sim, Estornar</a>
            <button type="button" class="btn btn-cancelar" onclick="closeModal()">Cancelar</button>
        </div>
    `;

    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.addEventListener('click', e => {
    const deleteModal = document.getElementById('deleteModal');
    if (e.target === deleteModal) {
        closeModal();
    }
});
</script>

</body>
</html>
<?php include('../includes/footer.php'); ?>