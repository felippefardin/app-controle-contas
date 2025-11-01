<?php
require_once '../includes/session_init.php';

// 1. Verifica se há um backup da sessão do admin
if (isset($_SESSION['super_admin_original'])) {

    // 2. Salva o backup
    $admin_session = $_SESSION['super_admin_original'];

    // 3. Limpa a sessão atual (do tenant)
    session_unset();
    $_SESSION = [];

    // 4. Restaura a sessão do super_admin
    $_SESSION['super_admin'] = $admin_session;

    // 5. Redireciona para o dashboard
    header('Location: ../pages/admin/dashboard.php');
    exit;
}

// Se não houver backup, manda para o login
header('Location: ../pages/login.php');
exit;
?>