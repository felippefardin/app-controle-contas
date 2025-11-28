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
    'editar_conta_pagar.php',   // Necessário para editar
    'editar_conta_receber.php', // Necessário para editar
    'add_conta_pagar.php',      // Ações
    'add_conta_receber.php',
    'baixar_conta.php',
    // [CORREÇÃO LOOP] Adicionadas páginas de assinatura e pagamento para evitar bloqueio
    'assinar.php',
    'checkout_plano.php',
    'webhook_mercadopago.php'
];

if ($planoAtual === 'basico') {
    if (!in_array($paginaAtual, $paginasBasico)) {
        // Se tentar acessar relatórios, vendas, estoque, etc...
        $_SESSION['msg_erro'] = "Seu plano Básico não permite acesso a esta funcionalidade.";
        header("Location: home.php");
        exit;
    }
}
?>