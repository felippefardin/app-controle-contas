<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

$usuarioId = $_SESSION['usuario_id'];
$perfil = $_SESSION['nivel_acesso'];

// INCLUI O HEADER PADRÃO (Já abre a tag <main>)
require_once '../includes/header.php';

// EXIBE O POP-UP CENTRALIZADO
display_flash_message();

// 3. FILTROS
$userFilter = "usuario_id = " . intval($usuarioId);
$userFilterCategorias = "id_usuario = " . intval($usuarioId);

// --- FUNÇÃO GET TOTAIS MELHORADA ---
function getTotais($conn, $tabela, $status, $userFilter, $mes = null, $colunaData = 'data_vencimento') {
    $sql = "SELECT COUNT(id) AS total_contas, SUM(valor) AS valor_total FROM $tabela WHERE status = ? AND $userFilter";
    
    if ($mes) {
        $sql .= " AND DATE_FORMAT($colunaData, '%Y-%m') = '$mes'";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// Data atual para filtros
$mesAtual = date('Y-m');

// --- 1. DADOS ACUMULADOS ---
$historicoPagar = getTotais($conn, 'contas_pagar', 'baixada', $userFilter); 
$historicoReceber = getTotais($conn, 'contas_receber', 'baixada', $userFilter);

// Caixa diário (Acumulado)
$stmtCaixa = $conn->prepare("SELECT SUM(valor) AS total FROM caixa_diario WHERE $userFilter");
$stmtCaixa->execute();
$totalCaixa = $stmtCaixa->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCaixa->close();

// CALCULO DO SALDO REALIZADO
$saldoRealizado = (($historicoReceber['valor_total'] ?? 0) + $totalCaixa) - ($historicoPagar['valor_total'] ?? 0);

// --- 2. DADOS DO MÊS ATUAL ---
$pendentePagarMes = getTotais($conn, 'contas_pagar', 'pendente', $userFilter, $mesAtual, 'data_vencimento');
$pendenteReceberMes = getTotais($conn, 'contas_receber', 'pendente', $userFilter, $mesAtual, 'data_vencimento');

$baixadoPagarMes = getTotais($conn, 'contas_pagar', 'baixada', $userFilter, $mesAtual, 'data_baixa');
$baixadoReceberMes = getTotais($conn, 'contas_receber', 'baixada', $userFilter, $mesAtual, 'data_baixa');

// Caixa Diário (Somente do Mês)
$stmtCaixaMes = $conn->prepare("SELECT SUM(valor) AS total FROM caixa_diario WHERE $userFilter AND DATE_FORMAT(data, '%Y-%m') = ?");
$stmtCaixaMes->bind_param("s", $mesAtual);
$stmtCaixaMes->execute();
$caixaMes = $stmtCaixaMes->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCaixaMes->close();

// --- 3. SALDO PREVISTO INTELIGENTE ---
$saldoPrevisto = ($saldoRealizado + ($pendenteReceberMes['valor_total'] ?? 0)) - ($pendentePagarMes['valor_total'] ?? 0);

// --- 4. GRÁFICO 12 MESES ---
$labels = $entradasPendentes = $saidasPendentes = $entradasBaixadas = $saidasBaixadas = [];

for ($i = 11; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i month"));
    $labels[] = date('M/Y', strtotime($mes . '-01'));

    // Entradas Pendentes
    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_receber WHERE $userFilter AND status=? AND DATE_FORMAT(data_vencimento,'%Y-%m')=?");
    $status = 'pendente';
    $stmt->bind_param("ss", $status, $mes);
    $stmt->execute();
    $entradasPendentes[] = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // Entradas Baixadas
    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_receber WHERE $userFilter AND status='baixada' AND DATE_FORMAT(data_baixa,'%Y-%m')=?");
    $stmt->bind_param("s", $mes);
    $stmt->execute();
    $total_receber = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM caixa_diario WHERE $userFilter AND DATE_FORMAT(data,'%Y-%m')=?");
    $stmt->bind_param("s", $mes);
    $stmt->execute();
    $total_caixa = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $entradasBaixadas[] = $total_receber + $total_caixa;

    // Saídas Pendentes
    $stmt = $conn->prepare("SELECT SUM(valor) AS total FROM contas_pagar WHERE $userFilter AND status=? AND DATE_FORMAT(data_vencimento,'%Y-%m')=?");
    $status = 'pendente';
    $stmt->bind_param("ss", $status, $mes);
    $stmt->execute();
    $saidasPendentes[] = floatval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // Saídas Baixadas
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

    if ($receber > 0 || $pagar > 0) {
        $totaisPorCategoria[$nome] = ['receber' => $receber, 'pagar' => $pagar];
    }
}
?>

<style>
    /* Container principal da página de relatórios */
    .report-wrapper {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 10px;
        box-sizing: border-box;
    }

    /* Cards e Containers internos */
    .report-card-container { 
        width: 100%;
        background: var(--bg-card, #1e1e1e); 
        padding: 25px; 
        border-radius: 10px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
        box-sizing: border-box;
        color: var(--text-primary, #eee);
        margin-bottom: 20px;
    }

    h2 { text-align: center; color: #00bfff; font-weight: 600; margin-bottom: 25px; }
    .section-title { border-bottom: 1px solid #333; color: var(--text-secondary, #ccc); padding-bottom: 8px; margin-top: 30px; margin-bottom: 20px; font-size: 1.3rem; }
    
    /* Grid responsivo para os cards */
    .report-row { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
        gap: 20px; 
    }

    .summary-card { 
        background: #242424; 
        border-left: 5px solid #00bfff33; 
        padding: 20px; 
        border-radius: 10px; 
        transition: .3s; 
    }
    /* Adaptação para tema claro via variáveis globais se existirem, senão mantém dark */
    body.light-mode .summary-card { background: #f8f9fa; border: 1px solid #ddd; border-left-width: 5px; }
    body.light-mode .summary-card h5 { color: #555; }
    body.light-mode .summary-card p { color: #333; }
    
    .summary-card:hover { transform: translateY(-3px); }
    .summary-card i { font-size: 1.8rem; color: #00bfff; margin-bottom: 8px; }
    .summary-card h5 { font-size: 1rem; margin: 0; color: #bbb; }
    .summary-card p { font-size: 1.6rem; margin: 5px 0; font-weight: 600; color: #fff; }
    .summary-card span { font-size: .9rem; color: #999; }
    
    .card-positive { border-left-color: #2ecc71; }
    .card-negative { border-left-color: #e74c3c; }

    .table-container { background: #242424; border-radius: 10px; padding: 20px; margin-top: 30px; overflow-x: auto; }
    body.light-mode .table-container { background: #fff; border: 1px solid #ddd; }
    
    .table-container table { width: 100%; border-collapse: collapse; min-width: 500px; }
    .table-container th, .table-container td { padding: 12px; border-bottom: 1px solid #333; text-align: left; color: var(--text-primary, #eee); }
    body.light-mode .table-container th, body.light-mode .table-container td { border-bottom: 1px solid #ddd; color: #333; }
    
    .table-container th { background: #2a2a2a; color: #00bfff; }
    body.light-mode .table-container th { background: #f1f1f1; }
    
    .table-container td.currency { text-align: right; }
    .table-container .total-recebido { color: #2ecc71; }
    .table-container .total-pago { color: #e74c3c; }

    .chart-container { background: #242424; border-radius: 10px; padding: 25px; margin-top: 30px; }
    body.light-mode .chart-container { background: #fff; border: 1px solid #ddd; }
    
    .chart-container canvas { width: 100%; height: 400px !important; }
    .chart-container h4 { color: var(--text-primary, #eee); margin-bottom: 15px; }
    
    #exportOptions { display: flex; gap: 10px; margin-top: 15px; justify-content: center; flex-wrap: wrap; }
    button.export-btn { background: #00bfff; border: none; color: #fff; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 15px; }
    button.export-btn:hover { background: #0099cc; }
    
    /* Modal */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 800px; position: relative; max-height: 90vh; overflow-y: auto; color: #eee; }
    body.light-mode .modal-content { background-color: #fff; color: #333; }
    
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content .close-btn:hover { color: #00bfff; }
    .modal-content form { display: flex; flex-direction: column; gap: 15px; }
    .modal-content form input, .modal-content form select { width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; box-sizing: border-box; }
    body.light-mode .modal-content form input, body.light-mode .modal-content form select { background-color: #f9f9f9; border: 1px solid #ccc; color: #333; }
    
    .export-buttons-group { text-align: center; margin-top: 20px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
    .btn-export { background-color: #28a745; color: white; padding: 10px 14px; border: none; font-weight: bold; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
    .btn-export:hover { background-color: #218838; }
    .section-export { border: 1px solid #333; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
    .section-export h4 { color: #00bfff; margin-top: 0; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px; }

    @media (max-width: 768px) {
        .chart-container canvas { height: 250px !important; }
        button.export-btn { width: 100%; margin-bottom: 5px; }
        #exportOptions { flex-direction: column; }
        .export-buttons-group { flex-direction: column; }
        .btn-export { width: 100%; }
    }
</style>

<div class="report-wrapper">
    <div class="report-card-container" id="pdf-content">
        <h2>Dashboard Financeiro</h2>

        <h3 class="section-title">Balanço Previsto (Mês Atual)</h3>
        <div class="report-row">
            <div class="summary-card">
                <i class="fa-solid fa-arrow-down"></i>
                <h5>A Receber (Mês)</h5>
                <p>R$ <?= number_format($pendenteReceberMes['valor_total'] ?? 0, 2, ',', '.') ?></p>
                <span><?= $pendenteReceberMes['total_contas'] ?? 0 ?> contas pendentes</span>
            </div>
            <div class="summary-card">
                <i class="fa-solid fa-arrow-up"></i>
                <h5>A Pagar (Mês)</h5>
                <p>R$ <?= number_format($pendentePagarMes['valor_total'] ?? 0, 2, ',', '.') ?></p>
                <span><?= $pendentePagarMes['total_contas'] ?? 0 ?> contas pendentes</span>
            </div>
            <div class="summary-card <?= $saldoPrevisto >= 0 ? 'card-positive' : 'card-negative' ?>">
                <i class="fa-solid fa-scale-balanced"></i>
                <h5>Saldo Projetado (Final do Mês)</h5>
                <p>R$ <?= number_format($saldoPrevisto, 2, ',', '.') ?></p>
                <span>Caixa Hoje + Receitas - Despesas</span>
            </div>
        </div>

        <h3 class="section-title">Balanço Realizado (Caixa e Mês)</h3>
        <div class="report-row">
            <div class="summary-card">
                <i class="fa-solid fa-money-bill-wave"></i>
                <h5>Recebido (Mês)</h5>
                <p>R$ <?= number_format($baixadoReceberMes['valor_total'] ?? 0, 2, ',', '.') ?></p>
            </div>
            <div class="summary-card">
                <i class="fa-solid fa-cash-register"></i>
                <h5>Caixa Diário (Mês)</h5>
                <p>R$ <?= number_format($caixaMes, 2, ',', '.') ?></p>
            </div>
            <div class="summary-card">
                <i class="fa-solid fa-wallet"></i>
                <h5>Pago (Mês)</h5>
                <p>R$ <?= number_format($baixadoPagarMes['valor_total'] ?? 0, 2, ',', '.') ?></p>
            </div>
            <div class="summary-card <?= $saldoRealizado >= 0 ? 'card-positive' : 'card-negative' ?>">
                <i class="fa-solid fa-chart-line"></i>
                <h5>Saldo em Caixa (Atual)</h5>
                <p>R$ <?= number_format($saldoRealizado, 2, ',', '.') ?></p>
                <span>Dinheiro Acumulado</span>
            </div>
        </div>

        <h3 class="section-title"><i class="fa-solid fa-list-check"></i> Totais por Categoria</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th style="text-align: right;">Recebido</th>
                        <th style="text-align: right;">Pago</th>
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
                <button class="export-btn" id="savePdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF Dashboard
                </button>
                <button class="export-btn" onclick="document.getElementById('exportarDadosModal').style.display='flex'">
                    <i class="fa-solid fa-file-export"></i> Exportar Dados
                </button>
            </div>
        </div>
    </div>
</div>

<div id="exportarDadosModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('exportarDadosModal').style.display='none'">&times;</span>
        <h3>Exportar Dados Financeiros e Cadastrais</h3>

        <div class="section-export">
            <h4>Contas a Pagar / Receber</h4>
            <form id="formExportarContas" action="" method="GET" target="_blank">
                <label for="tipo_conta">Tipo de Conta:</label>
                <select id="tipo_conta" name="tipo_conta" required>
                    <option value="pagar">Contas a Pagar</option>
                    <option value="receber">Contas a Receber</option>
                </select>

                <label for="data_inicio_contas">Data de Início (Vencimento):</label>
                <input type="date" id="data_inicio_contas" name="data_inicio">
                <label for="data_fim_contas">Data de Fim (Vencimento):</label>
                <input type="date" id="data_fim_contas" name="data_fim">

                <label for="status_export_contas">Status:</label>
                <select id="status_export_contas" name="status">
                    <option value="pendente">Em Aberto</option>
                    <option value="baixada">Baixadas</option>
                </select>

                <div class="export-buttons-group">
                    <button type="submit" name="formato" value="csv" class="btn-export">CSV</button>
                    <button type="submit" name="formato" value="pdf" class="btn-export">PDF</button>
                    <button type="submit" name="formato" value="excel" class="btn-export">Excel</button>
                </div>
            </form>
        </div>

        <div class="section-export">
            <h4>Clientes / Fornecedores</h4>
            <form id="formExportarPessoas" action="../actions/exportar_pessoas_fornecedores.php" method="GET" target="_blank">
                <label for="tipo_pessoa">Tipo de Cadastro:</label>
                <select id="tipo_pessoa" name="tipo" required>
                    <option value="cliente">Clientes</option>
                    <option value="fornecedor">Fornecedores</option>
                    <option value="todos">Todos</option>
                </select>

                <div class="export-buttons-group">
                    <button type="submit" name="formato" value="csv" class="btn-export">CSV</button>
                    <button type="submit" name="formato" value="pdf" class="btn-export">PDF</button>
                    <button type="submit" name="formato" value="excel" class="btn-export">Excel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    const labels = <?= json_encode($labels) ?>;
    const entradasPendentes = <?= json_encode($entradasPendentes) ?>;
    const entradasBaixadas = <?= json_encode($entradasBaixadas) ?>;
    const saidasPendentes = <?= json_encode($saidasPendentes) ?>;
    const saidasBaixadas = <?= json_encode($saidasBaixadas) ?>;

    new Chart(document.getElementById('fluxoChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label:'Receita Prevista', data: entradasPendentes, borderColor:'#2ecc71', backgroundColor:'#2ecc7133', fill:true, tension:0.3 },
                { label:'Receita Realizada', data: entradasBaixadas, borderColor:'#27ae60', backgroundColor:'#27ae6033', fill:true, tension:0.3 },
                { label:'Despesa Prevista', data: saidasPendentes, borderColor:'#e74c3c', backgroundColor:'#e74c3c33', fill:true, tension:0.3 },
                { label:'Despesa Realizada', data: saidasBaixadas, borderColor:'#c0392b', backgroundColor:'#c0392b33', fill:true, tension:0.3 },
            ]
        },
        options: { 
            responsive:true, 
            scales:{ 
                y:{ beginAtZero:true, grid: { color: 'rgba(255,255,255,0.1)' } },
                x:{ grid: { color: 'rgba(255,255,255,0.1)' } }
            },
            plugins: {
                legend: { labels: { color: '#999' } }
            }
        }
    });

    document.getElementById('savePdf').addEventListener('click', ()=>{
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('pdf-content');
        
        // Temporarily force dark mode background for PDF render if needed or keep as is
        html2canvas(element, { scale:2, useCORS: true }).then(canvas=>{
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p','mm','a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth)/canvas.width;
            pdf.addImage(imgData,'PNG',0,0,pdfWidth,pdfHeight);
            pdf.save('relatorio_financeiro.pdf');
        });
    });

    document.getElementById('formExportarContas').addEventListener('submit', function(e){
        const tipoConta = document.getElementById('tipo_conta').value;
        const formato = e.submitter.value;
        this.action = `../actions/exportar_contas_${tipoConta}.php?formato=${formato}`;
    });

    window.addEventListener('click', e => {
        const modal = document.getElementById('exportarDadosModal');
        if(e.target === modal) modal.style.display='none';
    });
</script>

<?php include('../includes/footer.php'); ?>