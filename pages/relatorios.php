<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // agora $conn está disponível
require_once '../includes/header.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    // Redireciona para a página de login se não estiver logado
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario']['id'];

// Buscar totais de contas a pagar (pendentes) do usuário logado
$stmtPagar = $conn->prepare("SELECT COUNT(id) as total_contas, SUM(valor) as valor_total FROM contas_pagar WHERE status = 'pendente' AND usuario_id = ?");
$stmtPagar->bind_param("i", $usuarioId);
$stmtPagar->execute();
$resultPagar = $stmtPagar->get_result();
$totaisPagar = $resultPagar->fetch_assoc();
$stmtPagar->close();

// Buscar totais de contas a receber (pendentes) do usuário logado
$stmtReceber = $conn->prepare("SELECT COUNT(id) as total_contas, SUM(valor) as valor_total FROM contas_receber WHERE status = 'pendente' AND usuario_id = ?");
$stmtReceber->bind_param("i", $usuarioId);
$stmtReceber->execute();
$resultReceber = $stmtReceber->get_result();
$totaisReceber = $resultReceber->fetch_assoc();
$stmtReceber->close();

// Calcula o saldo previsto
$saldoPrevisto = ($totaisReceber['valor_total'] ?? 0) - ($totaisPagar['valor_total'] ?? 0);
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #121212; /* Cor de fundo principal do site */
    color: #eee; /* Cor de texto principal do site */
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 1200px;
    margin: auto;
    background-color: #222; /* Cor de fundo do container */
    /* padding: 25px; */
    border-radius: 8px;
}

h2 {
    font-size: 2rem;
    margin-bottom: 30px;
    font-weight: 600;
    color: #00bfff; /* Cor de destaque dos títulos */
    text-align: center;
}

.summary-card {
    background-color: #1f1f1f; /* Cor de fundo dos cards */
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    padding: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 5px solid #00bfff;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 15px rgba(0, 191, 255, 0.5);
}

.card-receber {
    border-left-color: #2ecc71;
}

.card-pagar {
    border-left-color: #e74c3c;
}

.card-saldo.positive {
    border-left-color: #3498db;
}

.card-saldo.negative {
    border-left-color: #c0392b;
}

.summary-card h5 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: #eee;
}

.summary-card p {
    font-size: 1.6rem;
    margin: 0;
    font-weight: bold;
    color: #fff;
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
    color: #00bfff;
}

.chart-container {
    background-color: #1f1f1f; /* Cor de fundo do container do gráfico */
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

.chart-container h4 {
    font-size: 1.2rem;
    margin-bottom: 20px;
    color: #eee;
    font-weight: 600;
}

/* Ajustes para o Chart.js em tema escuro */
.chart-container canvas {
    background-color: #1f1f1f;
}
</style>

<div class="container">
    <h2>Dashboard Financeiro</h2>

    <div class="row mb-4">
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
fetch(`../actions/get_relatorios_data.php?report=fluxo_caixa&usuario_id=<?= $usuarioId ?>`)
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
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#eee' // Cor dos ticks do eixo Y
                        },
                        grid: {
                            color: '#444' // Cor das linhas de grade do eixo Y
                        }
                    },
                    x: {
                        ticks: {
                            color: '#eee' // Cor dos ticks do eixo X
                        },
                        grid: {
                            color: '#444' // Cor das linhas de grade do eixo X
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#eee' // Cor da legenda
                        }
                    }
                }
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>