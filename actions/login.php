<?php
require_once '../includes/session_init.php';
require_once __DIR__ . '/../database.php'; // Contém getMasterConnection() e getTenantConnection()

// 🔹 Captura dados do formulário
$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if (!$email || !$senha) {
    $_SESSION['login_erro'] = "Preencha todos os campos.";
    header("Location: ../pages/login.php");
    exit;
}

try {
    // 🔹 1. Buscar usuário master (para identificar tenant)
    $connMaster = getMasterConnection();
    $stmt = $connMaster->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userMaster = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $connMaster->close();

    if (!$userMaster) {
        $_SESSION['login_erro'] = "E-mail ou senha inválidos.";
        header("Location: ../pages/login.php");
        exit;
    }

    // 🔹 2. Validar senha
    if (!password_verify($senha, $userMaster['senha'])) {
        $_SESSION['login_erro'] = "E-mail ou senha inválidos.";
        header("Location: ../pages/login.php");
        exit;
    }

    // 🔹 3. Buscar tenant associado (se houver)
    $tenantId = $userMaster['tenant_id'] ?? null;
    $tenant = null;
    if ($tenantId) {
        $connMaster = getMasterConnection();
        $stmt = $connMaster->prepare("SELECT * FROM tenants WHERE tenant_id = ? LIMIT 1");
        $stmt->bind_param("s", $tenantId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $connMaster->close();
    }

    // 🔹 4. Carregar tenant na sessão se existir
    if ($tenant) {
        $_SESSION['tenant_db'] = [
            "db_host"     => $tenant['db_host'],
            "db_user"     => $tenant['db_user'],
            "db_password" => $tenant['db_password'],
            "db_database" => $tenant['db_database']
        ];

        // 🔹 Garantir que o schema está criado
        ensureTenantDatabaseExists(
            $tenant['db_host'],
            $tenant['db_user'],
            $tenant['db_password'],
            $tenant['db_database']
        );

        // 🔹 Executar schema.sql no tenant se a tabela usuarios não existir
        $tenantConn = getTenantConnection();
        $check = $tenantConn->query("SHOW TABLES LIKE 'usuarios'");
        if ($check->num_rows == 0) {
            $schemaPath = __DIR__ . '/../schema.sql';
            if (!file_exists($schemaPath)) {
                throw new Exception("Schema do tenant não encontrado.");
            }

            $schemaSql = file($schemaPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $query = '';
            foreach ($schemaSql as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '--')) continue;
                $query .= $line . ' ';
                if (str_ends_with($line, ';')) {
                    if (!$tenantConn->query($query)) {
                        throw new Exception("Erro ao criar tabela no tenant: " . $tenantConn->error);
                    }
                    $query = '';
                }
            }
        }
    }

    // 🔹 5. Sucesso: salvar sessão do usuário
    $_SESSION['usuario_id']       = $userMaster['id'];
    $_SESSION['nome']             = $userMaster['nome'];
    $_SESSION['email']            = $userMaster['email'];
    $_SESSION['tenant_id']        = $tenantId;
    $_SESSION['nivel_acesso']     = $userMaster['nivel'] ?? 'padrao'; // admin | padrao
    $_SESSION['is_master_admin']  = $userMaster['is_master'] ? true : false;
    $_SESSION['usuario_logado']   = true;

    header("Location: ../pages/home.php");
    exit;

} catch (Exception $e) {
    error_log("[LOGIN ERROR] " . $e->getMessage());
    $_SESSION['login_erro'] = "Erro ao processar login. Tente novamente.";
    header("Location: ../pages/login.php");
    exit;
}
?>
