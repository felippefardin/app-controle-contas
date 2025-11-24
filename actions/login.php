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
    // ============================
    // 1. CONEXÃO COM BANCO MASTER
    // ============================
    $connMaster = getMasterConnection();

    // Busca usuário no banco master
    $stmt = $connMaster->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userMaster = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ============================
    // 2. USUÁRIO EXISTE?
    // ============================
    if (!$userMaster) {
        $_SESSION['login_erro'] = "Conta não encontrada.";
        $connMaster->close();
        header("Location: ../pages/login.php");
        exit;
    }

    // ============================
    // 3. SENHA CORRETA?
    // ============================
    if (!password_verify($senha, $userMaster['senha'])) {
        $_SESSION['login_erro'] = "E-mail ou senha inválidos.";
        $connMaster->close();
        header("Location: ../pages/login.php");
        exit;
    }

    // ============================
    // 4. SUPER ADMIN
    // ============================
    $emails_admin = [
        'contatotech.tecnologia@gmail.com',
        'contatotech.tecnologia@gmail.com.br'
    ];

    if (in_array($userMaster['email'], $emails_admin)) {
        $_SESSION['super_admin'] = $userMaster;

        // Ajuste necessário
        if ($userMaster['email'] === 'contatotech.tecnologia@gmail.com') {
            $_SESSION['super_admin']['email'] = 'contatotech.tecnologia@gmail.com.br';
        }

        $connMaster->close();
        header('Location: ../pages/admin/dashboard.php');
        exit;
    }

    // ============================
    // 5. CARREGA TENANT DO USUÁRIO
    // ============================
    $tenantId = $userMaster['tenant_id'] ?? null;
    $tenant = null;

    if ($tenantId) {
        $stmt = $connMaster->prepare("SELECT * FROM tenants WHERE tenant_id = ? LIMIT 1");
        $stmt->bind_param("s", $tenantId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($tenant) {

            // 🔥 CORREÇÃO PRINCIPAL (ANTES errava usando campo "plano")
            $_SESSION['plano'] = $tenant['plano_atual'] ?? 'basico';
        }
    }

    // ============================
    // 6. VALIDA STATUS DA ASSINATURA
    // ============================
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

    // Fecha conexão master
    $connMaster->close();

    // ============================
    // 7. CONFIGURA SESSÃO DO TENANT
    // ============================
    $idUsuarioTenant = null;
    $nivelAcessoTenant = 'padrao';

    if ($tenant) {
        $_SESSION['tenant_db'] = [
            "db_host"     => $tenant['db_host'],
            "db_user"     => $tenant['db_user'],
            "db_password" => $tenant['db_password'],
            "db_database" => $tenant['db_database']
        ];

        // Puxa usuário do tenant
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
                $idUsuarioTenant = $userMaster['id'];
            }

            $tenantConn->close();
        }
    }

    // ============================
    // 8. FINALIZA A SESSÃO
    // ============================
    unset($_SESSION['login_erro']);

    $_SESSION['usuario_id']        = $idUsuarioTenant ?? $userMaster['id'];
    $_SESSION['usuario_id_master'] = $userMaster['id'];
    $_SESSION['nome']              = $userMaster['nome'];
    $_SESSION['email']             = $userMaster['email'];
    $_SESSION['tenant_id']         = $tenantId;
    $_SESSION['nivel_acesso']      = $nivelAcessoTenant;
    $_SESSION['usuario_logado']    = true;

    // ============================
    // 9. REDIRECIONA AO SISTEMA
    // ============================
    header("Location: ../pages/selecionar_usuario.php");
    exit;

} catch (Exception $e) {
    error_log("Erro login: " . $e->getMessage());
    $_SESSION['login_erro'] = "Erro interno. Tente novamente.";
    header("Location: ../pages/login.php");
    exit;
}
?>
