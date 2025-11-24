<?php
// ----------------------------------------------
// home.php (Atualizado com Permissões por Plano)
// ----------------------------------------------
require_once '../includes/session_init.php';
require_once '../database.php';

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
$perfil        = $_SESSION['nivel_acesso']; // admin ou padrao

// 📌 Conexão do tenant
$conn = getTenantConnection();
if (!$conn) {
    session_destroy();
    header("Location: ../pages/login.php?erro=db_tenant");
    exit();
}

// 📌 Buscar Plano Atual do Tenant e Permissões do Usuário
$plano_tenant = 'basico'; // Default
$permissoes_usuario = [];

// Busca plano no banco Master
$connMaster = getMasterConnection();
if ($connMaster) {
    $tenant = getTenantById($tenant_id, $connMaster);
    if ($tenant) {
        $plano_tenant = $tenant['plano_atual'] ?? 'basico';
        $_SESSION['subscription_status'] = validarStatusAssinatura($tenant);
    }
    $connMaster->close();
}

// Busca permissões específicas do usuário no banco do Tenant
$stmtPerm = $conn->prepare("SELECT permissoes FROM usuarios WHERE id = ?");
$stmtPerm->bind_param("i", $usuario_id);
$stmtPerm->execute();
$resPerm = $stmtPerm->get_result();
if ($rowPerm = $resPerm->fetch_assoc()) {
    // Se for admin/proprietario, tem permissão total (respeitando o plano)
    // Se for padrao, usa o JSON salvo. Se NULL, array vazio.
    $json_perm = $rowPerm['permissoes'];
    $permissoes_usuario = $json_perm ? json_decode($json_perm, true) : [];
}
$stmtPerm->close();

// --- DEFINIÇÃO DOS ITENS POR PLANO ---

// Itens disponíveis para TODOS (Básico, Plus, Essencial)
$itens_basicos = [
    'contas_pagar.php' => ['titulo' => 'Contas a Pagar', 'icone' => 'fa-file-invoice-dollar'],
    'contas_pagar_baixadas.php' => ['titulo' => 'Pagas', 'icone' => 'fa-check-double'],
    'contas_receber.php' => ['titulo' => 'Contas a Receber', 'icone' => 'fa-hand-holding-dollar'],
    'contas_receber_baixadas.php' => ['titulo' => 'Recebidas', 'icone' => 'fa-clipboard-check'],
    'lembretes.php' => ['titulo' => 'Lembretes', 'icone' => 'fa-sticky-note'],
    'perfil.php' => ['titulo' => 'Perfil', 'icone' => 'fa-user-circle'],
    'trocar_usuario.php' => ['titulo' => 'Trocar Usuário', 'icone' => 'fa-users-cog'],
    'usuarios.php' => ['titulo' => 'Usuários', 'icone' => 'fa-users']
];

// Itens EXCLUSIVOS para Plus e Essencial
$itens_avancados = [
    'lancamento_caixa.php' => ['titulo' => 'Fluxo de Caixa', 'icone' => 'fa-exchange-alt'], // Adicionei aqui pois não estava na lista explícita do básico no prompt, mas costuma ser básico. Se for avançado, mantenha aqui.
    'vendas_periodo.php' => ['titulo' => 'Vendas e Comissão', 'icone' => 'fa-chart-line'],
    'controle_estoque.php' => ['titulo' => 'Estoque', 'icone' => 'fa-boxes-stacked'],
    'vendas.php' => ['titulo' => 'Caixa de Vendas', 'icone' => 'fa-cash-register'],
    'compras.php' => ['titulo' => 'Compras', 'icone' => 'fa-shopping-bag'],
    'cadastrar_pessoa_fornecedor.php' => ['titulo' => 'Clientes/Forn.', 'icone' => 'fa-address-book'],
    'banco_cadastro.php' => ['titulo' => 'Contas Bancárias', 'icone' => 'fa-university'],
    'categorias.php' => ['titulo' => 'Categorias', 'icone' => 'fa-tags'],
    'relatorios.php' => ['titulo' => 'Relatórios', 'icone' => 'fa-chart-pie'],
    'configuracao_fiscal.php' => ['titulo' => 'Config. Fiscal', 'icone' => 'fa-file-invoice']
];

// Monta lista de itens permitidos pelo PLANO
$itens_disponiveis_plano = $itens_basicos;

if ($plano_tenant === 'plus' || $plano_tenant === 'essencial') {
    $itens_disponiveis_plano = array_merge($itens_basicos, $itens_avancados);
}

