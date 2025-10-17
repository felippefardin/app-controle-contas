<?php
require_once '../includes/session_init.php';
require_once '../includes/header.php';
require_once '../database.php';

// Busca todos os lançamentos de caixa
$lancamentos = [];
$sql = "SELECT id, data, valor FROM caixa_diario ORDER BY data DESC";
$result = $conn->query($sql); 
if ($result) {
    $lancamentos = $result->fetch_all(MYSQLI_ASSOC);
}

// Calcula totais
$totalLancamentos = count($lancamentos);
$totalValor = 0;
foreach ($lancamentos as $l) {
    $totalValor += $l['valor'];
}
?>

<style>
body {
    background-color: #121212;
    color: #eee;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

/* Container */
.container {
    width: 95%;
    max-width: 1200px;
    margin: 20px auto;
    background-color: #222;
    padding: 25px;
    border-radius: 8px;
    box-sizing: border-box;
}

/* Títulos */
h2, h3 {
    color: #00bfff;
    border-bottom: 2px solid #00bfff;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

/* Alertas */
.alert {
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 20px;
    text-align: center;
    color: white;
    opacity: 1;
    transition: opacity 0.5s ease-out;
}
.alert-success { background-color: #28a745; }
.alert-danger { background-color: #cc4444; }

/* Formulários */
form, .search-container {
    background-color: #1f1f1f;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; color: #eee; }

.form-control, .form-control-date {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #444;
    background-color: #2c2c2c;
    color: #eee;
    font-size: 1rem;
    box-sizing: border-box;
}

.form-control:focus, .form-control-date:focus {
    outline: none;
    border-color: #00bfff;
}

/* Botões */
.btn-primary, .btn-pdf {
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: 0.2s;
}
.btn-primary { background-color: #00bfff; margin-right: 10px; }
.btn-primary:hover { background-color: #0099cc; }
.btn-pdf { background-color: #2ecc71; }
.btn-pdf:hover { background-color: #27ae60; }

.btn-edit, .btn-delete {
    color: white;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 0.85rem;
    text-decoration: none;
    transition: 0.2s;
}
.btn-edit { background-color: #17a2b8; }
.btn-edit:hover { background-color: #117a8b; }
.btn-delete { background-color: #dc3545; }
.btn-delete:hover { background-color: #c82333; }

/* === TABELA FULL DESKTOP === */
.table-wrapper {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    table-layout: auto;
}
table thead {
    background-color: #00bfff;
    color: #fff;
}
table th, table td {
    padding: 12px;
    border: 1px solid #444;
    text-align: left;
    white-space: nowrap;
}
table tbody tr { background-color: #2c2c2c; }
table tbody tr:hover { background-color: #3c3c3c; }

.text-center { text-align: center; }

/* === RESPONSIVIDADE === */
@media (max-width: 768px) {
    .container { padding: 15px; width: 95%; }

    h2, h3 { font-size: 1.2rem; text-align: center; }

    form, .search-container { padding: 15px; }

    .btn-primary, .btn-pdf {
        width: 100%;
        margin: 8px 0;
        font-size: 1rem;
        padding: 12px;
    }

    /* Tabela compacta (modo padrão) */
    .table-wrapper {
        overflow-x: auto;
        border-radius: 8px;
    }

    table th:nth-child(3),
    table td:nth-child(3) {
        display: none; /* Esconde coluna "Ações" */
    }

    /* Modo expandido */
    .expanded table th:nth-child(3),
    .expanded table td:nth-child(3) {
        display: table-cell;
    }

    /* Botão toggle */
    .toggle-btn {
        display: block;
        width: 100%;
        background-color: #444;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 6px;
        margin-top: 10px;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.2s;
    }
    .toggle-btn:hover { background-color: #00bfff; }
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

    <div class="table-wrapper" id="tableWrapper">
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
                                <a href="../actions/excluir_caixa_diario.php?id=<?php echo $lancamento['id']; ?>" class="btn-delete" onclick="return confirm('Tem certeza que deseja excluir este lançamento?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">Nenhum lançamento encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Botão Toggle apenas no mobile -->
    <button class="toggle-btn" id="toggleTableBtn">Ver mais colunas</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// Esconde alertas automaticamente
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() { alert.style.display = 'none'; }, 500);
        }, 3000);
    });
});

// Filtro por período
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const table = document.getElementById('lancamentosTable').getElementsByTagName('tbody')[0];

function filterTable() {
    const startDate = startDateInput.value ? new Date(startDateInput.value) : null;
    const endDate = endDateInput.value ? new Date(endDateInput.value) : null;

    Array.from(table.rows).forEach(row => {
        const dateStr = row.cells[0].innerText.split('/');
        const rowDate = new Date(`${dateStr[2]}-${dateStr[1]}-${dateStr[0]}`);
        let show = true;

        if (startDate && rowDate < startDate) show = false;
        if (endDate && rowDate > endDate) show = false;

        row.style.display = show ? '' : 'none';
    });
}
startDateInput.addEventListener('input', filterTable);
endDateInput.addEventListener('input', filterTable);

// Geração de PDF
function gerarPDFPeriodo() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(18);
    doc.text("Histórico de Lançamentos de Caixa (Período)", 14, 22);
    doc.setFontSize(12);

    const rows = [];
    Array.from(table.rows).forEach(row => {
        if (row.style.display !== 'none') {
            rows.push([row.cells[0].innerText, row.cells[1].innerText]);
        }
    });

    if (rows.length === 0) {
        alert('Não há lançamentos neste período.');
        return;
    }

    doc.autoTable({
        head: [['Data', 'Valor']],
        body: rows,
        startY: 30,
        theme: 'grid',
        headStyles: { fillColor: [0, 191, 255], textColor: [255, 255, 255] },
        bodyStyles: { fillColor: [47, 47, 47], textColor: [255, 255, 255] },
        alternateRowStyles: { fillColor: [60, 60, 60] },
        margin: { left: 14, right: 14 }
    });

    const total = rows.reduce((sum, r) => sum + parseFloat(r[1].replace('R$ ', '').replace('.', '').replace(',', '.')), 0);
    const finalY = doc.lastAutoTable.finalY + 10;
    doc.setFontSize(14);
    doc.text(`Total de Lançamentos: ${rows.length}`, 14, finalY);
    doc.text(`Valor Total: R$ ${total.toFixed(2).replace('.', ',')}`, 14, finalY + 8);
    doc.save('historico_caixa_periodo.pdf');
}

// Toggle modo compacto mobile
const toggleBtn = document.getElementById('toggleTableBtn');
const tableWrapper = document.getElementById('tableWrapper');

toggleBtn.addEventListener('click', () => {
    tableWrapper.classList.toggle('expanded');
    toggleBtn.textContent = tableWrapper.classList.contains('expanded')
        ? 'Ver menos colunas'
        : 'Ver mais colunas';
});

// Oculta botão toggle no desktop
function checkScreenSize() {
    toggleBtn.style.display = window.innerWidth > 768 ? 'none' : 'block';
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
</script>

<?php require_once '../includes/footer.php'; ?>
