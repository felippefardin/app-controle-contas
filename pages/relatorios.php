<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // agora $conn está disponível
require_once '../includes/header.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];

// Totais gerais de contas a pagar (pendentes)
$stmtPagar = $conn->prepare("SELECT COUNT(id) as total_contas, SUM(valor) as valor_total FROM contas_pagar WHERE status = 'pendente' AND usuario_id = ?");
$stmtPagar->bind_param("i", $usuarioId);
$stmtPagar->execute();
$resultPagar = $stmtPagar->get_result();
$totaisPagar = $resultPagar->fetch_assoc();
$stmtPagar->close();

// Totais gerais de contas a receber (pendentes)
$stmtReceber = $conn->prepare("SELECT COUNT(id) as total_contas, SUM(valor) as valor_total FROM contas_receber WHERE status = 'pendente' AND usuario_id = ?");
$stmtReceber->bind_param("i", $usuarioId);
$stmtReceber->execute();
$resultReceber = $stmtReceber->get_result();
$totaisReceber = $resultReceber->fetch_assoc();
$stmtReceber->close();

// Saldo previsto
$saldoPrevisto = ($totaisReceber['valor_total'] ?? 0) - ($totaisPagar['valor_total'] ?? 0);

// Dados mensais para o gráfico de fluxo de caixa (últimos 12 meses)
$labels = [];
$entradas = [];
$saidas = [];
for ($i = 11; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $labels[] = date('M/Y', strtotime($mes.'-01'));

    // Entradas
    $stmtE = $conn->prepare("SELECT SUM(valor) as total FROM contas_receber WHERE usuario_id = ? AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?");
    $stmtE->bind_param("is", $usuarioId, $mes);
    $stmtE->execute();
    $resE = $stmtE->get_result()->fetch_assoc();
    $entradas[] = floatval($resE['total'] ?? 0);
    $stmtE->close();

    // Saídas
    $stmtS = $conn->prepare("SELECT SUM(valor) as total FROM contas_pagar WHERE usuario_id = ? AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?");
    $stmtS->bind_param("is", $usuarioId, $mes);
    $stmtS->execute();
    $resS = $stmtS->get_result()->fetch_assoc();
    $saidas[] = floatval($resS['total'] ?? 0);
    $stmtS->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatórios Financeiros</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #121212; color: #eee; margin:0; padding:20px;}
.container { max-width:1200px; margin:auto; background-color:#222; padding:25px; border-radius:8px; }
h2 { font-size:2rem; margin-bottom:30px; font-weight:600; color:#00bfff; text-align:center; }
.summary-card { background-color:#1f1f1f; border-radius:10px; padding:20px; margin-bottom:20px; border-left:5px solid #00bfff; }
.card-receber { border-left-color:#2ecc71; }
.card-pagar { border-left-color:#e74c3c; }
.card-saldo.positive { border-left-color:#3498db; }
.card-saldo.negative { border-left-color:#c0392b; }
.summary-card h5 { font-size:1.2rem; font-weight:600; margin-bottom:5px; color:#eee; }
.summary-card p { font-size:1.6rem; margin:0; font-weight:bold; color:#fff; }
.summary-card span { font-size:0.9rem; color:#7f8c8d; }
.card-icon { font-size:2.5rem; color:#bdc3c7; }
.chart-container { background-color:#1f1f1f; border-radius:10px; padding:25px; margin-top:30px; }
.chart-container h4 { font-size:1.2rem; margin-bottom:20px; color:#eee; font-weight:600; }
button#savePdf { margin-top:20px; background-color:#00bfff; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:16px; }
button#savePdf:hover { background-color:#0099cc; }
</style>
</head>
<body>
<div class="container">
<h2>Dashboard Financeiro</h2>

<div class="row">
    <div class="summary-card card-receber">
        <h5>A Receber</h5>
        <p>R$ <?= number_format($totaisReceber['valor_total'] ?? 0, 2, ',', '.'); ?></p>
        <span><?= $totaisReceber['total_contas'] ?? 0 ?> contas pendentes</span>
    </div>
    <div class="summary-card card-pagar">
        <h5>A Pagar</h5>
        <p>R$ <?= number_format($totaisPagar['valor_total'] ?? 0, 2, ',', '.'); ?></p>
        <span><?= $totaisPagar['total_contas'] ?? 0 ?> contas pendentes</span>
    </div>
    <div class="summary-card card-saldo <?= ($saldoPrevisto>=0)?'positive':'negative' ?>">
        <h5>Saldo Previsto</h5>
        <p>R$ <?= number_format($saldoPrevisto,2,',','.'); ?></p>
        <span>Balanço</span>
    </div>
</div>

<div class="chart-container">
<h4>Fluxo de Caixa (Últimos 12 meses)</h4>
<canvas id="fluxoCaixaChart"></canvas>
<button id="savePdf"></i> Salvar PDF</button>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
const labels = <?= json_encode($labels) ?>;
const entradas = <?= json_encode($entradas) ?>;
const saidas = <?= json_encode($saidas) ?>;

const ctx = document.getElementById('fluxoCaixaChart').getContext('2d');
const fluxoChart = new Chart(ctx, {
    type:'bar',
    data: {
        labels: labels,
        datasets: [
            { label:'Entradas', data:entradas, backgroundColor:'rgba(46, 204, 113, 0.6)', borderColor:'rgba(46, 204, 113, 1)', borderWidth:1 },
            { label:'Saídas', data:saidas, backgroundColor:'rgba(231, 76, 60, 0.6)', borderColor:'rgba(231, 76, 60, 1)', borderWidth:1 }
        ]
    },
    options: {
        responsive:true,
        scales: {
            y: { beginAtZero:true, ticks:{color:'#eee'}, grid:{color:'#444'} },
            x: { ticks:{color:'#eee'}, grid:{color:'#444'} }
        },
        plugins:{ legend:{ labels:{ color:'#eee' } } }
    }
});

// Botão para salvar PDF
document.getElementById('savePdf').addEventListener('click', () => {
    const container = document.querySelector('.container');
    html2canvas(container).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p','mm','a4');
        const imgProps = pdf.getImageProperties(imgData);
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        pdf.addImage(imgData,'PNG',0,0,pdfWidth,pdfHeight);
        pdf.save('relatorio_financeiro.pdf');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
