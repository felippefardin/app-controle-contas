<?php
require_once '../includes/session_init.php';
include('../database.php');

// DEBUG TEMPORÁRIO (Se continuar falhando, remova os comentários abaixo para testar)
// if (!isset($_SESSION['super_admin'])) {
//     die("Sessão perdida! ID da Sessão: " . session_id() . " <br> Conteúdo: " . print_r($_SESSION, true));
// }

// 1. Verifica se é Admin
if (!isset($_SESSION['super_admin'])) {
    session_write_close();
    header('Location: ../pages/login.php?erro=sessao_expirada');
    exit;
}

if (isset($_GET['tenant_id'])) {
    $tenant_id = (int)$_GET['tenant_id'];

    // 2. Backup da sessão
    $_SESSION['super_admin_original'] = $_SESSION['super_admin'];

    $master_conn = getMasterConnection();
    
    // Busca dados do tenant
    $sql = "SELECT db_host, db_database, db_user, db_password FROM tenants WHERE id = ?";
    $stmt = $master_conn->prepare($sql);
    $stmt->bind_param('i', $tenant_id);
    $stmt->execute();
    $tenant_info = $stmt->get_result()->fetch_assoc();
    
    if ($tenant_info) {
        try {
            $tenant_conn = new mysqli(
                $tenant_info['db_host'],
                $tenant_info['db_user'],
                $tenant_info['db_password'],
                $tenant_info['db_database']
            );
            $tenant_conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            header('Location: ../pages/admin/dashboard.php?erro=conexao_tenant');
            exit;
        }
        
        // Busca o proprietário
        $sql_user = "SELECT * FROM usuarios WHERE nivel_acesso = 'proprietario' LIMIT 1";
        $user_result = $tenant_conn->query($sql_user);

        if ($proprietario = $user_result->fetch_assoc()) {
            $backup = $_SESSION['super_admin_original'];
            
            // Limpa sessão antiga e define a nova
            session_unset();
            
            $_SESSION['super_admin_original'] = $backup;
            $_SESSION['tenant_id'] = $tenant_id;
            $_SESSION['tenant_db'] = $tenant_info;
            
            $_SESSION['usuario_logado'] = $proprietario; 
            $_SESSION['usuario_id']     = $proprietario['id'];
            $_SESSION['nome']           = $proprietario['nome'];
            $_SESSION['email']          = $proprietario['email'];
            $_SESSION['nivel_acesso']   = 'proprietario';
            
            $tenant_conn->close();
            
            // Salva explicitamente antes de redirecionar
            session_write_close();
            
            header('Location: ../pages/home.php');
            exit;
        } else {
            header('Location: ../pages/admin/dashboard.php?erro=sem_proprietario');
            exit;
        }
    }
}

header('Location: ../pages/admin/dashboard.php?erro=tenant_nao_encontrado');
exit;
?>