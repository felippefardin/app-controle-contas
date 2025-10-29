<?php
// // Ficheiro: includes/session_init.php

// --- INÍCIO DA SESSÃO ---
// Garante que uma sessão seja iniciada se ainda não houver uma ativa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- GERAÇÃO DO TOKEN CSRF ---
// Gera um token para proteção contra ataques CSRF, se não existir.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>