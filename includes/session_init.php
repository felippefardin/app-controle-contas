<?php
// includes/session_init.php
// NÃO PODE HAVER NENHUMA LINHA OU ESPAÇO ANTES DE <?php

// --- Inicia sessão global para todo o domínio ---
if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,     // Sessão dura até fechar o navegador
        'path' => '/',       // Importante: sessão visível em todas as pastas (/actions, /pages, /includes)
        'domain' => '',      // Domínio atual
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true
    ]);

    session_start();
}

// ------------------------------
// 🔐 VERIFICAÇÃO DE ACESSO
// ------------------------------
// AVISO: Não use mais $_SESSION['usuario_logado'] como array!
// Agora usamos:
//   $_SESSION['usuario_logado'] = true/false
//   $_SESSION['nivel_acesso']   = 'admin' | 'padrao'
//   $_SESSION['usuario_id']     = id do usuário dentro do tenant
// ------------------------------

/**
 * Verifica se o usuário é ADMIN dentro do tenant
 */
function verificar_acesso_admin() {
    if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
        header("Location: ../pages/login.php?erro=nao_logado");
        exit;
    }

    if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'admin') {
        header("Location: ../pages/home.php?erro=sem_permissao");
        exit;
    }
}

// ------------------------------
// 🔰 CSRF PROTECTION
// ------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
?>
