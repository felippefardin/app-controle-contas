<?php
require_once '../includes/session_init.php';
include('../database.php');

// 1. Verifica se é Super Admin
if (!isset($_SESSION['super_admin'])) {
    session_write_close();
    header('Location: ../pages/login.php?erro=sessao_expirada');
    exit;
}

if (isset($_GET['tenant_id'])) {
    $tenant_id = (int)$_GET['tenant_id'];

    // 2. Backup da sessão Admin
    $backup_super_admin = $_SESSION['super_admin'];

    $master_conn = getMasterConnection();
    
    // Busca dados do tenant
    $sql = "SELECT db_host, db_database, db_user, db_password FROM tenants WHERE id = ?";
    $stmt = $master_conn->prepare($sql);
    $stmt->bind_param('i', $tenant_id);
    $stmt->execute();
    $tenant_info = $stmt->get_result()->fetch_assoc();
    
    if ($tenant_info) {
        // Testa conexão com o banco do tenant
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
        
        // Busca o proprietário do tenant para impersonar
        $sql_user = "SELECT * FROM usuarios WHERE nivel_acesso = 'proprietario' LIMIT 1";
        $user_result = $tenant_conn->query($sql_user);

        if ($proprietario = $user_result->fetch_assoc()) {
            
            // Limpa a sessão atual para evitar conflitos
            session_unset();
            
            // Restaura o backup do admin e define dados do tenant
            $_SESSION['super_admin_original'] = $backup_super_admin; // Flag que indica impersonação
            // Mantém a sessão super_admin ativa para verificações de segurança no dashboard se voltar
            $_SESSION['super_admin'] = $backup_super_admin; 
            
            $_SESSION['tenant_id'] = $tenant_id;
            $_SESSION['tenant_db'] = $tenant_info;
            
            // --- CORREÇÃO CRÍTICA AQUI ---
            // Define usuario_logado como TRUE (Booleano), pois home.php verifica "=== true"
            $_SESSION['usuario_logado'] = true; 
            
            // Define os dados do usuário impersonado
            $_SESSION['usuario_id']     = $proprietario['id'];
            $_SESSION['nome']           = $proprietario['nome'];
            $_SESSION['email']          = $proprietario['email'];
            $_SESSION['nivel_acesso']   = 'proprietario'; // Permite passar no verificar_acesso_admin
            
            $tenant_conn->close();
            
            // Salva sessão e redireciona
            session_write_close();
            
            // Vai direto para a home, pois o admin já "selecionou" a conta ao clicar em gerenciar
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