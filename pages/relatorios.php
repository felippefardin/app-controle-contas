<?php
require_once '../database.php'; // agora $conn está disponível
require_once '../includes/header.php';

// Buscar totais de contas a pagar (pendentes)
$resultPagar = $conn->query("SELECT COUNT(id) as total_contas, SUM(valor) as valor_total FROM contas_pagar WHERE status = 'pendente'");
$totaisPagar = $resultPagar->fetch_assoc();

// Buscar totais de contas a receber (pendentes)
$resultReceber = $conn->query("SELECT COUNT(id) as total_contas, SUM(valor) as valor_total FROM contas_receber WHERE status = 'pendente'");
$totaisReceber = $resultReceber->fetch_assoc();

// Calcula o saldo previsto
$saldoPrevisto = ($totaisReceber['valor_total'] ?? 0) - ($totaisPagar['valor_total'] ?? 0);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fa;
    color: #333;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 1200px;
    margin: auto;
}

h2 {
    font-size: 2rem;
    margin-bottom: 30px;
    font-weight: 600;
    color: #2c3e50;
}

.summary-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    padding: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
}

.card-receber {
    border-left: 5px solid #2ecc71;
}

.card-pagar {
    border-left: 5px solid #e74c3c;
}

.card-saldo.positive {
    border-left: 5px solid #3498db;
}

.card-saldo.negative {
    border-left: 5px solid #c0392b;
}

.summary-card h5 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: #34495e;
}

.summary-card p {
    font-size: 1.6rem;
    margin: 0;
    font-weight: bold;
    color: #2c3e50;
}

.summary-card span {
    font-size: 0.9rem;
    color: #7f8c8d;
}

.card-icon {
    font-size: 2.5rem;
    color: #bdc3c7;
    transition: color 0.3s ease;
}

.summary-card:hover .card-icon {
    color: #2980b9;
}

.chart-container {
    background-color: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

.chart-container h4 {
    font-size: 1.2rem;
    margin-bottom: 20px;
    color: #2c3e50;
    font-weight: 600;
}

@media (max-width: 767px) {
    .summary-card {
        margin-bottom: 20px;
    }

    .chart-container {
        padding: 15px;
    }
}
</style>

<div class="container">
    <h2>Dashboard Financeiro</h2>

    <!-- Cards principais -->
    <div class="row mb-4">
        <!-- A Receber -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="summary-card card-receber">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5>A Receber</h5>
                            <p>R$ <?= number_format($totaisReceber['valor_total'] ?? 0, 2, ',', '.'); ?></p>
                            <span><?= $totaisReceber['total_contas'] ?? 0; ?> contas pendentes</span>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- A Pagar -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="summary-card card-pagar">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5>A Pagar</h5>
                            <p>R$ <?= number_format($totaisPagar['valor_total'] ?? 0, 2, ',', '.'); ?></p>
                            <span><?= $totaisPagar['total_contas'] ?? 0; ?> contas pendentes</span>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saldo Previsto -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="summary-card card-saldo <?= ($saldoPrevisto >= 0) ? 'positive' : 'negative'; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5>Saldo Previsto</h5>
                            <p>R$ <?= number_format($saldoPrevisto, 2, ',', '.'); ?></p>
                            <span>Balanço</span>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Fluxo de Caixa -->
    <div class="row mt-4">
        <div class="col-md-10 offset-md-1">
            <div class="chart-container">
                <h4>Fluxo de Caixa (Últimos 30 dias)</h4>
                <canvas id="fluxoCaixaChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de Fluxo de Caixa
fetch('../actions/get_relatorios_data.php?report=fluxo_caixa')
    .then(response => response.json())
    .then(data => {
        const ctx = document.getElementById('fluxoCaixaChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Entradas',
                        data: data.entradas,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Saídas',
                        data: data.saidas,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>
