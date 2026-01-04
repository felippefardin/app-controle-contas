<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$planoAtual = $_SESSION['plano'] ?? 'basico';
$paginaAtual = basename($_SERVER['PHP_SELF']);

// Páginas permitidas para o plano Básico (E essencialmente a 'whitelist' para evitar loops)
$paginasBasico = [
    'contas_pagar.php',
    'contas_receber.php',
    'calculadora.php',
    'calendario.php',
    'home.php',
    'perfil.php',
    'suporte.php',
    'logout.php',
    'login.php',
    'index.php',
    'editar_conta_pagar.php',
    'editar_conta_receber.php',
    'add_conta_pagar.php',
    'add_conta_receber.php',
    'baixar_conta.php',
    'assinar.php',
    'checkout_plano.php',
    'webhook_mercadopago.php'
    // NÃO ADICIONE vendas.php aqui!
];

if ($planoAtual === 'basico') {
    // Note que aqui NÃO verificamos se é admin ou proprietário.
    // Assim, o bloqueio vale para TODOS.
    if (!in_array($paginaAtual, $paginasBasico)) {
        $_SESSION['msg_erro'] = "Seu plano Básico não permite acesso a esta funcionalidade (PDV/Vendas).";
        header("Location: home.php");
        exit;
    }
}
?>