// Função para verificar acesso
function temPermissao($pagina, $perfil, $permissoes_usuario, $itens_disponiveis_plano) {
    // 1. A página existe no plano atual?
    if (!array_key_exists($pagina, $itens_disponiveis_plano)) {
        return false;
    }
    
    // 2. Se for Admin/Proprietário, tem acesso a tudo do plano
    if ($perfil === 'admin' || $perfil === 'proprietario') {
        return true;
    }

    // 3. Se for Usuário Padrão, verifica se está no array de permissões salvas
    if (is_array($permissoes_usuario) && in_array($pagina, $permissoes_usuario)) {
        return true;
    }

    return false;
}

// --- LÓGICA DE LEMBRETES (Mantida) ---
$popupLembrete = false;
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'lembretes'");
    if($checkTable && $checkTable->num_rows > 0) {
        $sqlLembrete = "SELECT COUNT(*) as total FROM lembretes WHERE usuario_id = ? AND data_lembrete = CURDATE()";
        $stmtL = $conn->prepare($sqlLembrete);
        if ($stmtL) {
            $stmtL->bind_param("i", $usuario_id);
            $stmtL->execute();
            $resL = $stmtL->get_result();
            $rowL = $resL->fetch_assoc();
            if ($rowL['total'] > 0) $popupLembrete = true;
            $stmtL->close();
        }
    }
} catch (Exception $e) {}

$mensagem = $_SESSION['sucesso_mensagem'] ?? null;
unset($_SESSION['sucesso_mensagem']);
$produtos_estoque_baixo = $_SESSION['produtos_estoque_baixo'] ?? [];
unset($_SESSION['produtos_estoque_baixo']);

include('../includes/header.php');
?>

