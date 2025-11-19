<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica permissão
if (!isset($_SESSION['nivel_acesso']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'master' && $_SESSION['nivel_acesso'] !== 'proprietario')) {
    header("Location: home.php");
    exit;
}

$conn = getMasterConnection();
$tenant_id = $_SESSION['tenant_id'] ?? null;
$faturas = [];

// --- LÓGICA DE DADOS ---
// Tenta buscar do banco de dados real
try {
    // Verifica se a tabela existe (para evitar erro fatal se você ainda não criou)
    $checkTable = $conn->query("SHOW TABLES LIKE 'faturas_assinatura'");
    
    if ($checkTable && $checkTable->num_rows > 0) {
        $stmt = $conn->prepare("SELECT id, data_vencimento, data_pagamento, valor, status, forma_pagamento FROM faturas_assinatura WHERE tenant_id = ? ORDER BY data_vencimento DESC");
        $stmt->bind_param("s", $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $faturas[] = $row;
        }
        $stmt->close();
    } else {
        // DADOS DE SIMULAÇÃO (CASO A TABELA NÃO EXISTA)
        // Isso permite que você visualize a tela antes de criar a tabela no banco
        $faturas = [
            ['id' => 999, 'data_vencimento' => date('Y-m-d'), 'data_pagamento' => date('Y-m-d'), 'valor' => 49.90, 'status' => 'pago', 'forma_pagamento' => 'pix'],
            ['id' => 888, 'data_vencimento' => date('Y-m-d', strtotime('-1 month')), 'data_pagamento' => date('Y-m-d', strtotime('-1 month')), 'valor' => 49.90, 'status' => 'pago', 'forma_pagamento' => 'cartao']
        ];
    }
} catch (Exception $e) {
    error_log("Erro ao buscar histórico: " . $e->getMessage());
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Pagamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        .header-page { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .header-page h2 { color: #00bfff; margin: 0; }
        
        .table-responsive { background-color: #1e1e1e; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #252525; color: #aaa; padding: 15px; text-align: left; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #333; color: #ddd; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #2a2a2a; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .badge-pago { background-color: rgba(40, 167, 69, 0.2); color: #2ecc71; border: 1px solid #28a745; }
        .badge-pendente { background-color: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; }
        .badge-falha { background-color: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid #dc3545; }

        .btn-recibo { background-color: #333; color: #fff; border: 1px solid #555; padding: 8px 15px; border-radius: 5px; text-decoration: none; transition: 0.3s; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px; }
        .btn-recibo:hover { background-color: #00bfff; color: #000; border-color: #00bfff; }
        
        .btn-voltar { color: #aaa; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-voltar:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-page">
        <h2><i class="fa-solid fa-list-ul"></i> Histórico de Pagamentos</h2>
        <a href="minha_assinatura.php" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>

    <?php if (empty($faturas)): ?>
        <div style="text-align: center; padding: 50px; color: #777; background: #1e1e1e; border-radius: 8px;">
            <i class="fa-regular fa-folder-open fa-3x"></i>
            <p style="margin-top: 15px;">Nenhuma fatura encontrada.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Data Venc.</th>
                        <th>Data Pagto.</th>
                        <th>Valor</th>
                        <th>Forma</th>
                        <th>Status</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faturas as $fat): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($fat['data_vencimento'])) ?></td>
                        <td>
                            <?= !empty($fat['data_pagamento']) ? date('d/m/Y', strtotime($fat['data_pagamento'])) : '-' ?>
                        </td>
                        <td>R$ <?= number_format($fat['valor'], 2, ',', '.') ?></td>
                        <td style="text-transform: capitalize;"><?= $fat['forma_pagamento'] ?? '-' ?></td>
                        <td>
                            <?php if ($fat['status'] === 'pago'): ?>
                                <span class="badge badge-pago">Pago</span>
                            <?php elseif ($fat['status'] === 'pendente'): ?>
                                <span class="badge badge-pendente">Pendente</span>
                            <?php else: ?>
                                <span class="badge badge-falha"><?= htmlspecialchars($fat['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($fat['status'] === 'pago'): ?>
                                <a href="recibo_pagamentos.php?id=<?= $fat['id'] ?>" target="_blank" class="btn-recibo">
                                    <i class="fa-solid fa-print"></i> Recibo
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>