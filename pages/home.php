<?php
// 1. INICIALIZAÇÃO DA SESSÃO (DEVE SER A PRIMEIRA COISA NO ARQUIVO)
require_once '../includes/session_init.php';

// 2. VERIFICAÇÃO DE LOGIN
// Se faltar o usuário principal ou o ativo, destrói a sessão e volta para o login.
if (!isset($_SESSION['usuario_principal']) || !isset($_SESSION['usuario'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 3. INCLUDES E DEFINIÇÃO DE VARIÁVEIS
// Apenas inclua outros arquivos APÓS a verificação de sessão.
include('../database.php');

$usuario_ativo = $_SESSION['usuario'];
$nome = $usuario_ativo['nome'];
$perfil = $usuario_ativo['perfil'];

$mensagem = $_SESSION['mensagem'] ?? null;
unset($_SESSION['mensagem']);

// --- CÓDIGO DE ALERTA DE ESTOQUE ---
$id_usuario_ativo = $_SESSION['usuario']['id'];
$stmt_estoque = $conn->prepare("SELECT nome, quantidade_estoque FROM produtos WHERE id_usuario = ? AND quantidade_estoque <= quantidade_minima AND quantidade_minima > 0");
$stmt_estoque->bind_param("i", $id_usuario_ativo);
$stmt_estoque->execute();
$result_estoque = $stmt_estoque->get_result();
$produtos_estoque_baixo = [];
while ($produto = $result_estoque->fetch_assoc()) {
    $produtos_estoque_baixo[] = $produto; // Salva o array completo do produto
}
$stmt_estoque->close();

// INCLUI O HEADER PADRÃO (APÓS TODA A LÓGICA PHP)
include('../includes/header.php');

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home - App Controle Contas</title>
<style>
    /* Estilos gerais da página */
    body {
        background: radial-gradient(circle at top, #1a1a1a 0%, #0f0f0f 100%);
        animation: fadeIn 0.8s ease;
    }

    .home-container {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    h1 {
        color: #00bfff;
        text-align: center;
        font-size: 2rem;
        margin-bottom: 5px;
    }

    h3, h4 {
        text-align: center;
        color: #bbb;
        font-weight: 400;
        margin-bottom: 5px;
    }

    h4 { margin-bottom: 20px; color: #999; }

    .saudacao {
        font-size: 1.1rem;
        margin-bottom: 25px;
        color: #ccc;
        text-align: center;
    }

    /* ==== Dashboard de atalhos ==== */
    .dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 18px;
        width: 100%;
        margin-bottom: 30px;
    }

    .card-link {
        background-color: #1e1e1e;
        color: #fff;
        text-decoration: none;
        padding: 25px 15px;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 3px 6px rgba(0,0,0,0.4);
    }

    .card-link:hover {
        background-color: #00bfff;
        color: #121212;
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,191,255,0.5);
    }

    .card-link i {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .section-title {
        width: 100%;
        color: #00bfff;
        font-weight: bold;
        margin: 10px 0;
        border-bottom: 1px solid #333;
        padding-bottom: 5px;
        font-size: 1.1rem;
    }

    .mensagem-sucesso {
        background-color: #28a745;
        color: white;
        padding: 12px 20px;
        margin: 15px 0;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        font-weight: 600;
        text-align: center;
        animation: fadeIn 0.6s ease;
    }

    .alert-estoque {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #dc3545;
        color: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        z-index: 1000;
        text-align: left;
        width: 90%;
        max-width: 500px;
        animation: fadeInUp 0.8s ease;
    }

    .alert-estoque ul {
        margin-top: 10px;
        padding-left: 20px;
    }
    
    .btn-logout { background-color: #ff4b4b !important; color: #fff; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translate(-50%, 30px); }
        to { opacity: 1; transform: translate(-50%, 0); }
    }

    @media (max-width: 600px) {
        h1 { font-size: 1.6rem; }
        .card-link { padding: 18px 10px; }
        .card-link i { font-size: 1.5rem; }
    }
</style>
</head>
<body>

<div class="home-container">
    <h1>App Controle de Contas</h1>
    <h3>Usuário Ativo: <?= htmlspecialchars($nome) ?> (<?= htmlspecialchars($perfil) ?>)</h3>
    <h4>Conta Principal: <?= htmlspecialchars($_SESSION['usuario_principal']['nome']) ?></h4>
    <p class="saudacao" id="saudacao"></p>

    <?php if ($mensagem): ?>
        <div class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if (!empty($produtos_estoque_baixo)): ?>
        <div id="alert-estoque" class="alert-estoque">
            <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong>
            Estoque baixo ou zerado para:
            <ul>
                <?php foreach ($produtos_estoque_baixo as $produto): ?>
                    <li><?= htmlspecialchars($produto['nome']) ?> (Estoque: <?= $produto['quantidade_estoque'] ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="section-title"><i class="fas fa-wallet"></i> Financeiro</div>
    <div class="dashboard">
        <a class="card-link" href="contas_pagar.php"><i class="fas fa-arrow-down"></i>Contas a Pagar</a>
        <a class="card-link" href="contas_pagar_baixadas.php"><i class="fas fa-check-circle"></i>Pagas</a>
        <a class="card-link" href="contas_receber.php"><i class="fas fa-arrow-up"></i>Contas a Receber</a>
        <a class="card-link" href="contas_receber_baixadas.php"><i class="fas fa-hand-holding-usd"></i>Recebidas</a>
        <a class="card-link" href="lancamento_caixa.php"><i class="fas fa-cash-register"></i>Fluxo de Caixa</a>
    </div>

    <div class="section-title"><i class="fas fa-boxes"></i> Estoque & Vendas</div>
    <div class="dashboard">
        <a class="card-link" href="controle_estoque.php"><i class="fas fa-box"></i>Estoque</a>
        <a class="card-link" href="vendas.php"><i class="fas fa-shopping-cart"></i>Caixa de Vendas</a>
        <a class="card-link" href="compras.php"><i class="fas fa-dolly"></i>Registro de Compras</a>
    </div>

    <div class="section-title"><i class="fas fa-users"></i> Cadastros</div>
    <div class="dashboard">
        <a class="card-link" href="../pages/cadastrar_pessoa_fornecedor.php"><i class="fas fa-user"></i>Clientes/Fornecedores</a>
        <a class="card-link" href="../pages/banco_cadastro.php"><i class="fas fa-university"></i>Contas Bancárias</a>
        <a class="card-link" href="../pages/categorias.php"><i class="fas fa-list"></i>Categorias</a>
    </div>

    <div class="section-title"><i class="fas fa-cogs"></i> Sistema</div>
    <div class="dashboard">
        <a class="card-link" href="relatorios.php"><i class="fas fa-file-alt"></i>Relatórios</a>
        <a class="card-link" href="perfil.php"><i class="fas fa-user-circle"></i>Perfil</a>
        <a class="card-link" href="selecionar_usuario.php"><i class="fas fa-user-switch"></i>Trocar Usuário</a>
        <a class="card-link" href="usuarios.php"><i class="fas fa-user-gear"></i>Usuários</a>
        <a class="card-link" href="configuracao_fiscal.php"><i class="fas fa-file-invoice"></i>Config. Fiscal</a>
    </div>
</div>

<script>
    // Saudação dinâmica
    const saudacao = document.getElementById('saudacao');
    const hora = new Date().getHours();
    let texto = "Bem-vindo(a) de volta!";
    if (hora < 12) texto = "☀️ Bom dia!";
    else if (hora < 18) texto = "🌤️ Boa tarde!";
    else texto = "🌙 Boa noite!";
    saudacao.textContent = texto;

    // Oculta alerta de estoque após 8s
    document.addEventListener("DOMContentLoaded", function() {
        const alertEstoque = document.getElementById('alert-estoque');
        if (alertEstoque) {
            setTimeout(() => {
                alertEstoque.style.transition = 'opacity 0.5s';
                alertEstoque.style.opacity = '0';
                setTimeout(() => alertEstoque.remove(), 500);
            }, 3000); // 8 segundos
        }
    });
</script>

<?php include('../includes/footer.php'); ?>