<style>
    .home-container { max-width: 1000px; margin: auto; padding: 20px; animation: fadeIn 0.8s ease; }
    h1 { text-align: center; color: #00bfff; margin-bottom: 5px; }
    h3 { text-align: center; color: #ccc; font-weight: 400; margin-bottom: 20px; }
    .saudacao { text-align: center; margin-bottom: 25px; color: #ddd; font-size: 1.1rem; }
    .section-title { width: 100%; color: #00bfff; font-weight: bold; margin-top: 30px; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px; }
    .dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 30px; }
    .card-link { background: #1e1e1e; padding: 25px 15px; text-align: center; border-radius: 10px; text-decoration: none; color: #fff; transition: 0.3s; box-shadow: 0 3px 6px rgba(0,0,0,0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; }
    .card-link i { font-size: 2rem; margin-bottom: 5px; color: #00bfff; }
    .card-link:hover { background: #00bfff; color: #121212; transform: translateY(-5px); box-shadow: 0 6px 15px rgba(0,191,255,0.4); }
    .card-link:hover i { color: #121212; }
    .mensagem-sucesso { background: #28a745; padding: 12px 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; font-weight: bold; color: white; }
    .alert-estoque { background: #dc3545; padding: 15px; border-radius: 10px; margin-bottom: 25px; color: #fff; }
    #toast-lembrete { visibility: hidden; min-width: 300px; background-color: #00bfff; color: #121212; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; bottom: 30px; font-size: 17px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.5); cursor: pointer; opacity: 0; transition: opacity 0.5s, bottom 0.5s; }
    #toast-lembrete.show { visibility: visible; opacity: 1; bottom: 50px; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<div class="home-container">
    <h1>App Controle de Contas</h1>
    <h3>Olá, <?= htmlspecialchars($nome_usuario) ?> — Perfil: <?= htmlspecialchars(ucfirst($perfil)) ?> (Plano <?= ucfirst($plano_tenant) ?>)</h3>
    <p class="saudacao" id="saudacao"></p>

    <?php if ($mensagem): ?>
        <div class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if (!empty($produtos_estoque_baixo) && temPermissao('controle_estoque.php', $perfil, $permissoes_usuario, $itens_disponiveis_plano)): ?>
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
    $grupo_financeiro = ['contas_pagar.php', 'contas_pagar_baixadas.php', 'contas_receber.php', 'contas_receber_baixadas.php', 'lancamento_caixa.php', 'vendas_periodo.php'];
    $show_financeiro = false;
    foreach($grupo_financeiro as $p) { if(temPermissao($p, $perfil, $permissoes_usuario, $itens_disponiveis_plano)) $show_financeiro = true; }
    
    if ($show_financeiro): ?>
        <div class="section-title"><i class="fas fa-wallet"></i> Financeiro</div>
        <div class="dashboard">
            <?php foreach($grupo_financeiro as $page): 
                if(temPermissao($page, $perfil, $permissoes_usuario, $itens_disponiveis_plano)): 
                    $info = $itens_disponiveis_plano[$page] ?? ['titulo'=>'', 'icone'=>'']; ?>
                    <a class="card-link" href="<?= $page ?>">
                        <i class="fas <?= $info['icone'] ?>"></i> <?= $info['titulo'] ?>
                    </a>
            <?php endif; endforeach; ?>
        </div>
    <?php endif; ?>

    <?php 
    $grupo_estoque = ['controle_estoque.php', 'vendas.php', 'compras.php'];
    $show_estoque = false;
    foreach($grupo_estoque as $p) { if(temPermissao($p, $perfil, $permissoes_usuario, $itens_disponiveis_plano)) $show_estoque = true; }
    
    if ($show_estoque): ?>
        <div class="section-title"><i class="fas fa-boxes"></i> Estoque & Vendas</div>
        <div class="dashboard">
            <?php foreach($grupo_estoque as $page): 
                if(temPermissao($page, $perfil, $permissoes_usuario, $itens_disponiveis_plano)): 
                    $info = $itens_disponiveis_plano[$page] ?? ['titulo'=>'', 'icone'=>'']; ?>
                    <a class="card-link" href="<?= $page ?>">
                        <i class="fas <?= $info['icone'] ?>"></i> <?= $info['titulo'] ?>
                    </a>
            <?php endif; endforeach; ?>
        </div>
    <?php endif; ?>

    <?php 
    $grupo_cadastros = ['cadastrar_pessoa_fornecedor.php', 'perfil.php', 'banco_cadastro.php', 'categorias.php'];
    $show_cadastros = false;
    foreach($grupo_cadastros as $p) { if(temPermissao($p, $perfil, $permissoes_usuario, $itens_disponiveis_plano)) $show_cadastros = true; }
    
    if ($show_cadastros): ?>
        <div class="section-title"><i class="fas fa-users"></i> Cadastros</div>
        <div class="dashboard">
            <?php foreach($grupo_cadastros as $page): 
                // Ajuste de caminho para cadastros que estão em ../pages ou na mesma pasta
                $href = $page; 
                if($page == 'cadastrar_pessoa_fornecedor.php' || $page == 'banco_cadastro.php' || $page == 'categorias.php') $href = "../pages/" . $page;
                
                if(temPermissao($page, $perfil, $permissoes_usuario, $itens_disponiveis_plano)): 
                    $info = $itens_disponiveis_plano[$page] ?? ['titulo'=>'', 'icone'=>'']; ?>
                    <a class="card-link" href="<?= $href ?>">
                        <i class="fas <?= $info['icone'] ?>"></i> <?= $info['titulo'] ?>
                    </a>
            <?php endif; endforeach; ?>
        </div>
    <?php endif; ?>

    <?php 
    $grupo_sistema = ['lembrete.php', 'relatorios.php', 'selecionar_usuario.php', 'usuarios.php', 'configuracao_fiscal.php'];
    $show_sistema = false;
    foreach($grupo_sistema as $p) { if(temPermissao($p, $perfil, $permissoes_usuario, $itens_disponiveis_plano) || $p == 'selecionar_usuario.php') $show_sistema = true; }
    
    if ($show_sistema): ?>
        <div class="section-title"><i class="fas fa-cogs"></i> Sistema</div>
        <div class="dashboard">
            <?php if(temPermissao('lembretes.php', $perfil, $permissoes_usuario, $itens_disponiveis_plano)): ?>
                <a class="card-link" href="lembrete.php"><i class="fas fa-sticky-note"></i> Lembretes</a>
            <?php endif; ?>

            <?php if(temPermissao('relatorios.php', $perfil, $permissoes_usuario, $itens_disponiveis_plano)): ?>
                <a class="card-link" href="relatorios.php"><i class="fas fa-chart-pie"></i> Relatórios</a>
            <?php endif; ?>

            <a class="card-link" href="selecionar_usuario.php"><i class="fas fa-users-cog"></i> Trocar Usuário</a>

            <?php if(temPermissao('usuarios.php', $perfil, $permissoes_usuario, $itens_disponiveis_plano)): ?>
                <a class="card-link" href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <?php endif; ?>

            <?php if(temPermissao('configuracao_fiscal.php', $perfil, $permissoes_usuario, $itens_disponiveis_plano)): ?>
                <a class="card-link" href="configuracao_fiscal.php"><i class="fas fa-file-invoice"></i> Config. Fiscal</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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
const hora = new Date().getHours();
let texto = "Bem-vindo(a)!";
if (hora < 12) texto = "☀️ Bom dia!";
else if (hora < 18) texto = "🌤️ Boa tarde!";
else texto = "🌙 Boa noite!";
document.getElementById("saudacao").textContent = texto;
</script>

<?php include('../includes/footer.php'); ?>