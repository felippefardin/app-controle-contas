<?php
// Ficheiro: includes/session_init.php

// --- CONFIGURAÇÃO DE SEGURANÇA DA SESSÃO ---

// Força o uso de cookies mais seguros
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1); // Impede acesso via JavaScript
ini_set('session.cookie_samesite', 'Strict'); // Mitiga CSRF

// Garante que o cookie só seja enviado por HTTPS (essencial em produção)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// --- INÍCIO DA SESSÃO ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- GERAÇÃO DO TOKEN CSRF ---
// Gera um token se não existir um na sessão atual
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>