<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica permissão
if (!isset($_SESSION['nivel_acesso']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'master' && $_SESSION['nivel_acesso'] !== 'proprietario')) {
    header("Location: home.php");
    exit;
}

$conn = getMasterConnection();
if ($conn === null) {
    die("Erro ao conectar ao banco de dados principal.");
}

$tenant_id = $_SESSION['tenant_id'] ?? null;
$dados_assinatura = [];
$dias_restantes = 0;
$is_trial = false;
// Define o nome padrão como o nome do usuário logado (do Perfil)
$nome_exibicao = $_SESSION['nome'] ?? 'Cliente'; 

if ($tenant_id) {
    // Usa SELECT * para evitar erros de colunas faltantes no seu banco
    $stmt = $conn->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
    $stmt->bind_param("s", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dados_assinatura = $result->fetch_assoc();
        
        // Lógica Inteligente de Nome:
        // 1. Tenta 'nome_empresa' do banco
        // 2. Se vazio, tenta 'nome' do banco
        // 3. Se ambos vazios, mantém o $nome_exibicao que já pegamos da sessão (Perfil)
        if (!empty($dados_assinatura['nome_empresa'])) {
            $nome_exibicao = $dados_assinatura['nome_empresa'];
        } elseif (!empty($dados_assinatura['nome'])) {
            $nome_exibicao = $dados_assinatura['nome'];
        }

        // Verifica Status Trial
        $status = $dados_assinatura['status_assinatura'] ?? 'padrao';
        
        if ($status === 'trial') {
            $is_trial = true;
            $dias_teste = 15; 
            
            // Tenta pegar a data de início (prioridade: data_inicio_teste > data_criacao > data_cadastro > agora)
            $data_ref = $dados_assinatura['data_inicio_teste'] 
                     ?? $dados_assinatura['data_criacao'] 
                     ?? $dados_assinatura['data_cadastro'] 
                     ?? date('Y-m-d H:i:s');
            
            try {
                $data_inicio = new DateTime($data_ref);
                $data_fim_teste = clone $data_inicio;
                $data_fim_teste->modify("+$dias_teste days");
                
                $hoje = new DateTime();
                
                // Calcula diferença
                $intervalo = $hoje->diff($data_fim_teste);
                $dias_restantes = (int)$intervalo->format('%r%a');
            } catch (Exception $e) {
                $dias_restantes = 0;
            }
        }
    }
    $stmt->close();
}
$conn->close();

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Minha Assinatura</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin: 40px auto; }
        .card { background-color: #1e1e1e; border: 1px solid #333; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .card h3 { border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 20px; color: #00bfff; display: flex; align-items: center; gap: 10px; }
        
        /* Alerta de Trial */
        .trial-alert {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            color: #222;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); } }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #2c2c2c; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #aaa; }
        .value { font-weight: bold; color: #fff; }
        .status-ativo { color: #2ecc71; }
        .status-inativo { color: #e74c3c; }
        
        /* Botões */
        .btn-group { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px; }
        .btn { text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; text-align: center; transition: transform 0.2s; border: none; cursor: pointer; flex: 1; align-items: center; justify-content: center; gap: 8px; color: white; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; color: white; }
        
        .btn-assinar { background: linear-gradient(135deg, #28a745, #218838); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        .btn-history { background-color: #17a2b8; }
        .btn-receipt { background-color: #6c757d; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="color: #fff; margin-bottom: 20px;"><i class="fa-solid fa-star"></i> Gestão da Assinatura</h2>

    <?php if ($is_trial && $dias_restantes > 0): ?>
        <div class="trial-alert">
            <i class="fa-solid fa-clock fa-lg"></i>
            <div>
                PERÍODO DE TESTE: Restam <strong><?= $dias_restantes ?> dias</strong> gratuitos.
            </div>
            <a href="assinar.php" class="btn btn-sm" style="background: #222; color: #ffc107; margin-left: 10px;">Confirmar Assinatura Agora</a>
        </div>
    <?php elseif ($is_trial && $dias_restantes <= 0): ?>
        <div class="trial-alert" style="background: #e74c3c; color: white; animation: none;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>Seu período de teste expirou!</div>
        </div>
    <?php endif; ?>


    <div class="card">
        <h3><i class="fa-solid fa-file-contract"></i> Detalhes do Plano</h3>
        
        <div class="info-row">
            <span class="label">Responsável/Empresa:</span>
            <span class="value"><?= htmlspecialchars($nome_exibicao) ?></span>
        </div>

        <div class="info-row">
            <span class="label">Status:</span>
            <span class="value">
                <?php 
                $st = $dados_assinatura['status_assinatura'] ?? '-';
                if ($st === 'ativo') echo '<span class="status-ativo"><i class="fa-solid fa-check-circle"></i> Ativo</span>';
                elseif ($st === 'trial') echo '<span style="color: #ffc107"><i class="fa-solid fa-flask"></i> Em Teste (Trial)</span>';
                else echo '<span class="status-inativo"><i class="fa-solid fa-times-circle"></i> Inativo/Vencido</span>';
                ?>
            </span>
        </div>

        <?php if (!$is_trial && isset($dados_assinatura['data_renovacao'])): ?>
            <div class="info-row">
                <span class="label">Próxima Renovação:</span>
                <span class="value"><?= date('d/m/Y', strtotime($dados_assinatura['data_renovacao'])) ?></span>
            </div>
        <?php endif; ?>

        <div style="margin-top: 25px;">
            <?php if ($is_trial || ($dados_assinatura['status_assinatura'] ?? '') !== 'ativo'): ?>
                <a href="assinar.php" class="btn btn-assinar" style="width: 100%">
                    <i class="fa-solid fa-crown"></i> 
                    <?= $is_trial ? 'Efetivar Assinatura Premium' : 'Reativar Assinatura' ?>
                </a>
            <?php else: ?>
                 <button class="btn btn-assinar" style="width: 100%; cursor: default; background: #2c2c2c; box-shadow: none;">
                    <i class="fa-solid fa-check"></i> Assinatura Premium Ativa
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3><i class="fa-solid fa-wallet"></i> Financeiro</h3>
        <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 20px;">Acesse suas faturas passadas e comprovantes.</p>
        
        <div class="btn-group">
            <a href="historico_pagamento.php" class="btn btn-history">
                <i class="fa-solid fa-list-ul"></i> Histórico
            </a>

            <a href="recibo_pagamentos.php" class="btn btn-receipt">
                <i class="fa-solid fa-file-invoice-dollar"></i> Recibos / Notas
            </a>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="home.php" style="color: #666; text-decoration: none;">Voltar ao Início</a>
    </div>

</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>