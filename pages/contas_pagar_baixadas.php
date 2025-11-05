<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Inclui o novo arquivo de banco de dados

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

// ✅ 3. SIMPLIFICA A QUERY PARA O MODELO SAAS E ADICIONA FILTROS
$where = ["c.status='baixada'"];
// No modelo SaaS, cada usuário só pode ver seus próprios dados.
$where[] = "c.usuario_id = " . intval($usuarioId);

// Filtros de pesquisa
$fornecedor_search = $_GET['fornecedor'] ?? '';
$numero_search = $_GET['numero'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

if (!empty($fornecedor_search)) {
    $where[] = "(c.fornecedor LIKE '%" . $conn->real_escape_string($fornecedor_search) . "%' OR pf.nome LIKE '%" . $conn->real_escape_string($fornecedor_search) . "%')";
}
if (!empty($numero_search)) {
    $where[] = "c.numero LIKE '%" . $conn->real_escape_string($numero_search) . "%'";
}
if (!empty($data_inicio) && !empty($data_fim)) {
    $where[] = "c.data_baixa BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
}


$sql = "SELECT c.*, u.nome AS usuario_baixou, pf.nome AS nome_pessoa_fornecedor
        FROM contas_pagar c
        LEFT JOIN usuarios u ON c.baixado_por = u.id
        LEFT JOIN pessoas_fornecedores pf ON c.id_pessoa_fornecedor = pf.id
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

<form class="search-form" method="GET" action="">
  <input type="text" name="fornecedor" placeholder="Fornecedor" value="<?php echo htmlspecialchars($fornecedor_search); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($numero_search); ?>">
  <input type="date" name="data_inicio" placeholder="Data Baixa Início" value="<?php echo htmlspecialchars($data_inicio); ?>">
  <input type="date" name="data_fim" placeholder="Data Baixa Fim" value="<?php echo htmlspecialchars($data_fim); ?>">
  <button type="submit"><i class="fa fa-search"></i> Buscar</button>
  <a href="contas_pagar_baixadas.php" class="clear-filters">Limpar</a>
</form>

<?php
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>Fornecedor</th><th>Descrição</th><th>Vencimento</th><th>Número</th><th>Valor</th><th>Juros</th><th>Forma Pag.</th><th>Data Baixa</th><th>Usuário</th><th>Categoria</th><th>Comprovante</th><th>Ações</th></tr></thead>";
    echo "<tbody>";
    while($row = $result->fetch_assoc()){
        $categoria_nome = '-';
        if (!empty($row['id_categoria'])) {
            $stmtCat = $conn->prepare("SELECT nome FROM categorias WHERE id = ?");
            $stmtCat->bind_param("i", $row['id_categoria']);
            $stmtCat->execute();
            $resultCat = $stmtCat->get_result();
            if ($catRow = $resultCat->fetch_assoc()) {
                $categoria_nome = $catRow['nome'];
            }
            $stmtCat->close();
        }

        $fornecedorDisplay = !empty($row['nome_pessoa_fornecedor']) ? $row['nome_pessoa_fornecedor'] : ($row['fornecedor'] ?? '');

        echo "<tr>";
        echo "<td data-label='Fornecedor'>".htmlspecialchars($fornecedorDisplay)."</td>";
        echo "<td data-label='Descrição'>".htmlspecialchars($row['descricao'] ?? '-')."</td>";
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
                  <a href='#' onclick=\"openDeleteModal({$row['id']}, '".htmlspecialchars(addslashes($fornecedorDisplay))."')\" class='btn-action btn-excluir'>Excluir</a>
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