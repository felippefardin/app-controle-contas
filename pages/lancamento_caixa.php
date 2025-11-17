<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao conectar ao banco de dados do cliente.");
}

// ID do usuário logado
$id_usuario = $_SESSION['usuario_id'];

// 2. AJUSTA A CONSULTA SQL PARA FILTRAR PELO USUÁRIO
$lancamentos = [];
$sql = "SELECT id, data, valor FROM caixa_diario WHERE usuario_id = ? ORDER BY data DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $lancamentos = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

include('../includes/header.php');
?>

<style>
    body {
        background-color: #121212;
        color: #eee;
    }
    .container {
        width: 95%;
        max-width: 1200px;
        margin: 20px auto;
        background-color: #222;
        padding: 25px;
        border-radius: 8px;
    }
    h2, h3 {
        color: #00bfff;
    }
    .alert {
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 20px;
        color: white;
    }
    .alert-success { background-color: #28a745; }
    .alert-danger { background-color: #cc4444; }
    form, .search-container {
        background-color: #1f1f1f;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; }
    .form-control, .form-control-date {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #444;
        background-color: #2c2c2c;
        color: #eee;
    }
    .btn-primary { background-color: #00bfff; padding: 10px 18px; border: none; cursor: pointer; }
    .btn-pdf { background-color: #2ecc71; padding: 10px 18px; border: none; color: white; cursor: pointer; }
    .btn-edit { background-color: #17a2b8; color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; }
    .btn-delete { background-color: #dc3545; color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table thead { background-color: #00bfff; color: #fff; }
    table th, table td { padding: 12px; border: 1px solid #444; }
    table tbody tr:hover { background-color: #3c3c3c; }
    .text-center { text-align: center; }

    /* Mobile toggle */
    #tableWrapper.expanded td:nth-child(n+3),
    #tableWrapper.expanded th:nth-child(n+3) {
        display: table-cell !important;
    }

    @media (max-width: 768px) {
        #tableWrapper td:nth-child(n+3),
        #tableWrapper th:nth-child(n+3) {
            display: none;
        }
        .toggle-btn {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background-color: #444;
            color: white;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
        }
    }
</style>

<div class="container">

    <h2>Lançamento de Caixa Diário</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Lançamento salvo com sucesso!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Ocorreu um erro ao salvar o lançamento.</div>
    <?php elseif (isset($_GET['success_delete'])): ?>
        <div class="alert alert-success">Lançamento excluído com sucesso!</div>
    <?php elseif (isset($_GET['error_delete'])): ?>
        <div class="alert alert-danger">Ocorreu um erro ao excluir o lançamento.</div>
    <?php endif; ?>

    <form action="../actions/add_caixa_diario.php" method="post">
        <div class="form-group">
            <label for="data">Data:</label>
            <input type="date" class="form-control" id="data" name="data" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label for="valor">Valor:</label>
            <input type="number" step="0.01" class="form-control" id="valor" name="valor" placeholder="0.00" required>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Lançamento</button>
    </form>

    <div class="search-container">
        <h3>Filtrar por Período</h3>

        <div class="form-group">
            <label for="startDate">Data Inicial:</label>
            <input type="date" id="startDate" class="form-control-date">
        </div>

        <div class="form-group">
            <label for="endDate">Data Final:</label>
            <input type="date" id="endDate" class="form-control-date">
        </div>

        <button class="btn btn-pdf" onclick="gerarPDFPeriodo()">Salvar PDF do Período</button>
    </div>

    <hr>

    <h3>Histórico de Lançamentos</h3>

    <div id="tableWrapper" class="table-wrapper">
        <table id="lancamentosTable">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($lancamentos)): ?>
                    <?php foreach ($lancamentos as $lancamento): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($lancamento['data'])); ?></td>
                            <td>R$ <?php echo number_format($lancamento['valor'], 2, ',', '.'); ?></td>
                            <td>
                                <a href="editar_caixa_diario.php?id=<?php echo $lancamento['id']; ?>" class="btn-edit">Editar</a>
                                <a href="../actions/excluir_caixa_diario.php?id=<?php echo $lancamento['id']; ?>" class="btn-delete" onclick="return confirm('Tem certeza?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">Nenhum lançamento encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <button class="toggle-btn" id="toggleTableBtn">Ver mais colunas</button>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() { alert.style.display = 'none'; }, 500);
        }, 3000);
    });
});

function filterTable() {
    const startDate = startDateInput.value ? new Date(startDateInput.value) : null;
    const endDate = endDateInput.value ? new Date(endDateInput.value) : null;

    if (startDate) startDate.setUTCHours(0,0,0,0);
    if (endDate) endDate.setUTCHours(23,59,59,999);

    Array.from(table.rows).forEach(row => {
        const dateStr = row.cells[0].innerText.split('/');
        const rowDate = new Date(`${dateStr[2]}-${dateStr[1]}-${dateStr[0]}`);
        rowDate.setUTCHours(0,0,0,0);

        let show = true;

        if (startDate && rowDate < startDate) show = false;
        if (endDate && rowDate > endDate) show = false;

        row.style.display = show ? '' : 'none';
    });
}

const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const table = document.getElementById('lancamentosTable').getElementsByTagName('tbody')[0];

startDateInput.addEventListener('input', filterTable);
endDateInput.addEventListener('input', filterTable);

function gerarPDFPeriodo() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(18);
    doc.text("Histórico de Lançamentos de Caixa (Período)", 14, 22);

    const rows = [];

    Array.from(table.rows).forEach(row => {
        if (row.style.display !== 'none') {
            rows.push([
                row.cells[0].innerText,
                row.cells[1].innerText
            ]);
        }
    });

    if (rows.length === 0) {
        alert('Não há lançamentos neste período.');
        return;
    }

    doc.autoTable({
        head: [['Data', 'Valor']],
        body: rows,
        startY: 30
    });

    const total = rows.reduce((sum, r) =>
        sum + parseFloat(
            r[1].replace('R$ ', '').replace('.', '').replace(',', '.')
        ), 0
    );

    const finalY = doc.lastAutoTable.finalY + 10;
    doc.setFontSize(14);
    doc.text(`Total de Lançamentos: ${rows.length}`, 14, finalY);
    doc.text(`Valor Total: R$ ${total.toFixed(2).replace('.', ',')}`, 14, finalY + 8);

    doc.save('historico_caixa_periodo.pdf');
}

const toggleBtn = document.getElementById('toggleTableBtn');
const tableWrapper = document.getElementById('tableWrapper');

toggleBtn.addEventListener('click', () => {
    tableWrapper.classList.toggle('expanded');
    toggleBtn.textContent = tableWrapper.classList.contains('expanded')
        ? 'Ver menos colunas'
        : 'Ver mais colunas';
});

function checkScreenSize() {
    toggleBtn.style.display = window.innerWidth > 768 ? 'none' : 'block';
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
</script>

<?php require_once '../includes/footer.php'; ?>
