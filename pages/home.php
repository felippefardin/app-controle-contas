<?php
// ----------------------------------------------
// home.php (versão final padronizada para SaaS)
// ----------------------------------------------
require_once '../includes/session_init.php';
require_once '../database.php';

// 🔒 Usuário precisa estar logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.php?erro=nao_logado");
    exit();
}

// 🔒 Se for o Master Admin, redireciona para dashboard dele
// ❗️❗️ INÍCIO DA CORREÇÃO ❗️❗️
// Só redireciona se for master admin E NÃO estiver a personificar um utilizador
if (
    isset($_SESSION['is_master_admin']) &&
    $_SESSION['is_master_admin'] === true &&
    !isset($_SESSION['proprietario_id_original']) && // <- Verifica se NÃO está a personificar (definido em trocar_usuario.php)
    !isset($_SESSION['super_admin_original']) // <- Verifica se NÃO está a personificar (definido em admin_impersonate.php)
) {
    header("Location: ../pages/admin/dashboard.php");
    exit();
}
// ❗️❗️ FIM DA CORREÇÃO ❗️❗️

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
$perfil        = $_SESSION['nivel_acesso']; // admin | padrao | proprietario

// 📌 Conexão do tenant
$conn = getTenantConnection();
if (!$conn) {
    session_destroy();
    header("Location: ../pages/login.php?erro=db_tenant");
    exit();
}

// 🔒 Revalidação do tenant
$connMaster = getMasterConnection();
if (!$connMaster) {
    session_destroy();
    header("Location: ../pages/login.php?erro=db_master");
    exit();
}

$tenant = getTenantById($tenant_id, $connMaster); // Usar a conexão MASTER
$connMaster->close(); // Fechar a conexão MASTER

if (!$tenant) {
    session_destroy();
    header("Location: ../pages/login.php?erro=tenant_invalido");
    exit();
}

// 🔒 Atualiza status da assinatura (função do database.php)
$_SESSION['subscription_status'] = validarStatusAssinatura($tenant);

// ⚡ Mensagem de sucesso (se houver)
$mensagem = $_SESSION['sucesso_mensagem'] ?? null;
unset($_SESSION['sucesso_mensagem']);

// ⚠ Estoque baixo (se houver)
$produtos_estoque_baixo = $_SESSION['produtos_estoque_baixo'] ?? [];
unset($_SESSION['produtos_estoque_baixo']);

// Cabeçalho
include('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home - App Controle de Contas</title>

<style>
    body {
        background: radial-gradient(circle at top, #1a1a1a 0%, #0f0f0f 100%);
        animation: fadeIn 0.8s ease;
        color: #fff;
    }
    .home-container {
        max-width: 1000px;
        margin: auto;
        padding: 20px;
    }
    h1 {
        text-align: center;
        color: #00bfff;
        margin-bottom: 5px;
    }
    h3 {
        text-align: center;
        color: #ccc;
        font-weight: 400;
        margin-bottom: 20px;
    }
    .saudacao {
        text-align: center;
        margin-bottom: 25px;
        color: #ddd;
        font-size: 1.1rem;
    }
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
    }
    .card-link:hover {
        background: #00bfff;
        color: #121212;
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,191,255,0.4);
    }
    .mensagem-sucesso {
        background: #28a745;
        padding: 12px 20px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: bold;
    }
    .alert-estoque {
        background: #dc3545;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        color: #fff;
    }
</style>
</head>

<body>
<div class="home-container">
    <h1>App Controle de Contas</h1>
    <h3>Olá, <?= htmlspecialchars($nome_usuario) ?> — Perfil: <?= htmlspecialchars($perfil) ?></h3>
    <p class="saudacao" id="saudacao"></p>

    <?php if ($mensagem): ?>
        <div class="mensagem-sucesso">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($produtos_estoque_baixo)): ?>
        <div class="alert-estoque">
            <strong>⚠ Produtos com estoque baixo:</strong>
            <ul>
                <?php foreach ($produtos_estoque_baixo as $p): ?>
                    <li>
                        <?= htmlspecialchars($p['nome']) ?> — Estoque: <?= intval($p['quantidade_estoque']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
        <div class="section-title">
            <i class="fas fa-wallet"></i> Financeiro
        </div>

        <div class="dashboard">
            <a class="card-link" href="contas_pagar.php">Contas a Pagar</a>
            <a class="card-link" href="contas_pagar_baixadas.php">Pagas</a>
            <a class="card-link" href="contas_receber.php">Contas a Receber</a>
            <a class="card-link" href="contas_receber_baixadas.php">Recebidas</a>
            <a class="card-link" href="lancamento_caixa.php">Fluxo de Caixa</a>
        </div>
    <?php endif; ?>

    <div class="section-title">
        <i class="fas fa-boxes"></i> Estoque & Vendas
    </div>

    <div class="dashboard">
        <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
            <a class="card-link" href="controle_estoque.php">Estoque</a>
        <?php endif; ?>

        <a class="card-link" href="vendas.php">Caixa de Vendas</a>

        <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
            <a class="card-link" href="compras.php">Compras</a>
        <?php endif; ?>
    </div>

    <div class="section-title">
        <i class="fas fa-users"></i> Cadastros
    </div>

    <div class="dashboard">
        <a class="card-link" href="../pages/cadastrar_pessoa_fornecedor.php">
            Clientes/Fornecedores
        </a>

        <a class="card-link" href="perfil.php">Perfil</a>

        <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
            <a class="card-link" href="../pages/banco_cadastro.php">Contas Bancárias</a>
            <a class="card-link" href="../pages/categorias.php">Categorias</a>
        <?php endif; ?>
    </div>

    <div class="section-title">
        <i class="fas fa-cogs"></i> Sistema
    </div>

    <div class="dashboard">
        <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
            <a class="card-link" href="relatorios.php">Relatórios</a>
        <?php endif; ?>

        <a class="card-link" href="selecionar_usuario.php">Trocar Usuário</a>

        <?php if ($perfil === 'admin' || $perfil === 'proprietario'): ?>
            <a class="card-link" href="usuarios.php">Usuários</a>
            <a class="card-link" href="configuracao_fiscal.php">Configurações Fiscais</a>
        <?php endif; ?>
    </div>
</div>

<script>
// Saudação dinâmica
const hora = new Date().getHours();
let texto = "Bem-vindo(a)!";
if (hora < 12) texto = "☀️ Bom dia!";
else if (hora < 18) texto = "🌤️ Boa tarde!";
else texto = "🌙 Boa noite!";
document.getElementById("saudacao").textContent = texto;
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
