<?php
require_once '../includes/session_init.php';

// Verifica se há um usuário principal E um usuário ativo na sessão
if (!isset($_SESSION['usuario_principal']) || !isset($_SESSION['usuario'])) {
    // Se faltar algum, destrói a sessão e volta para o login para recomeçar
    session_destroy();
    header('Location: login.php');
    exit;
}

include('../includes/header_home.php');

$usuario_ativo = $_SESSION['usuario'];
$nome = $usuario_ativo['nome'];
$perfil = $usuario_ativo['perfil'];

$mensagem = $_SESSION['mensagem'] ?? null;
unset($_SESSION['mensagem']);
// --- INÍCIO DO CÓDIGO DE ALERTA DE ESTOQUE ---
include('../database.php'); // Garanta que a conexão está inclusa
$id_usuario_ativo = $_SESSION['usuario']['id'];
$stmt_estoque = $conn->prepare("SELECT nome FROM produtos WHERE id_usuario = ? AND quantidade <= quantidade_minima AND quantidade_minima > 0");
$stmt_estoque->bind_param("i", $id_usuario_ativo);
$stmt_estoque->execute();
$result_estoque = $stmt_estoque->get_result();
$produtos_estoque_baixo = [];
while ($produto = $result_estoque->fetch_assoc()) {
    $produtos_estoque_baixo[] = $produto['nome'];
}
// --- FIM DO CÓDIGO DE ALERTA DE ESTOQUE ---
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - App Controle Contas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    /* Reset básico */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background-color: #121212; color: #eee; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
    h1 { color: #00bfff; margin-bottom: 10px; text-align: center; }
    h3 { margin-bottom: 5px; font-weight: 400; text-align: center; color: #ddd; }
    h4 { margin-bottom: 20px; font-weight: 400; text-align: center; color: #999; }
    .alert-estoque {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #dc3545;
        color: white;
        padding: 15px;
        border-radius: 8px;
        z-index: 1000;
        text-align: left;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
    }
    .mensagem-sucesso { background-color: #28a745; color: white; padding: 15px 20px; margin-bottom: 20px; border-radius: 8px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 0 10px rgba(40, 167, 69, 0.5); font-weight: 600; font-size: 1rem; }
    nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-bottom: 30px; width: 100%; max-width: 800px; }
    nav a { background-color: #00bfff; color: #121212; text-decoration: none; padding: 12px 22px; border-radius: 8px; font-weight: 600; box-shadow: 0 3px 6px rgba(0,191,255,0.4); transition: background-color 0.3s ease, color 0.3s ease; flex: 1 1 130px; text-align: center; user-select: none; }
    nav a:hover, nav a:focus { background-color: #0095cc; color: #fff; outline: none; box-shadow: 0 0 12px #0095cc; }
    p { font-size: 1.1rem; color: #ccc; text-align: center; max-width: 600px; width: 100%; }
    @media (max-width: 480px) { nav { flex-direction: column; gap: 12px; } nav a { flex: 1 1 100%; padding: 14px 0; font-size: 1.1rem; } body { padding: 15px; } }
  </style>
</head>
<body>
  <h1>App Controle de Contas</h1>
  <h3>Usuário Ativo: <?= htmlspecialchars($nome) ?> (<?= htmlspecialchars($perfil) ?>)</h3>
  <h4>(Conta Principal: <?= htmlspecialchars($_SESSION['usuario_principal']['nome']) ?>)</h4>

  <?php if ($mensagem): ?>
    <div class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

    <?php if (!empty($produtos_estoque_baixo)): ?>
        <div id="alert-estoque" class="alert-estoque">
            <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong> Os seguintes produtos estão com estoque baixo ou zerado:
            <ul>
                <?php foreach ($produtos_estoque_baixo as $nome_produto): ?>
                    <li><?= htmlspecialchars($nome_produto) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

  <nav>
    <a href="contas_pagar.php">Contas a Pagar</a>
    <a href="contas_pagar_baixadas.php">Contas a pagar baixadas</a>
    <a href="contas_receber.php">Contas a Receber</a>
    <a href="contas_receber_baixadas.php">Contas a receber baixadas</a>
    <a href="usuarios.php">Usuários</a>
    <a href="perfil.php">Perfil</a>
    <a href="selecionar_usuario.php">Trocar Usuário</a>
    <a style="background-color: red;" href="logout.php">Sair</a>    
  </nav>
   <nav>  
        <a href="../pages/cadastrar_pessoa_fornecedor.php">Clientes/Fornecedores</a>
        <a href="../pages/banco_cadastro.php">Contas Bancárias</a>         
        <a href="../pages/categorias.php">Categorias</a> 
        <a href="relatorios.php">Relatórios</a>
        <a href="lancamento_caixa.php">Fluxo de Caixa Diário</a> 
        <a href="controle_estoque.php">Controle de Estoque</a>
        <a href="vendas.php">Caixa de Vendas</a>
        <!-- <a href="compras.php">Registrar Compra</a> -->

    </nav>

  <p>Bem-vindo ao sistema!</p>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var alertEstoque = document.getElementById('alert-estoque');
        if (alertEstoque) {
            setTimeout(function() {
                alertEstoque.style.display = 'none';
            }, 6000); // 6 segundos
        }
    });
</script>
</body>
</html>