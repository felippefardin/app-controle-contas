<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include('../includes/header.php');
include('../database.php');



// ADICIONE ISTO: inicializa $conn se ainda não existir
if (!isset($conn)) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "app_controle_contas";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Contas a Receber Baixadas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    * { box-sizing: border-box; }
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }
    h2 {
      text-align: center;
      color: #00bfff;
      margin-bottom: 20px;
    }
    a { color: #00bfff; text-decoration: none; font-weight: bold; }
    a:hover { text-decoration: underline; }

    form.search-form {
      max-width: 900px; margin: 0 auto 25px auto;
      display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;
    }
    form.search-form input[type="text"],
    form.search-form input[type="number"],
    form.search-form input[type="date"]{
      padding: 10px; font-size: 16px;
      border-radius: 5px; border: 1px solid #444;
      background-color: #333; color: #eee; min-width: 180px;
    }
    form.search-form input::placeholder { color: #aaa; }

    /* Botões padrão */
    .btn-primary {
      background-color: #27ae60; color: #fff; border: none;
      padding: 10px 22px; font-weight: bold; border-radius: 6px;
      cursor: pointer; transition: background-color .3s ease;
      display: inline-flex; align-items: center; justify-content: center;
      text-decoration: none; min-width: 120px; text-align: center;
    }
    .btn-primary:hover { background-color: #1e874b; }

    .btn-danger {
      background-color: #cc3333; color: #fff; border: none;
      padding: 10px 22px; font-weight: bold; border-radius: 6px;
      cursor: pointer; transition: background-color .3s ease;
      display: inline-flex; align-items: center; justify-content: center;
      text-decoration: none; min-width: 120px; text-align: center;
    }
    .btn-danger:hover { background-color: #a02a2a; }

    form.search-form button { composes: btn-primary; }
    form.search-form a.clear-filters { composes: btn-danger; }

    /* Fallback quando composes não é suportado */
    form.search-form button { background-color:#27ae60; }
    form.search-form button:hover { background-color:#1e874b; }
    form.search-form a.clear-filters { background-color:#cc3333; color:#fff; }
    form.search-form a.clear-filters:hover { background-color:#a02a2a; }

    .export-buttons {
      display:flex; justify-content:center; gap:12px; margin:20px 0; flex-wrap:wrap;
    }

    table {
      width:100%; border-collapse:collapse; background-color:#1f1f1f;
      border-radius:8px; overflow:hidden; margin-top:10px;
    }
    th, td { padding:12px 10px; text-align:left; border-bottom:1px solid #333; }
    th { background-color:#222; color:#00bfff; }
    tr:nth-child(even){ background-color:#262626; }
    tr:hover{ background-color:#333; }

    .btn-excluir {
      background:none; border:none; color:#cc3333; cursor:pointer;
      padding:0; font-weight:bold; font-size:1em; transition:color .3s ease;
    }
    .btn-excluir:hover { color:#a02a2a; text-decoration:underline; }

    @media (max-width: 768px){
      form.search-form { flex-direction:column; align-items:stretch; }
      form.search-form button,
      form.search-form a.clear-filters{ width:100%; min-width:auto; }
      table, thead, tbody, th, td, tr { display:block; }
      th { display:none; }
      tr { margin-bottom:20px; border:1px solid #333; border-radius:8px; padding:10px; }
      td { position:relative; padding-left:50%; margin-bottom:10px; }
      td::before {
        content: attr(data-label); position:absolute; left:10px; top:12px;
        font-weight:bold; color:#aaa;
      }
    }
    /* Formulário de Busca */
    form.search-form {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin-bottom: 25px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    form.search-form input[type="text"],
    form.search-form input[type="date"] {
      padding: 10px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #444;
      background-color: #333;
      color: #eee;
      min-width: 180px;
    }
    form.search-form input::placeholder {
      color: #aaa;
    }
    form.search-form button {
      background-color: #27ae60;
      color: white;
      border: none;
      padding: 10px 22px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      min-width: 100px;
    }
    form.search-form button:hover {
      background-color: #1e874b;
    }
    form.search-form a.clear-filters {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background-color: #cc3333;
      color: white;
      padding: 10px 18px;
      font-weight: bold;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      min-width: 100px;
      transition: background-color 0.3s ease;
    }
    form.search-form a.clear-filters:hover {
      background-color: #a02a2a;
    }


    /* Modal base */
    .modal {
      display:none; position:fixed; z-index:999; padding-top:100px;
      left:0; top:0; width:100%; height:100%; overflow:auto;
      background-color: rgba(0,0,0,0.4);
    }
    /* Cores padrão (dark) do conteúdo do modal */
    .modal-content {
      background-color:#1f1f1f; color:#eee; margin:auto; padding:24px;
      border:1px solid #333; width:100%; max-width:520px; border-radius:10px;
      box-shadow:0 8px 24px rgba(0,0,0,.5);
    }
    .modal-content h2, .modal-content h3 { color:#00bfff; margin-top:0; }
    .modal-content label { display:block; margin-top:10px; font-weight:bold; color:#ddd; }
    .modal-content input, .modal-content select {
      width:100%; padding:10px; margin-top:6px; border-radius:6px;
      border:1px solid #444; background:#2b2b2b; color:#eee;
    }

    .modal-actions {
      display:flex; gap:10px; justify-content:flex-end; margin-top:18px; flex-wrap:wrap;
    }

    .close {
      float:right; font-size:24px; font-weight:bold; cursor:pointer; color:#bbb;
    }
    .close:hover { color:#fff; }
  </style>
</head>
<body>
  <?php if (!empty($_GET['msg'])): ?>
  <div id="msg-sucesso" style="
    background-color:#27ae60;color:#fff;padding:15px 20px;text-align:center;
    font-weight:bold;border-radius:5px;margin-bottom:20px;max-width:700px;margin-left:auto;margin-right:auto;">
    <?php echo htmlspecialchars($_GET['msg']); ?>
  </div>
  <?php endif; ?>

  <h2>Contas a Receber Baixadas</h2>

 <!-- Formulário de Busca -->
<form class="search-form" method="GET" action="">
  <input type="text" name="responsavel" placeholder="Responsável" value="<?php echo htmlspecialchars($_GET['responsavel'] ?? ''); ?>">
  <input type="text" name="numero" placeholder="Número" value="<?php echo htmlspecialchars($_GET['numero'] ?? ''); ?>">
  <input type="date" name="data_vencimento" placeholder="Data Vencimento" value="<?php echo htmlspecialchars($_GET['data_vencimento'] ?? ''); ?>">
  <button type="submit">Buscar</button>
  <a href="contas_receber_baixadas.php" class="clear-filters">Limpar</a>
</form>

  <!-- Botão para abrir o modal Exportar -->
  <div class="export-buttons">
    <button type="button" class="btn-primary" onclick="document.getElementById('export_receber_baixadas').style.display='block'">Exportar Baixadas</button>
  </div>

  <?php
  $where = ["cr.status = 'baixada'"];
  if (!empty($_GET['responsavel'])) $where[] = "cr.responsavel LIKE '%" . $conn->real_escape_string($_GET['responsavel']) . "%'";
  if (!empty($_GET['numero'])) $where[] = "cr.numero LIKE '%" . $conn->real_escape_string($_GET['numero']) . "%'";
  if (!empty($_GET['valor'])) $where[] = "cr.valor = " . floatval($_GET['valor']);
  if (!empty($_GET['data_baixa'])) $where[] = "cr.data_baixa = '" . $conn->real_escape_string($_GET['data_baixa']) . "'";

  $sql = "SELECT cr.*, u.nome AS usuario 
          FROM contas_receber cr
          LEFT JOIN usuarios u ON cr.baixado_por = u.id
          WHERE " . implode(" AND ", $where) . " 
          ORDER BY cr.data_baixa DESC";

  $result = $conn->query($sql);

  if (!$result) {
      echo "<p>Erro na consulta: " . $conn->error . "</p>";
  } else {
      if ($result->num_rows > 0) {
          echo "<table>";
          echo "<thead><tr>
              <th>Responsável</th>
              <th>Número</th>
              <th>Valor</th>
              <th>Forma</th>
              <th>Data de Baixa</th>
              <th>Baixado por</th>
              <th>Ações</th>
            </tr></thead>";
          echo "<tbody>";
          while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td data-label='Responsável'>" . htmlspecialchars($row['responsavel'] ?? '') . "</td>";
              echo "<td data-label='Número'>" . htmlspecialchars($row['numero'] ?? '') . "</td>";
              echo "<td data-label='Valor'>R$ " . number_format(floatval($row['valor'] ?? 0), 2, ',', '.') . "</td>";
              echo "<td data-label='Forma'>" . htmlspecialchars($row['forma_pagamento'] ?? '') . "</td>";

              $data_baixa = $row['data_baixa'] ?? null;
              echo "<td data-label='Data de Baixa'>" . ($data_baixa ? date('d/m/Y', strtotime($data_baixa)) : '-') . "</td>";

              echo "<td data-label='Baixado por'>" . htmlspecialchars($row['usuario'] ?? '') . "</td>";

              echo "<td data-label='Ações'>";
              echo "<button class='btn-excluir' data-id='" . htmlspecialchars($row['id'] ?? '') . "'>Excluir</button>";
              echo "</td>";

              echo "</tr>";
          }
          echo "</tbody>";
          echo "</table>";
      } else {
          echo "<p>Nenhuma conta baixada encontrada.</p>";
      }
  }
  ?>

  <!-- Modal Exportar Baixadas -->
  <div id="export_receber_baixadas" class="modal" aria-hidden="true">
    <div class="modal-content">
      <span class="close" onclick="document.getElementById('export_receber_baixadas').style.display='none'">&times;</span>
      <h2>Exportar Contas Baixadas</h2>

      <form action="../pages/export_receber.php" method="get">
        <label for="tipo">Formato:</label>
        <select name="tipo" id="tipo" required>
          <option value="pdf">PDF</option>
          <option value="csv">CSV</option>
          <option value="excel">Excel</option>
        </select>

        <input type="hidden" name="status" value="baixada">

        <label for="data_inicio">Data Início:</label>
        <input type="date" name="data_inicio" id="data_inicio" required />

        <label for="data_fim">Data Fim:</label>
        <input type="date" name="data_fim" id="data_fim" required />

        <div class="modal-actions">
          <button type="button" class="btn-danger" onclick="document.getElementById('export_receber_baixadas').style.display='none'">Cancelar</button>
          <button type="submit" class="btn-primary">Exportar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Exclusão -->
  <div id="deleteModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <span class="close" onclick="closeDeleteModal()">&times;</span>
      <h3>Confirmar Exclusão</h3>
      <p>Tem certeza que deseja excluir esta conta baixada? Essa ação não poderá ser desfeita.</p>
      <div class="modal-actions">
        <button id="confirmDeleteBtn" class="btn-primary">Sim, excluir</button>
        <button onclick="closeDeleteModal()" class="btn-danger">Cancelar</button>
      </div>
    </div>
  </div>

  <script>
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let deleteId = null;

    document.querySelectorAll('.btn-excluir').forEach(button => {
      button.addEventListener('click', () => {
        deleteId = button.getAttribute('data-id');
        deleteModal.style.display = 'block';
      });
    });

    confirmDeleteBtn.addEventListener('click', () => {
      if (deleteId) {
        window.location.href = `../actions/excluir_conta_receber.php?id=${deleteId}`;
      }
    });

    function closeDeleteModal() {
      deleteModal.style.display = 'none';
      deleteId = null;
    }

    // Fecha modais ao clicar fora
    window.addEventListener('click', event => {
      const exportModal = document.getElementById('export_receber_baixadas');
      if (event.target === deleteModal) closeDeleteModal();
      if (event.target === exportModal) exportModal.style.display = 'none';
    });

    // ESC fecha modais
    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const exportModal = document.getElementById('export_receber_baixadas');
        if (deleteModal.style.display === 'block') closeDeleteModal();
        if (exportModal.style.display === 'block') exportModal.style.display = 'none';
      }
    });

    // Mensagem de sucesso some
    window.addEventListener('DOMContentLoaded', () => {
      const msg = document.getElementById('msg-sucesso');
      if (msg) {
        setTimeout(() => {
          msg.style.transition = 'opacity .5s ease';
          msg.style.opacity = '0';
          setTimeout(() => { msg.remove(); }, 500);
        }, 2000);
      }
    });
  </script>
</body>
</html>

<?php include('../includes/footer.php'); ?>
