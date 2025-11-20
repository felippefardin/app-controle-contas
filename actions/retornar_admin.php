<?php
require_once '../includes/session_init.php';
include('../database.php');

// Configuração do Super Admin
$super_admin_email = 'contatotech.tecnologia@gmail.com.br';

// Verifica se há uma sessão de super admin original guardada
if (isset($_SESSION['super_admin_original'])) {
    $admin_data = $_SESSION['super_admin_original'];

    // Verifica segurança extra: o e-mail guardado é o do super admin?
    if ($admin_data['email'] === $super_admin_email) {
        
        // Limpa a sessão atual (do tenant/usuário impersonado)
        session_unset();
        
        // Restaura a sessão do super admin
        $_SESSION['super_admin'] = $admin_data;
        
        session_write_close();

        // Redireciona de volta para o dashboard
        header('Location: ../pages/admin/dashboard.php');
        exit;
    }
}

// Se algo der errado ou não for o super admin, manda pro login ou home
session_unset();
session_destroy();
header('Location: ../pages/login.php');
exit;
?>