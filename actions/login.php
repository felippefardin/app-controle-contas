<?php
require_once '../includes/session_init.php';
include('../database.php');

$email = trim(strtolower($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    $_SESSION['erro_login'] = "Por favor, preencha o e-mail e a senha.";
    header('Location: ../pages/login.php');
    exit;
}

// === LÓGICA DE LOGIN ATUALIZADA ===

// 1. Primeiro, tenta logar como SUPER ADMIN no banco de dados MASTER
$master_conn = getMasterConnection();
$stmt_super_admin = $master_conn->prepare("SELECT * FROM usuarios WHERE email = ? AND tenant_id IS NULL");
$stmt_super_admin->bind_param('s', $email);
$stmt_super_admin->execute();
$result_super_admin = $stmt_super_admin->get_result();

if ($user = $result_super_admin->fetch_assoc()) {
    if (password_verify($senha, $user['senha'])) {
        $_SESSION['super_admin'] = $user;
        header('Location: ../pages/admin/dashboard.php'); // Redireciona para o dashboard de admin
        exit;
    }
}
$stmt_super_admin->close();

// 2. Se não for o super admin, procura o e-mail do cliente na tabela de TENANTS
$stmt_tenant = $master_conn->prepare("SELECT * FROM tenants WHERE admin_email = ?");
$stmt_tenant->bind_param('s', $email);
$stmt_tenant->execute();
$result_tenant = $stmt_tenant->get_result();

if ($tenant_info = $result_tenant->fetch_assoc()) {
    // Cliente encontrado! Agora vamos conectar no banco de dados DELE.
    try {
        $tenant_conn = new mysqli(
            $tenant_info['db_host'],
            $tenant_info['db_user'],
            $tenant_info['db_password'],
            $tenant_info['db_database']
        );
        $tenant_conn->set_charset("utf8mb4");

        // 3. AGORA, sim, procuramos o usuário e validamos a senha no banco do cliente
        $stmt_user = $tenant_conn->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt_user->bind_param('s', $email);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();

        if ($user_data = $result_user->fetch_assoc()) {
            if (password_verify($senha, $user_data['senha'])) {
                // SUCESSO! Usuário autenticado.
                
                // Armazena as informações de conexão do tenant na sessão
                $_SESSION['tenant_db'] = [
                    'db_host'     => $tenant_info['db_host'],
                    'db_database' => $tenant_info['db_database'],
                    'db_user'     => $tenant_info['db_user'],
                    'db_password' => $tenant_info['db_password'],
                ];

                // Armazena os dados do usuário do tenant na sessão
                $_SESSION['usuario_logado'] = $user_data;
                // Redireciona para a página principal da aplicação
                header('Location: ../pages/home.php'); 
                exit;
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Se a conexão com o banco do cliente falhar
        $_SESSION['erro_login'] = "Erro ao acessar a conta do cliente.";
        header('Location: ../pages/login.php');
        exit;
    }
}

// Se chegou até aqui, nem o super admin nem o cliente foram encontrados.
$_SESSION['erro_login'] = "Credenciais inválidas, usuário não autorizado ou bloqueado.";
header('Location: ../pages/login.php');
exit;
?>