<?php
// ----------------------------------------------
// home.php (DASHBOARD FULL WIDTH + LISTAS DE HOJE)
// ----------------------------------------------
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php';

// 🔒 Usuário precisa estar logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.php?erro=nao_logado");
    exit();
}

// 🔒 Se for o Master Admin sem impersonação, redireciona
if (
    isset($_SESSION['is_master_admin']) &&
    $_SESSION['is_master_admin'] === true &&
    !isset($_SESSION['proprietario_id_original']) && 
    !isset($_SESSION['super_admin_original']) 
) {
    header("Location: ../pages/admin/dashboard.php");
    exit();
}

// 🔒 Verificar tenant ativo
if (!isset($_SESSION['tenant_id'])) {
    session_destroy();
    header("Location: ../pages/login.php?erro=tenant_inexistente");
    exit();
}

// 📌 Pega dados do usuário
$usuario_id    = $_SESSION['usuario_id'];
$tenant_id     = $_SESSION['tenant_id'];
$nome_usuario  = $_SESSION['nome'];
$perfil        = $_SESSION['nivel_acesso']; 

// 📌 Conexão do tenant
$conn = getTenantConnection();
if (!$conn) {
    session_destroy();
    header("Location: ../pages/login.php?erro=db_tenant");
    exit();
}

// ==========================================================================
// 🔍 LÓGICA DE PERMISSÕES
// ==========================================================================
$permissoes_usuario = [];
if ($perfil !== 'admin' && $perfil !== 'proprietario' && $perfil !== 'master') {
    $stmtPerm = $conn->prepare("SELECT permissoes FROM usuarios WHERE id = ?");
    if ($stmtPerm) {
        $stmtPerm->bind_param("i", $usuario_id);
        $stmtPerm->execute();
        $resPerm = $stmtPerm->get_result();
        if ($rowPerm = $resPerm->fetch_assoc()) {
            $json = $rowPerm['permissoes'];
            if (!empty($json)) {
                $permissoes_usuario = json_decode($json, true);
            }
        }
        $stmtPerm->close();
    }
    if (!is_array($permissoes_usuario)) $permissoes_usuario = [];
}

function temPermissao($arquivo_chave, $permissoes_array, $perfil_atual) {
    if ($perfil_atual === 'admin' || $perfil_atual === 'proprietario' || $perfil_atual === 'master') {
        return true;
    }
    return in_array($arquivo_chave, $permissoes_array);
}

// ==========================================================================
// 📊 LÓGICA DO DASHBOARD
// ==========================================================================
$mesAtual = date('Y-m');
$hoje = date('Y-m-d');

// 1. Totais Rápidos (KPIs)
$sqlResumo = "
    SELECT 
        (SELECT COALESCE(SUM(valor), 0) FROM contas_receber WHERE usuario_id = ? AND status = 'pendente' AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?) as receber_mes,
        (SELECT COALESCE(SUM(valor), 0) FROM contas_pagar WHERE usuario_id = ? AND status = 'pendente' AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?) as pagar_mes,
        (SELECT COALESCE(SUM(valor), 0) FROM contas_pagar WHERE usuario_id = ? AND status = 'pendente' AND data_vencimento = ?) as pagar_hoje
";
$stmtDash = $conn->prepare($sqlResumo);
$stmtDash->bind_param("isisis", $usuario_id, $mesAtual, $usuario_id, $mesAtual, $usuario_id, $hoje);
$stmtDash->execute();
$dashData = $stmtDash->get_result()->fetch_assoc();
$stmtDash->close();

// 2. Saldo em Caixa (Estimado: Recebido + Caixa - Pago)
$sqlSaldo = "
    SELECT 
    (COALESCE((SELECT SUM(valor) FROM contas_receber WHERE usuario_id = ? AND status = 'baixada'),0) + 
     COALESCE((SELECT SUM(valor) FROM caixa_diario WHERE usuario_id = ?),0)) 
    - 
    COALESCE((SELECT SUM(valor) FROM contas_pagar WHERE usuario_id = ? AND status = 'baixada'),0) 
    as saldo_real
