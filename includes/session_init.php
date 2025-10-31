<?php
// // Ficheiro: includes/session_init.php

// --- INÍCIO DA SESSÃO ---
// Garante que uma sessão seja iniciada se ainda não houver uma ativa.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para verificar se o usuário é proprietário
function verificar_acesso_proprietario() {
    if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado']['nivel_acesso'] !== 'proprietario') {
        header('Location: ../pages/vendas.php');
        exit;
    }
}


// --- GERAÇÃO DO TOKEN CSRF ---
// Gera um token para proteção contra ataques CSRF, se não existir.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>