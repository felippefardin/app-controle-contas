<?php
// ----------------------------------------------
// home.php (Ajustado com Flash Messages)
// ----------------------------------------------
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php'; // Importante para o flash message

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

// 📌 Conexão Master
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
    
    $connMaster->close();
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
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    /* REFORÇANDO O TEMA DARK */
    body {
        background-color: #121212 !important;
        color: #eee !important;
    }
    a { text-decoration: none !important; }

    .home-container {
        max-width: 1000px;
        margin: auto;
        padding: 20px;
        animation: fadeIn 0.8s ease;
    }
    h1 { text-align: center; color: #00bfff; margin-bottom: 5px; }
    h3 { text-align: center; color: #ccc; font-weight: 400; margin-bottom: 20px; }
    .saudacao { text-align: center; margin-bottom: 25px; color: #ddd; font-size: 1.1rem; }
    .section-title {
        width: 100%;
        color: #00bfff;
        font-weight: bold;
        margin-top: 30px;
        margin-bottom: 10px;
        border-bottom: 1px solid #333;
        padding-bottom: 5px;
    }
    .dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 18px;
        margin-bottom: 30px;
    }
    .card-link {
        background: #1e1e1e;
        padding: 25px 15px;
        text-align: center;
        border-radius: 10px;
        text-decoration: none;
        color: #fff;
        transition: 0.3s;
        box-shadow: 0 3px 6px rgba(0,0,0,0.4);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    a.card-link { color: #fff !important; text-decoration: none; }

    .card-link i { font-size: 2rem; margin-bottom: 5px; color: #00bfff; }
    .card-link:hover {
        background: #00bfff;
        color: #121212 !important;
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,191,255,0.4);
    }
    .card-link:hover i { color: #121212; }
    
    .alert-estoque {
        background: #dc3545;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        color: #fff;
    }
    .chat-alert {
        background-color: #ff4444;
        color: white;
        padding: 15px;
        text-align: center;
        font-weight: bold;
        cursor: pointer;
        margin-bottom: 20px;
        border-radius: 5px;
        animation: pulse 2s infinite;
        display: block;
        border: 2px solid #fff;
        text-decoration: none;
    }
    .chat-alert:hover { color: #fff; text-decoration: none; }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 68, 68, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 68, 68, 0); }
    }
    /* CSS DO MODAL CUSTOMIZADO */
    .custom-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); }
    .custom-modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; color: #333; }
    .custom-modal-header h5 { margin: 0; font-size: 1.5rem; }
    .custom-modal-body { margin: 20px 0; line-height: 1.6; font-size: 1rem; }
    .custom-modal-footer { text-align: right; }
    .btn-modal { padding: 10px 20px; cursor: pointer; border: none; border-radius: 5px; font-weight: bold; }
    .btn-cancel { background: #ccc; color: #333; margin-right: 10px; }
    .btn-accept { background: #28a745; color: #fff; }
    #toast-lembrete { visibility: hidden; min-width: 300px; background-color: #00bfff; color: #121212; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; bottom: 30px; font-size: 17px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.5); cursor: pointer; opacity: 0; transition: opacity 0.5s, bottom 0.5s; }
    #toast-lembrete.show { visibility: visible; opacity: 1; bottom: 50px; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<?php 
// EXIBE O FLASH MESSAGE (Login sucesso, etc)
display_flash_message(); 
?>

<div class="home-container">
    <h1>App Controle de Contas</h1>
    <h3>Olá, <?= htmlspecialchars($nome_usuario) ?> — Perfil: <?= htmlspecialchars($perfil) ?></h3>
    <p class="saudacao" id="saudacao"></p>

    <?php if ($conviteChat): ?>
        <div class="chat-alert" onclick="abrirModalChat(<?php echo $conviteChat['id']; ?>)">
            <i class="fas fa-headset"></i> O Suporte solicitou um Chat Online com você. Clique aqui para atender.
        </div>
    <?php endif; ?>

    <?php if (!empty($produtos_estoque_baixo) && temPermissao('controle_estoque.php', $permissoes_usuario, $perfil)): ?>
        <div class="alert-estoque">
            <strong>⚠ Produtos com estoque baixo:</strong>
            <ul>
                <?php foreach ($produtos_estoque_baixo as $p): ?>
                    <li><?= htmlspecialchars($p['nome']) ?> — Estoque: <?= intval($p['quantidade_estoque']) ?></li>
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
                <a class="card-link" href="contas_pagar.php"><i class="fas fa-file-invoice-dollar"></i> Contas a Pagar</a>
            <?php endif; ?>
            <?php if (temPermissao('contas_pagar_baixadas.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_pagar_baixadas.php"><i class="fas fa-check-double"></i> Pagas</a>
            <?php endif; ?>
            <?php if (temPermissao('contas_receber.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_receber.php"><i class="fas fa-hand-holding-dollar"></i> Contas a Receber</a>
            <?php endif; ?>
            <?php if (temPermissao('contas_receber_baixadas.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="contas_receber_baixadas.php"><i class="fas fa-clipboard-check"></i> Recebidas</a>
            <?php endif; ?>
            <?php if (temPermissao('lancamento_caixa.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="lancamento_caixa.php"><i class="fas fa-exchange-alt"></i> Fluxo de Caixa</a>
            <?php endif; ?>
            <?php if (temPermissao('vendas_periodo.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="vendas_periodo.php"><i class="fas fa-chart-line"></i> Vendas e Comissão</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php 
    $estoque_items = ['controle_estoque.php', 'vendas.php', 'compras.php'];
    $show_estoque = false;
    foreach($estoque_items as $item) { if(temPermissao($item, $permissoes_usuario, $perfil)) $show_estoque = true; }
    ?>
    <?php if($show_estoque): ?>
        <div class="section-title"><i class="fas fa-boxes"></i> Estoque & Vendas</div>
        <div class="dashboard">
            <?php if (temPermissao('controle_estoque.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="controle_estoque.php"><i class="fas fa-boxes-stacked"></i> Estoque</a>
            <?php endif; ?>
            <?php if (temPermissao('vendas.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="vendas.php"><i class="fas fa-cash-register"></i> Caixa de Vendas</a>
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
                <a class="card-link" href="../pages/cadastrar_pessoa_fornecedor.php"><i class="fas fa-address-book"></i> Clientes/Forn.</a>
            <?php endif; ?>
            <?php if (temPermissao('perfil.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="perfil.php"><i class="fas fa-user-circle"></i> Perfil</a>
            <?php endif; ?>
            <?php if (temPermissao('banco_cadastro.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="../pages/banco_cadastro.php"><i class="fas fa-university"></i> Contas Bancárias</a>
            <?php endif; ?>
            <?php if (temPermissao('categorias.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="../pages/categorias.php"><i class="fas fa-tags"></i> Categorias</a>
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
                <a class="card-link" href="selecionar_usuario.php"><i class="fas fa-users-cog"></i> Trocar Usuário</a>
            <?php endif; ?>

            <?php if (temPermissao('usuarios.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <?php endif; ?>
            <?php if (temPermissao('configuracao_fiscal.php', $permissoes_usuario, $perfil)): ?>
                <a class="card-link" href="configuracao_fiscal.php"><i class="fas fa-file-invoice"></i> Config. Fiscal</a>
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
        <i class="fas fa-bell"></i> Você tem lembretes para hoje!
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
function atualizarSaudacao() {
    const agora = new Date();
    const hora = agora.getHours();
    const minutos = String(agora.getMinutes()).padStart(2, '0');
    const dia = String(agora.getDate()).padStart(2, '0');
    const mes = String(agora.getMonth() + 1).padStart(2, '0');
    const ano = agora.getFullYear();
    let texto = "Bem-vindo(a)!";
    if (hora < 12) texto = "☀️ Bom dia!";
    else if (hora < 18) texto = "🌤️ Boa tarde!";
    else texto = "🌙 Boa noite!";
    const dataFormatada = `${dia}/${mes}/${ano}`;
    const horaFormatada = `${hora}:${minutos}`;
    document.getElementById("saudacao").innerHTML = `${texto} <br><span style="font-size: 0.9em; color: #aaa;">${dataFormatada} — ${horaFormatada}</span>`;
}
atualizarSaudacao();
setInterval(atualizarSaudacao, 60000);

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

<?php include('../includes/footer.php'); ?>