";
$stmtSaldo = $conn->prepare($sqlSaldo);
$stmtSaldo->bind_param("iii", $usuario_id, $usuario_id, $usuario_id);
$stmtSaldo->execute();
$saldoCaixa = $stmtSaldo->get_result()->fetch_assoc()['saldo_real'] ?? 0;
$stmtSaldo->close();

// 3. Verifica se é Usuário Novo (Para Onboarding)
$novoUsuario = false;
$checkNew = $conn->query("SELECT id FROM contas_bancarias WHERE id_usuario = $usuario_id LIMIT 1");
if ($checkNew && $checkNew->num_rows == 0) {
    $novoUsuario = true;
}

// 4. Listas de Contas para HOJE (Substituindo Gráfico)
$listReceberHoje = [];
$qRH = $conn->prepare("SELECT descricao, valor FROM contas_receber WHERE usuario_id = ? AND status = 'pendente' AND data_vencimento = ? ORDER BY valor DESC");
$qRH->bind_param("is", $usuario_id, $hoje);
$qRH->execute();
$resRH = $qRH->get_result();
while($row = $resRH->fetch_assoc()) { $listReceberHoje[] = $row; }
$qRH->close();

$listPagarHoje = [];
$qPH = $conn->prepare("SELECT descricao, valor FROM contas_pagar WHERE usuario_id = ? AND status = 'pendente' AND data_vencimento = ? ORDER BY valor DESC");
$qPH->bind_param("is", $usuario_id, $hoje);
$qPH->execute();
$resPH = $qPH->get_result();
while($row = $resPH->fetch_assoc()) { $listPagarHoje[] = $row; }
$qPH->close();

// ==========================================================================


// 📌 Conexão Master e Assinatura
$connMaster = getMasterConnection();
$status_assinatura = 'ok';

if ($connMaster) {
    // 1. Verifica Assinatura
    $tenant = getTenantById($tenant_id, $connMaster);
    if ($tenant) {
        $_SESSION['subscription_status'] = validarStatusAssinatura($tenant);
        $status_assinatura = $_SESSION['subscription_status'];
    }

    // 2. LÓGICA DE CHAT SUPORTE
    $conviteChat = null;
    try {
        $master_usuario_id = isset($tenant['usuario_id']) ? $tenant['usuario_id'] : 0;
        $sqlChat = "SELECT id FROM chat_sessions WHERE usuario_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1";
        $stmtChat = $connMaster->prepare($sqlChat);
        if ($stmtChat) {
            $stmtChat->bind_param("i", $master_usuario_id);
            $stmtChat->execute();
            $resChat = $stmtChat->get_result();
            $conviteChat = $resChat->fetch_assoc();
            $stmtChat->close();
        }
    } catch (Exception $e) { }
}

// --- LÓGICA DE LEMBRETES ---
$popupLembrete = false;
try {
    if (temPermissao('lembretes.php', $permissoes_usuario, $perfil)) {
        $checkTable = $conn->query("SHOW TABLES LIKE 'lembretes'");
        if($checkTable && $checkTable->num_rows > 0) {
            $sqlLembrete = "SELECT COUNT(*) as total FROM lembretes WHERE usuario_id = ? AND data_lembrete = CURDATE()";
            $stmtL = $conn->prepare($sqlLembrete);
            if ($stmtL) {
                $stmtL->bind_param("i", $usuario_id);
                $stmtL->execute();
                $resL = $stmtL->get_result();
                $rowL = $resL->fetch_assoc();
                if ($rowL['total'] > 0) {
                    $popupLembrete = true;
                }
                $stmtL->close();
            }
        }
    }
} catch (Exception $e) { }

$produtos_estoque_baixo = $_SESSION['produtos_estoque_baixo'] ?? [];
unset($_SESSION['produtos_estoque_baixo']);

include('../includes/header.php');

