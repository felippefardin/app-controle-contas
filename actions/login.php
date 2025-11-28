<?php
// actions/login.php
require_once '../includes/session_init.php';
require_once __DIR__ . '/../database.php';
require_once '../includes/utils.php'; // Importa o sistema de Flash Messages

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

// --- NOVA FUNÇÃO: Redireciona salvando o input antigo ---
function redirect_with_error($msg, $email_input) {
    set_flash_message('danger', $msg);
    $_SESSION['old_email'] = $email_input; // Salva o e-mail digitado
    header("Location: ../pages/login.php");
    exit;
}
// --------------------------------------------------------

if (!$email || !$senha) {
    set_flash_message('warning', 'Preencha o e-mail e a senha para entrar.');
    header("Location: ../pages/login.php");
    exit;
}

try {
    $connMaster = getMasterConnection();

    $stmt = $connMaster->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userMaster = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$userMaster) {
        $connMaster->close();
        // Usa a nova função para preservar o e-mail
        redirect_with_error('Conta não encontrada. Verifique o e-mail.', $email);
    }

    if (!password_verify($senha, $userMaster['senha'])) {
        $connMaster->close();
        // Usa a nova função para preservar o e-mail
        redirect_with_error('Senha incorreta. Tente novamente.', $email);
    }

    // Super Admin
    $emails_admin = ['contatotech.tecnologia@gmail.com', 'contatotech.tecnologia@gmail.com.br'];
    if (in_array($userMaster['email'], $emails_admin)) {
        $_SESSION['super_admin'] = $userMaster;
        // Limpa old_email se existir, pois deu sucesso
        unset($_SESSION['old_email']); 
        $connMaster->close();
        header('Location: ../pages/admin/dashboard.php');
        exit;
    }

    $tenantId = $userMaster['tenant_id'] ?? null;
    $tenant = null;

    if ($tenantId) {
        $stmt = $connMaster->prepare("SELECT * FROM tenants WHERE tenant_id = ? LIMIT 1");
        $stmt->bind_param("s", $tenantId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($tenant) {
            $_SESSION['plano'] = $tenant['plano_atual'] ?? 'basico';
        }
    }

    // VALIDAÇÃO DE STATUS E TRIAL
    if ($tenant) {
        $status = $tenant['status_assinatura'] ?? 'padrao';
        $is_trial = ($status === 'trial');
        $expired = false;

        if ($is_trial) {
            $dias_teste = ($tenant['plano_atual'] === 'essencial') ? 30 : 15;
            $data_inicio = new DateTime($tenant['data_inicio_teste'] ?? $tenant['data_criacao']);
            $data_fim = clone $data_inicio;
            $data_fim->modify("+$dias_teste days");
            
            if (new DateTime() > $data_fim) {
                $expired = true;
                $connMaster->query("UPDATE tenants SET status_assinatura = 'trial_expired' WHERE id = " . $tenant['id']);
            }
        }

        $bloqueados = ['vencido', 'cancelado', 'trial_expired', 'pendente'];

        if ($expired || in_array($status, $bloqueados)) {
            $_SESSION['usuario_id']     = $userMaster['id'];
            $_SESSION['tenant_id']      = $tenantId;
            $_SESSION['email']          = $userMaster['email'];
            $_SESSION['usuario_logado'] = true;
            $_SESSION['nivel_acesso']   = 'proprietario';
            $_SESSION['erro_assinatura'] = "Seu período gratuito acabou ou sua assinatura está pendente.";
            
            // Limpa old_email
            unset($_SESSION['old_email']);
            
            $connMaster->close();
            header("Location: ../pages/assinar.php");
            exit;
        }
    }

    $connMaster->close();

    // Configura Sessão do Tenant
    $idUsuarioTenant = null;
    $nivelAcessoTenant = 'padrao';

    if ($tenant) {
        $_SESSION['tenant_db'] = [
            "db_host"     => $tenant['db_host'],
            "db_user"     => $tenant['db_user'],
            "db_password" => $tenant['db_password'],
            "db_database" => $tenant['db_database']
        ];

        $tenantConn = getTenantConnection();
        if ($tenantConn) {
            $stmtTenant = $tenantConn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
            $stmtTenant->bind_param("s", $email);
            $stmtTenant->execute();
            $userTenant = $stmtTenant->get_result()->fetch_assoc();
            $stmtTenant->close();

            if ($userTenant) {
                $idUsuarioTenant = $userTenant['id'];
                $nivelAcessoTenant = $userTenant['nivel_acesso'];
            }
            $tenantConn->close();
        }
    }

    // Sucesso!
    $_SESSION['usuario_id']        = $idUsuarioTenant ?? $userMaster['id'];
    $_SESSION['usuario_id_master'] = $userMaster['id'];
    $_SESSION['nome']              = $userMaster['nome'];
    $_SESSION['email']             = $userMaster['email'];
    $_SESSION['tenant_id']         = $tenantId;
    $_SESSION['nivel_acesso']      = $nivelAcessoTenant;
    $_SESSION['usuario_logado']    = true;

    // Limpa old_email pois o login foi bem sucedido
    unset($_SESSION['old_email']);

    // Define mensagem de boas-vindas para a Home
    set_flash_message('success', "Bem-vindo de volta, {$userMaster['nome']}!");

    // REDIRECIONAMENTO DIRETO PARA HOME (Mudança solicitada)
    header("Location: ../pages/home.php");
    exit;

} catch (Exception $e) {
    set_flash_message('danger', "Erro interno no servidor. Tente novamente mais tarde.");
    header("Location: ../pages/login.php");
    exit;
}
?>