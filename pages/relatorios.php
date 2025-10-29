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

require_once '../includes/header.php';

// ✅ 3. SIMPLIFICA OS FILTROS PARA O MODELO SAAS
$userFilter = "usuario_id = " . intval($usuarioId);
$userFilterCategorias = "id_usuario = " . intval($usuarioId);


// Função para reduzir repetições nas consultas
function getTotais($conn, $tabela, $status, $userFilter) {
    // Usando prepared statements para segurança
    $stmt = $conn->prepare("SELECT COUNT(id) AS total_contas, SUM(valor) AS valor_total FROM $tabela WHERE status = ? AND $userFilter");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// Totais principais
$totaisPagarPendentes = getTotais($conn, 'contas_pagar', 'pendente', $userFilter);
$totaisPagarBaixadas  = getTotais($conn, 'contas_pagar', 'baixada', $userFilter);
$totaisReceberPendentes = getTotais($conn, 'contas_receber', 'pendente', $userFilter);
$totaisReceberBaixadas  = getTotais($conn, 'contas_receber', 'baixada', $userFilter);

// Caixa diário
$stmtCaixa = $conn->prepare("SELECT SUM(valor) AS total FROM caixa_diario WHERE $userFilter");
$stmtCaixa->execute();
$totalCaixa = $stmtCaixa->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCaixa->close();

// Saldos
$saldoPrevisto = ($totaisReceberPendentes['valor_total'] ?? 0) - ($totaisPagarPendentes['valor_total'] ?? 0);
$saldoRealizado = (($totaisReceberBaixadas['valor_total'] ?? 0) + $totalCaixa) - ($totaisPagarBaixadas['valor_total'] ?? 0);

// --- DADOS GRÁFICO ---
$labels = $entradasPendentes = $saidasPendentes = $entradasBaixadas = $saidasBaixadas = [];

for ($i = 11; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $labels[] = date('M/Y', strtotime($mes . '-01'));

    // Entradas previstas e realizadas
    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_receber WHERE $userFilter AND status=? AND DATE_FORMAT(IF(status='baixada',data_baixa,data_vencimento),'%Y-%m')=?");
    $status = 'pendente';
    $stmt->bind_param("ss", $status, $mes);
    $stmt->execute();
    $entradasPendentes[] = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_receber WHERE $userFilter AND status='baixada' AND DATE_FORMAT(data_baixa,'%Y-%m')=?");
    $stmt->bind_param("s", $mes);
    $stmt->execute();
    $total_receber = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // Caixa do mês
    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM caixa_diario WHERE $userFilter AND DATE_FORMAT(data,'%Y-%m')=?");
    $stmt->bind_param("s", $mes);
    $stmt->execute();
    $total_caixa = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $entradasBaixadas[] = $total_receber + $total_caixa;

    // Saídas previstas e realizadas
    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_pagar WHERE $userFilter AND status=? AND DATE_FORMAT(IF(status='baixada',data_baixa,data_vencimento),'%Y-%m')=?");
    $status = 'pendente';
    $stmt->bind_param("ss", $status, $mes);
    $stmt->execute();
    $saidasPendentes[] = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_pagar WHERE $userFilter AND status='baixada' AND DATE_FORMAT(data_baixa,'%Y-%m')=?");
    $stmt->bind_param("s", $mes);
    $stmt->execute();
    $saidasBaixadas[] = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

// Categorias
$stmt = $conn->prepare("SELECT id, nome FROM categorias WHERE $userFilterCategorias ORDER BY nome");
$stmt->execute();
$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totaisPorCategoria = [];
foreach ($categorias as $c) {
    $id = $c['id'];
    $nome = $c['nome'];

    $stmt = $conn->prepare("SELECT SUM(valor) as total FROM contas_receber WHERE $userFilter AND id_categoria=? AND status='baixada'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $receber = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(valor) as total FROM contas_pagar WHERE $userFilter AND id_categoria=? AND status='baixada'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pagar = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    if ($receber > 0 || $pagar > 0)
        $totaisPorCategoria[$nome] = ['receber' => $receber, 'pagar' => $pagar];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatórios Financeiros</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Seus estilos CSS permanecem os mesmos */
body { font-family:'Segoe UI',sans-serif; background:#121212; color:#eee; margin:0; padding:20px; }
.container { max-width:1300px; margin:auto; background:#1e1e1e; padding:25px; border-radius:10px; box-shadow:0 0 10px #000; }
h2 { text-align:center; color:#00bfff; font-weight:600; margin-bottom:25px; }
.section-title { border-bottom:1px solid #333; color:#ccc; padding-bottom:8px; margin-top:30px; margin-bottom:20px; font-size:1.3rem; }
.row { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }
.summary-card { background:#242424; border-left:5px solid #00bfff33; padding:20px; border-radius:10px; transition:.3s; }
.summary-card:hover { transform:translateY(-3px); background:#2b2b2b; }
.summary-card i { font-size:1.8rem; color:#00bfff; margin-bottom:8px; }
.summary-card h5 { font-size:1rem; margin:0; color:#bbb; }
.summary-card p { font-size:1.6rem; margin:5px 0; font-weight:600; color:#fff; }
.summary-card span { font-size:.9rem; color:#999; }
.card-positive { border-left-color:#2ecc71; }
.card-negative { border-left-color:#e74c3c; }
.table-container { background:#242424; border-radius:10px; padding:20px; margin-top:30px; overflow-x:auto; }
.table-container table { width:100%; }
.table-container th, .table-container td { padding:12px; border-bottom:1px solid #333; }
.table-container th { background:#2a2a2a; color:#00bfff; }
.table-container td.currency { text-align:center; }
.table-container .total-recebido { color:#2ecc71; }
.table-container .total-pago { color:#e74c3c; }
.chart-container { background:#242424; border-radius:10px; padding:25px; margin-top:30px; }
.chart-container canvas { width:100%; height:400px !important; }
.chart-container h4 { color:#eee; margin-bottom:15px; }
#exportOptions { display:flex; gap:10px; margin-top:15px; }
button.export-btn { background:#00bfff; border:none; color:#fff; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:15px; }
button.export-btn:hover { background:#0099cc; }
@media (max-width:768px){
    body{padding:10px;}
    h2{font-size:1.5rem;}
    .summary-card p{font-size:1.3rem;}
}
</style>
</head>
<body>
<div class="container" id="pdf-content">
    <h2>Dashboard Financeiro</h2>

    <h3 class="section-title">Balanço Previsto</h3>
    <div class="row">
        <div class="summary-card">
            <i class="fa-solid fa-arrow-down"></i>
            <h5>A Receber (Previsto)</h5>
            <p>R$ <?= number_format($totaisReceberPendentes['valor_total'] ?? 0, 2, ',', '.') ?></p>
            <span><?= $totaisReceberPendentes['total_contas'] ?? 0 ?> contas</span>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-arrow-up"></i>
            <h5>A Pagar (Previsto)</h5>
            <p>R$ <?= number_format($totaisPagarPendentes['valor_total'] ?? 0, 2, ',', '.') ?></p>
            <span><?= $totaisPagarPendentes['total_contas'] ?? 0 ?> contas</span>
        </div>
        <div class="summary-card <?= $saldoPrevisto >= 0 ? 'card-positive' : 'card-negative' ?>">
            <i class="fa-solid fa-scale-balanced"></i>
            <h5>Saldo Previsto</h5>
            <p>R$ <?= number_format($saldoPrevisto, 2, ',', '.') ?></p>
            <span>Balanço Futuro</span>
        </div>
    </div>

    <h3 class="section-title">Balanço Realizado</h3>
    <div class="row">
        <div class="summary-card">
            <i class="fa-solid fa-money-bill-wave"></i>
            <h5>Recebido</h5>
            <p>R$ <?= number_format($totaisReceberBaixadas['valor_total'] ?? 0, 2, ',', '.') ?></p>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-cash-register"></i>
            <h5>Caixa Diário</h5>
            <p>R$ <?= number_format($totalCaixa, 2, ',', '.') ?></p>
        </div>
        <div class="summary-card">
            <i class="fa-solid fa-wallet"></i>
            <h5>Pago</h5>
            <p>R$ <?= number_format($totaisPagarBaixadas['valor_total'] ?? 0, 2, ',', '.') ?></p>
        </div>
        <div class="summary-card <?= $saldoRealizado >= 0 ? 'card-positive' : 'card-negative' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <h5>Balanço Realizado</h5>
            <p>R$ <?= number_format($saldoRealizado, 2, ',', '.') ?></p>
        </div>
    </div>

    <h3 class="section-title"><i class="fa-solid fa-list-check"></i> Totais por Categoria</h3>
<div class="table-container">
    <table class="table-categorias">
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Recebido</th>
                <th>Pago</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($totaisPorCategoria)): ?>
                <?php foreach($totaisPorCategoria as $nome => $totais): ?>
                    <tr>
                        <td><?= htmlspecialchars($nome) ?></td>
                        <td class="currency total-recebido">R$ <?= number_format($totais['receber'], 2, ',', '.') ?></td>
                        <td class="currency total-pago">R$ <?= number_format($totais['pagar'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center; color:#999;">Nenhum dado disponível</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <div class="chart-container">
        <h4>Fluxo de Caixa (Últimos 12 meses)</h4>
        <canvas id="fluxoChart"></canvas>
        <div id="exportOptions">
            <button class="export-btn" id="savePdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const labels = <?= json_encode($labels) ?>;
const entradasPendentes = <?= json_encode($entradasPendentes) ?>;
const saidasPendentes = <?= json_encode($saidasPendentes) ?>;
const entradasBaixadas = <?= json_encode($entradasBaixadas) ?>;
const saidasBaixadas = <?= json_encode($saidasBaixadas) ?>;

new Chart(document.getElementById('fluxoChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            { 
                label:'Receita Realizada', 
                data: entradasBaixadas, 
                borderColor: 'rgba(46,204,113,1)', 
                backgroundColor: 'rgba(46,204,113,0.2)', 
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7
            },
            { 
                label:'Despesa Realizada', 
                data: saidasBaixadas, 
                borderColor: 'rgba(231,76,60,1)', 
                backgroundColor: 'rgba(231,76,60,0.2)', 
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7
            },
            { 
                label:'Receita Prevista', 
                data: entradasPendentes, 
                borderColor: 'rgba(46,204,113,0.5)', 
                backgroundColor: 'rgba(46,204,113,0.1)', 
                borderDash: [5,5],
                fill: false,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            { 
                label:'Despesa Prevista', 
                data: saidasPendentes, 
                borderColor: 'rgba(231,76,60,0.5)', 
                backgroundColor: 'rgba(231,76,60,0.1)', 
                borderDash: [5,5],
                fill: false,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        stacked: false,
        scales:{ 
            y:{ 
                beginAtZero:true, 
                ticks:{ color:'#eee', callback: value => 'R$ ' + value.toLocaleString('pt-BR',{minimumFractionDigits:2}) }, 
                grid:{ color:'#333' } 
            },
            x:{ ticks:{ color:'#eee' }, grid:{ color:'#333' } }
        },
        plugins:{ 
            legend:{ labels:{ color:'#eee' } },
            tooltip:{ 
                callbacks:{
                    label:c => `${c.dataset.label}: R$ ${c.parsed.y.toLocaleString('pt-BR',{minimumFractionDigits:2})}`
                }
            }
        }
    }
});

// Exportar PDF
document.getElementById('savePdf').addEventListener('click',()=>{
    const { jsPDF } = window.jspdf;
    html2canvas(document.getElementById('pdf-content'), { backgroundColor:'#1e1e1e', scale:2 }).then(canvas=>{
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p','mm','a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('relatorio_financeiro.pdf');
    });
});
</script>
<?php require_once '../includes/footer.php'; ?>