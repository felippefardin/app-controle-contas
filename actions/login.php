<?php
require_once '../includes/session_init.php';
require_once __DIR__ . '/../database.php';

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if (!$email || !$senha) {
    $_SESSION['login_erro'] = "Preencha todos os campos.";
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

    // 1. Verifica se o usuário existe
    if (!$userMaster) {
        $_SESSION['login_erro'] = "Conta não encontrada"; // Mensagem solicitada
        $connMaster->close();
        header("Location: ../pages/login.php");
        exit;
    }

    // 2. Verifica a senha
    if (!password_verify($senha, $userMaster['senha'])) {
        $_SESSION['login_erro'] = "E-mail ou senha inválidos"; // Mensagem solicitada
        $connMaster->close();
        header("Location: ../pages/login.php");
        exit;
    }

    // 3. Lógica de Tenant e Assinatura (Mantida a original)
    $tenantId = $userMaster['tenant_id'] ?? null;
    $tenant = null;
    
    if ($tenantId) {
        $stmt = $connMaster->prepare("SELECT * FROM tenants WHERE tenant_id = ? LIMIT 1");
        $stmt->bind_param("s", $tenantId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // Validação de Assinatura
    if ($tenant && function_exists('validarStatusAssinatura')) {
        $statusAssinatura = validarStatusAssinatura($tenant);
        $statusBloqueados = ['vencido', 'cancelado', 'trial_expired', 'pendente'];

        if (in_array($statusAssinatura, $statusBloqueados)) {
            $_SESSION['usuario_id']     = $userMaster['id'];
            $_SESSION['email']          = $userMaster['email']; 
            $_SESSION['usuario_logado'] = true; 
            $_SESSION['erro_assinatura'] = "Sua assinatura está com status: $statusAssinatura";
            $connMaster->close();
            header("Location: ../pages/assinar.php");
            exit;
        }
    }
    $connMaster->close();

    // 4. Configura Sessão do Tenant
    $idUsuarioTenant = null; 
    $nivelAcessoTenant = 'padrao';

    if ($tenant) {
        $_SESSION['tenant_db'] = [
            "db_host"     => $tenant['db_host'],
            "db_user"     => $tenant['db_user'],
            "db_password" => $tenant['db_password'],
            "db_database" => $tenant['db_database']
        ];

        // Conecta no tenant para pegar o ID do usuário lá dentro
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
            } else {
                // Se não existir no tenant (caso raro de desincronia), cria ou trata erro
                // Aqui vamos assumir o ID do master provisoriamente ou redirecionar erro
                $idUsuarioTenant = $userMaster['id']; 
            }
            $tenantConn->close();
        }
    }

    // 5. Salva Sessão
    unset($_SESSION['login_erro']);
    $_SESSION['usuario_id']       = $idUsuarioTenant ?? $userMaster['id'];
    $_SESSION['usuario_id_master']= $userMaster['id'];
    $_SESSION['nome']             = $userMaster['nome'];
    $_SESSION['email']            = $userMaster['email'];
    $_SESSION['tenant_id']        = $tenantId;
    $_SESSION['nivel_acesso']     = $nivelAcessoTenant;
    $_SESSION['usuario_logado']   = true;

    // --- MUDANÇA AQUI ---
    // Em vez de ir para selecionar_usuario.php, volta para login com flag de sucesso
    header("Location: ../pages/login.php?sucesso=1");
    exit;

} catch (Exception $e) {
    error_log("Erro login: " . $e->getMessage());
    $_SESSION['login_erro'] = "Erro interno. Tente novamente.";
    header("Location: ../pages/login.php");
    exit;
}
?>