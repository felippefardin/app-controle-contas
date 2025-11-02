<?php
require_once '../includes/session_init.php';
include('../database.php'); // Inclui a conexão master

// 1. Apenas o SUPER ADMIN pode acessar
if (!isset($_SESSION['super_admin'])) {
    session_write_close();
    header('Location: ../pages/login.php');
    exit;
}

if (isset($_GET['tenant_id'])) {
    $tenant_id = (int)$_GET['tenant_id'];

    // 2. Salva a sessão ATUAL do super_admin
    $_SESSION['super_admin_original'] = $_SESSION['super_admin'];

    // 3. Busca os dados de conexão do tenant no banco master
    $master_conn = getMasterConnection();
    
    // Busca as colunas de conexão corretas (conforme seu arquivo .sql)
    $sql = "SELECT db_host, db_database, db_user, db_password FROM tenants WHERE id = ?";
    $stmt = $master_conn->prepare($sql);

    if (!$stmt) {
        die("Erro ao preparar a query master: " . $master_conn->error);
    }

    $stmt->bind_param('i', $tenant_id);
    $stmt->execute();
    $tenant_result = $stmt->get_result();
    
    // Verifica se encontrou o tenant
    if ($tenant_db_info = $tenant_result->fetch_assoc()) {
        
        // 4. Tenta conectar no banco do tenant com as credenciais específicas
        try {
            $tenant_conn = new mysqli(
                $tenant_db_info['db_host'],
                $tenant_db_info['db_user'],
                $tenant_db_info['db_password'],
                $tenant_db_info['db_database']
            );
            $tenant_conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            // Se não conseguir conectar, restaura a sessão e volta
            $_SESSION['super_admin'] = $_SESSION['super_admin_original']; 
            unset($_SESSION['super_admin_original']);
            session_write_close();
            header('Location: ../pages/admin/dashboard.php?erro=db_tenant_invalido');
            exit;
        }
        
        // 5. Busca o usuário "proprietário" (id_criador IS NULL) dentro do banco do tenant
        $sql_user = "SELECT * FROM usuarios WHERE id_criador IS NULL LIMIT 1";
        $user_result = $tenant_conn->query($sql_user);

        if ($proprietario = $user_result->fetch_assoc()) {
            
            // 6. Limpa a sessão do admin (guardando o backup)
            $backup_admin = $_SESSION['super_admin_original'];
            session_unset(); // Limpa todas as variáveis de sessão
            
            // 7. Define as novas sessões
            $_SESSION['super_admin_original'] = $backup_admin; // Restaura o backup
            $_SESSION['tenant_id'] = $tenant_id;
            
            $_SESSION['tenant_db'] = [
                'db_host' => $tenant_db_info['db_host'],
                'db_user' => $tenant_db_info['db_user'],
                'db_password' => $tenant_db_info['db_password'],
                'db_database' => $tenant_db_info['db_database']
            ];
            
            // --- INÍCIO DA CORREÇÃO ---
            // Esta é a chave que faltava. As páginas internas (home.php, etc.) 
            // verificam 'usuario_logado' para autenticação.
            $_SESSION['usuario_logado'] = $proprietario; 
            // --- FIM DA CORREÇÃO ---

            $_SESSION['proprietario'] = $proprietario;
            $_SESSION['usuario_principal'] = $proprietario;
            $_SESSION['usuario_id'] = $proprietario['id']; 

            // 8. Redireciona para a home do tenant
            $tenant_conn->close();
            session_write_close(); 
            header('Location: ../pages/home.php');
            exit;
        } else {
             // Se não encontrar o usuário proprietário no banco do tenant
             $tenant_conn->close();
             $_SESSION['super_admin'] = $_SESSION['super_admin_original']; 
             unset($_SESSION['super_admin_original']);
             session_write_close();
             header('Location: ../pages/admin/dashboard.php?erro=proprietario_nao_encontrado');
             exit;
        }
    }
}

// Se algo der errado (ex: tenant_id não existe), volta para o dashboard
if(isset($_SESSION['super_admin_original'])) {
    $_SESSION['super_admin'] = $_SESSION['super_admin_original']; 
    unset($_SESSION['super_admin_original']);
}
session_write_close();
header('Location: ../pages/admin/dashboard.php');
exit;
?>