// --- LÓGICA DE PROMOÇÃO ESPECIAL (GIFT) ---
$promo_modal = null;
if ($tenant_id && $connMaster) {
    $sqlPromo = "
        SELECT tp.id, c.descricao, c.valor, c.tipo_desconto 
        FROM tenant_promocoes tp
        JOIN cupons_desconto c ON tp.cupom_id = c.id
        WHERE tp.tenant_id = ? AND tp.visualizado = 0 AND tp.ativo = 1
        LIMIT 1
    ";
    $stmtP = $connMaster->prepare($sqlPromo);
    if ($stmtP) {
        $tenant_id_numeric = $_SESSION['tenant_id_master'] ?? null;
        if (!$tenant_id_numeric) {
            $t_uuid = $_SESSION['tenant_id'];
            $qT = $connMaster->query("SELECT id FROM tenants WHERE tenant_id = '$t_uuid' LIMIT 1");
            if($qT && $rowT = $qT->fetch_assoc()){
                $tenant_id_numeric = $rowT['id'];
            }
        }
        if ($tenant_id_numeric) {
            $stmtP->bind_param("i", $tenant_id_numeric);
            $stmtP->execute();
            $resPromo = $stmtP->get_result();
            $promo_modal = $resPromo->fetch_assoc();
        }
        $stmtP->close();
    }
    $connMaster->close();
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    /* =========================================
       1. AJUSTES PARA TELA CHEIA (FULL WIDTH)
    ========================================= */
    main {
        max-width: 100% !important;
        padding-left: 20px !important;
        padding-right: 20px !important;
        margin: 0 !important;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
        background-color: var(--bg-body); 
        font-family: Arial, sans-serif;
        overflow-x: hidden;
    }

    .home-container {
        max-width: 100%; 
        width: 100%;
        margin: 0;
        padding: 10px;
        animation: fadeIn 0.8s ease;
    }

    .btn-home { background-color: #28a745 !important; border-color: #28a745 !important; color: #fff !important; }
    .btn-exit { background-color: #dc3545 !important; border-color: #dc3545 !important; color: #fff !important; }

    /* =========================================
       ELEMENTOS DA DASHBOARD
    ========================================= */
    .dashboard-kpi {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    .kpi-card {
        padding: 20px;
        border-radius: 12px;
        background: var(--bg-card, #1e1e1e); 
        color: var(--text-primary, #fff);
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        border-left: 5px solid #00bfff;
        transition: transform 0.2s;
    }
    .kpi-card:hover { transform: translateY(-5px); }
    .kpi-title { font-size: 0.9rem; opacity: 0.8; margin-bottom: 5px; text-transform: uppercase; }
    .kpi-value { font-size: 1.8rem; font-weight: bold; }
    
    .text-danger-custom { color: #ff4444 !important; }
    .text-success-custom { color: #00C851 !important; }

    /* ESTILOS PARA AS LISTAS DE HOJE */
    .today-card {
        background: var(--bg-card, #1e1e1e);
        color: var(--text-primary, #fff);
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        overflow: hidden;
        height: 100%;
        margin-bottom: 25px;
    }
    .today-header {
        padding: 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(128,128,128,0.1);
    }
    .today-list-container {
        max-height: 250px;
        overflow-y: auto;
    }
    .today-item {
        padding: 12px 15px;
        border-bottom: 1px solid rgba(128,128,128,0.1);
        display: flex;
        justify-content: space-between;
        font-size: 0.95rem;
    }
    .today-item:last-child { border-bottom: none; }
    .empty-msg { padding: 20px; text-align: center; font-size: 0.9rem; opacity: 0.7; }

    /* Onboarding */
    .onboarding-card {
        background: linear-gradient(135deg, #00bfff 0%, #0066cc 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        text-align: center;
        animation: slideDown 0.8s ease;
    }
    .onboarding-steps { display: flex; justify-content: center; gap: 15px; margin-top: 15px; flex-wrap: wrap; }
    .step-btn { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.5); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: bold; transition: 0.3s; font-size: 0.9rem; }
    .step-btn:hover { background: white; color: #0066cc; transform: scale(1.05); }
    @keyframes slideDown { from {opacity:0; transform:translateY(-20px);} to {opacity:1; transform:translateY(0);} }

    /* Layout */
    h1 { text-align: center; color: var(--highlight-color, #00bfff); margin-bottom: 2px; font-size: 1.5rem; }
    h3 { text-align: center; color: var(--text-secondary, #ccc); font-weight: 400; margin-bottom: 10px; font-size: 1rem; }
    .saudacao { text-align: center; margin-bottom: 15px; color: var(--text-secondary, #ddd); font-size: 0.9rem; }
    
    .section-title {
        width: 100%;
        color: var(--highlight-color, #00bfff);
        font-weight: bold;
        margin-top: 15px; margin-bottom: 8px;
        border-bottom: 1px solid #333;
        padding-bottom: 2px;
        font-size: 0.95rem;
    }
    .dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(125px, 1fr));
        gap: 10px; margin-bottom: 15px;
    }
    .card-link {
        background: var(--bg-card, #1e1e1e);
        padding: 12px 5px;
        text-align: center;
        border-radius: 8px;
        text-decoration: none;
        color: var(--text-primary, #fff);
        transition: 0.3s;
        box-shadow: 0 3px 6px rgba(0,0,0,0.4);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px; font-size: 0.85rem;
    }
    a.card-link { color: var(--text-primary, #fff) !important; text-decoration: none; }
    .card-link i { font-size: 1.4rem; margin-bottom: 2px; color: var(--highlight-color, #00bfff); }
    .card-link:hover {
        background: var(--highlight-color, #00bfff);
        color: #121212 !important;
        transform: translateY(-3px);
    }
    .card-link:hover i { color: #121212; }
    
    .alert-estoque { background: #dc3545; padding: 10px; border-radius: 8px; margin-bottom: 15px; color: #fff; font-size: 0.9rem; }
    .chat-alert { background-color: #ff4444; color: white; padding: 10px; text-align: center; font-weight: bold; cursor: pointer; margin-bottom: 15px; border-radius: 5px; animation: pulse 2s infinite; display: block; border: 2px solid #fff; text-decoration: none; font-size: 0.9rem; }
    
    /* Modal e Toast */
    .custom-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
    .custom-modal-content { background-color: var(--bg-card, #1f1f1f); margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; color: var(--text-primary, #333); }
    #toast-lembrete { visibility: hidden; min-width: 300px; background-color: #00bfff; color: #121212; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; bottom: 30px; font-size: 17px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.5); cursor: pointer; opacity: 0; transition: opacity 0.5s, bottom 0.5s; }
    #toast-lembrete.show { visibility: visible; opacity: 1; bottom: 50px; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<?php display_flash_message(); ?>

<div class="home-container">
    <h1>App Controle</h1>
    <h3><?= htmlspecialchars($nome_usuario) ?> — <?= htmlspecialchars($perfil) ?></h3>
    <p class="saudacao" id="saudacao"></p>

    <?php if ($novoUsuario): ?>
    <div class="onboarding-card">
        <h4>🚀 Bem-vindo ao seu Controle Financeiro!</h4>
        <p style="margin-bottom:0">Para começar, configure o básico do seu sistema:</p>
        <div class="onboarding-steps">
            <a href="banco_cadastro.php" class="step-btn">1. Cadastrar Banco</a>
            <a href="cadastrar_pessoa_fornecedor.php" class="step-btn">2. Add Cliente/Fornecedor</a>
            <a href="contas_receber.php" class="step-btn">3. Lançar 1ª Receita</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-kpi">
        <div class="kpi-card" style="border-left-color: #00C851;"> 
            <div class="kpi-title">Saldo em Caixa</div>
            <div class="kpi-value <?= $saldoCaixa >= 0 ? 'text-success-custom' : 'text-danger-custom' ?>">
                R$ <?= number_format($saldoCaixa, 2, ',', '.') ?>
            </div>
            <small style="opacity:0.7">Disponível agora</small>
        </div>

        <div class="kpi-card" style="border-left-color: #ff4444;">
            <div class="kpi-title">A Pagar Hoje</div>
            <div class="kpi-value text-danger-custom">
                R$ <?= number_format($dashData['pagar_hoje'] ?? 0, 2, ',', '.') ?>
            </div>
            <small style="opacity:0.7">Vence hoje (<?= date('d/m') ?>)</small>
        </div>

        <div class="kpi-card" style="border-left-color: #33b5e5;">
            <div class="kpi-title">Receita (Mês)</div>
            <div class="kpi-value">
                R$ <?= number_format($dashData['receber_mes'] ?? 0, 2, ',', '.') ?>
            </div>
            <small style="opacity:0.7">Pendentes em <?= date('M') ?></small>
        </div>

        <div class="kpi-card" style="border-left-color: #ffbb33;">
            <div class="kpi-title">Despesa (Mês)</div>
            <div class="kpi-value">
                R$ <?= number_format($dashData['pagar_mes'] ?? 0, 2, ',', '.') ?>
            </div>
            <small style="opacity:0.7">Pendentes em <?= date('M') ?></small>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="today-card">
                <div class="today-header" style="background: rgba(0, 200, 81, 0.15); color: #00C851;">
                    <span><i class="fas fa-arrow-down"></i> A Receber Hoje (<?= date('d/m') ?>)</span>
                    <span class="badge bg-success rounded-pill"><?= count($listReceberHoje) ?></span>
                </div>
                <div class="today-list-container">
                    <?php if (empty($listReceberHoje)): ?>
                        <div class="empty-msg">Nenhuma conta para receber hoje.</div>
                    <?php else: ?>
                        <?php foreach($listReceberHoje as $item): ?>
                            <div class="today-item">
                                <span><?= htmlspecialchars($item['descricao']) ?></span>
                                <strong style="color: #00C851;">R$ <?= number_format($item['valor'], 2, ',', '.') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="today-card">
                <div class="today-header" style="background: rgba(255, 68, 68, 0.15); color: #ff4444;">
                    <span><i class="fas fa-arrow-up"></i> A Pagar Hoje (<?= date('d/m') ?>)</span>
                    <span class="badge bg-danger rounded-pill"><?= count($listPagarHoje) ?></span>
                </div>
                <div class="today-list-container">
                    <?php if (empty($listPagarHoje)): ?>
                        <div class="empty-msg">Nenhuma conta para pagar hoje.</div>
                    <?php else: ?>
                        <?php foreach($listPagarHoje as $item): ?>
                            <div class="today-item">
                                <span><?= htmlspecialchars($item['descricao']) ?></span>
                                <strong style="color: #ff4444;">R$ <?= number_format($item['valor'], 2, ',', '.') ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if ($conviteChat): ?>
        <div class="chat-alert" onclick="abrirModalChat(<?php echo $conviteChat['id']; ?>)">
            <i class="fas fa-headset"></i> Suporte Online Solicitado
        </div>
    <?php endif; ?>

    <?php if (!empty($produtos_estoque_baixo) && temPermissao('controle_estoque.php', $permissoes_usuario, $perfil)): ?>
        <div class="alert-estoque">
            <strong>⚠ Estoque Baixo:</strong>
            <ul style="margin-bottom: 0; padding-left: 20px;">
                <?php foreach ($produtos_estoque_baixo as $p): ?>
                    <li><?= htmlspecialchars($p['nome']) ?> (<?= intval($p['quantidade_estoque']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php 
    $financeiro_items = ['contas_pagar.php', 'contas_pagar_baixadas.php', 'contas_receber.php', 'contas_receber_baixadas.php', 'lancamento_caixa.php', 'vendas_periodo.php'];
    $show_financeiro = false;
    foreach($financeiro_items as $item) { if(temPermissao($item, $permissoes_usuario, $perfil)) $show_financeiro = true; }
    ?>
    <?php if($show_financeiro): ?>
        <div class="section-title"><i class="fas fa-wallet"></i> Financeiro</div>
        <div class="dashboard">
            <?php if (temPermissao('contas_pagar.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_pagar.php"><i class="fas fa-file-invoice-dollar"></i> A Pagar</a>
            <?php endif; ?>
            <?php if (temPermissao('contas_pagar_baixadas.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_pagar_baixadas.php"><i class="fas fa-check-double"></i> Pagas</a>
            <?php endif; ?>
            <?php if (temPermissao('contas_receber.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_receber.php"><i class="fas fa-hand-holding-dollar"></i> A Receber</a>
            <?php endif; ?>
            <?php if (temPermissao('contas_receber_baixadas.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_receber_baixadas.php"><i class="fas fa-clipboard-check"></i> Recebidas</a>
            <?php endif; ?>
            <?php if (temPermissao('lancamento_caixa.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="lancamento_caixa.php"><i class="fas fa-exchange-alt"></i> Caixa</a>
            <?php endif; ?>
            <?php if (temPermissao('vendas_periodo.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="vendas_periodo.php"><i class="fas fa-chart-line"></i> Vendas</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php 
    $estoque_items = ['controle_estoque.php', 'vendas.php', 'compras.php'];
    $show_estoque = false;
    foreach($estoque_items as $item) { if(temPermissao($item, $permissoes_usuario, $perfil)) $show_estoque = true; }
    ?>
    <?php if($show_estoque): ?>
        <div class="section-title"><i class="fas fa-boxes"></i> Estoque/Vendas</div>
        <div class="dashboard">
            <?php if (temPermissao('controle_estoque.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="controle_estoque.php"><i class="fas fa-boxes-stacked"></i> Estoque</a>
            <?php endif; ?>
            <?php if (temPermissao('vendas.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="vendas.php"><i class="fas fa-cash-register"></i> PDV</a>
            <?php endif; ?>
            <?php if (temPermissao('compras.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="compras.php"><i class="fas fa-shopping-bag"></i> Compras</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php 
    $cadastro_items = ['cadastrar_pessoa_fornecedor.php', 'perfil.php', 'banco_cadastro.php', 'categorias.php'];
    $show_cadastro = false;
    foreach($cadastro_items as $item) { if(temPermissao($item, $permissoes_usuario, $perfil)) $show_cadastro = true; }
    ?>
    <?php if($show_cadastro): ?>
        <div class="section-title"><i class="fas fa-users"></i> Cadastros</div>
        <div class="dashboard">
            <?php if (temPermissao('cadastrar_pessoa_fornecedor.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="../pages/cadastrar_pessoa_fornecedor.php"><i class="fas fa-address-book"></i> Pessoas</a>
            <?php endif; ?>
            <?php if (temPermissao('perfil.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="perfil.php"><i class="fas fa-user-circle"></i> Perfil</a>
            <?php endif; ?>
            <?php if (temPermissao('banco_cadastro.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="../pages/banco_cadastro.php"><i class="fas fa-university"></i> Bancos</a>
            <?php endif; ?>
            <?php if (temPermissao('categorias.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="../pages/categorias.php"><i class="fas fa-tags"></i> Categ.</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php 
    $sistema_items = ['lembretes.php', 'relatorios.php', 'trocar_usuario.php', 'usuarios.php', 'configuracao_fiscal.php'];
    $show_sistema = false;
    foreach($sistema_items as $item) { if(temPermissao($item, $permissoes_usuario, $perfil)) $show_sistema = true; }
    ?>
    <?php if($show_sistema): ?>
        <div class="section-title"><i class="fas fa-cogs"></i> Sistema</div>
        <div class="dashboard">
            <?php if (temPermissao('lembretes.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="lembrete.php"><i class="fas fa-sticky-note"></i> Lembretes</a>
            <?php endif; ?>
            <?php if (temPermissao('relatorios.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="relatorios.php"><i class="fas fa-chart-pie"></i> Relatórios</a>
            <?php endif; ?>
            
            <?php if (temPermissao('trocar_usuario.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="selecionar_usuario.php"><i class="fas fa-users-cog"></i> Trocar</a>
            <?php endif; ?>

            <?php if (temPermissao('usuarios.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <?php endif; ?>
            <?php if (temPermissao('configuracao_fiscal.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="configuracao_fiscal.php"><i class="fas fa-file-invoice"></i> Fiscal</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<div id="modalChatInvite" class="custom-modal">
  <div class="custom-modal-content">
    <div class="custom-modal-header"><h5>Convite de Suporte Online</h5></div>
    <div class="custom-modal-body">
      <p>Você tem 1 hora para tirar suas dúvidas...</p>
    </div>
    <div class="custom-modal-footer">
      <button type="button" class="btn-modal btn-cancel" onclick="document.getElementById('modalChatInvite').style.display='none'">Cancelar</button>
      <button type="button" class="btn-modal btn-accept" id="btnAceitarChat">Aceitar e Iniciar</button>
    </div>
  </div>
</div>

<?php if ($popupLembrete): ?>
    <div id="toast-lembrete" onclick="window.location.href='lembrete.php'">
        <i class="fas fa-bell"></i> Lembretes hoje!
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var x = document.getElementById("toast-lembrete");
            x.className = "show";
            setTimeout(function(){ x.className = x.className.replace("show", ""); }, 4000);
        });
    </script>
<?php endif; ?>

<script>
// SAUDAÇÃO INTELIGENTE
function atualizarSaudacao() {
    const agora = new Date();
    const hora = agora.getHours();
    let texto = "Bem-vindo(a)!";
    if (hora < 12) texto = "☀️ Bom dia!";
    else if (hora < 18) texto = "🌤️ Boa tarde!";
    else texto = "🌙 Boa noite!";
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dataStr = agora.toLocaleDateString('pt-BR', options);
    
    document.getElementById("saudacao").innerHTML = `<strong>${texto}</strong><br><span style="font-size: 0.85em; opacity: 0.8;">${dataStr}</span>`;
}
atualizarSaudacao();
setInterval(atualizarSaudacao, 60000);

// LÓGICA DO CHAT (MANTIDA)
let currentChatId = null;
function abrirModalChat(chatId) {
    currentChatId = chatId;
    document.getElementById('modalChatInvite').style.display = 'block';
}
document.getElementById('btnAceitarChat').addEventListener('click', function() {
    if(!currentChatId) return;
    const formData = new FormData();
    formData.append('action', 'aceitar_convite');
    formData.append('chat_id', currentChatId);
    fetch('../actions/chat_api.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            window.location.href = 'chat_suporte_online.php?chat_id=' + currentChatId;
        } else {
            alert('Erro ao iniciar o chat.');
        }
    });
});
</script>

<?php if ($promo_modal): ?>
<div id="modalGift" class="custom-modal" style="display:block;">
    <div class="custom-modal-content" style="text-align:center; border: 1px solid #e67e22; box-shadow: 0 0 20px rgba(230, 126, 34, 0.5);">
        <div style="font-size: 3rem; color: #e67e22; margin-bottom: 10px;">
            <i class="fas fa-gift"></i>
        </div>
        <h2 style="color: #fff;">Você ganhou um presente!</h2>
        <p style="font-size: 1.2rem; color: #ccc; margin: 20px 0;">
            Parabéns! Você recebeu um presente do sistema:<br><br>
            <strong style="color: #00bfff; font-size: 1.3rem;">
                <?= htmlspecialchars($promo_modal['descricao']) ?>
            </strong>
        </p>
        <p style="font-size: 0.9rem; color: #888;">O desconto será aplicado automaticamente na sua próxima fatura.</p>
        <p style="margin-top:20px; font-weight:bold; color: #eee;">Obrigado por ser nosso parceiro!</p>
        
        <button class="btn-modal btn-accept" style="background:#e67e22; width:100%; padding: 15px; font-size:1.1rem;" onclick="fecharGift(<?= $promo_modal['id'] ?>)">
            OK, Obrigado!
        </button>
    </div>
</div>

<script>
function fecharGift(promoId) {
    // Ajax para marcar como visualizado
    fetch('../actions/marcar_promocao_lida.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + promoId
    }).then(() => {
        document.getElementById('modalGift').style.display = 'none';
    });
}
</script>
<?php endif; ?>

<?php include('../includes/mensagem_home_display.php'); ?>
<?php include('../includes/footer.php'); ?>