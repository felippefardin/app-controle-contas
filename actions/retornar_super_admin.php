<?php
require_once '../includes/session_init.php'; // Isso já chama session_start()

// 1. Verifica se há um backup da sessão do admin
if (isset($_SESSION['super_admin_original'])) {

    // 2. Salva o backup
    $admin_session = $_SESSION['super_admin_original'];

    // 3. Limpa a sessão atual (do tenant)
    session_unset();
    $_SESSION = []; 
    session_destroy(); // Destrói os dados antigos

    // 4. Restaura a sessão do super_admin
    // A session_init.php no topo já iniciou a sessão com os parâmetros corretos.
    // Basta reiniciar o array $_SESSION.
    $_SESSION['super_admin'] = $admin_session;
    
    // Regenera o token CSRF para a sessão de admin
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // 5. Redireciona para o dashboard
    session_write_close(); 
    header('Location: ../pages/admin/dashboard.php');
    exit;
}

// Se não houver backup, manda para o login
session_write_close(); 
header('Location: ../pages/login.php');
exit;
?>