<?php
require_once '../includes/session_init.php';
require_once __DIR__ . '/../database.php'; // Contém getMasterConnection(), getTenantConnection() e validarStatusAssinatura()

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
    // Não feche a $connMaster ainda, precisaremos dela para o tenant

    // ❗️❗️ INÍCIO DA CORREÇÃO DE MENSAGEM ❗️❗️
    if (!$userMaster) {
        // Mensagem específica se o e-mail não foi encontrado
        $_SESSION['login_erro'] = "E-mail não encontrado. Verifique o e-mail digitado.";
        $connMaster->close();
        header("Location: ../pages/login.php");
        exit;
    }

    // 🔹 2. Validar senha
    if (!password_verify($senha, $userMaster['senha'])) {
        // Mensagem específica se a senha estiver errada
        $_SESSION['login_erro'] = "Senha incorreta. Tente novamente.";
        $connMaster->close();
        header("Location: ../pages/login.php");
        exit;
    }
    // ❗️❗️ FIM DA CORREÇÃO DE MENSAGEM ❗️❗️

    // 🔹 3. Buscar tenant associado (se houver)
    $tenantId = $userMaster['tenant_id'] ?? null;
    $tenant = null;
    
    // SE HÁ UM ID DE TENANT, O TENANT PRECISA EXISTIR
    if ($tenantId) {
        $stmt = $connMaster->prepare("SELECT * FROM tenants WHERE tenant_id = ? LIMIT 1");
        $stmt->bind_param("s", $tenantId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$tenant) {
            error_log("[LOGIN ERROR] Usuário {$email} (ID: {$userMaster['id']}) tem tenant_id '{$tenantId}' órfão (não encontrado na tabela tenants).");
            $_SESSION['login_erro'] = "Sua conta está com um problema de configuração (Tenant ID '{$tenantId}' não encontrado). Contate o suporte.";
            $connMaster->close(); // Fecha a conexão master antes de sair
            header("Location: ../pages/login.php");
            exit;
        }
    }
    
    // 🔹 4. Validar Assinatura
    if ($tenant) {
        if (!function_exists('validarStatusAssinatura')) {
            error_log("[LOGIN ERROR] Função validarStatusAssinatura() não encontrada em database.php.");
            $_SESSION['login_erro'] = "Erro crítico na verificação de assinatura. Contate o suporte.";
            $connMaster->close();
            header("Location: ../pages/login.php");
            exit;
        }

        $statusAssinatura = validarStatusAssinatura($tenant);
        $_SESSION['subscription_status'] = $statusAssinatura; 

        $statusBloqueados = ['vencido', 'cancelado', 'trial_expired', 'pendente'];

        if (in_array($statusAssinatura, $statusBloqueados)) {
            // Salva dados mínimos necessários para a página 'assinar.php'
            $_SESSION['usuario_id']     = $userMaster['id']; // ID do usuário (da tabela master)
            $_SESSION['email']          = $userMaster['email']; 
            $_SESSION['tenant_id']      = $tenantId;
            $_SESSION['usuario_logado'] = true; 

            // Mensagem de erro para a página de assinatura
            if ($statusAssinatura === 'trial_expired') {
                $_SESSION['erro_assinatura'] = "Seu período de teste gratuito de 15 dias terminou. Por favor, escolha um plano para continuar.";
            } else {
                $_SESSION['erro_assinatura'] = "Sua assinatura está com status '{$statusAssinatura}'. Por favor, regularize para continuar.";
            }

            $connMaster->close();
            header("Location: ../pages/assinar.php");
            exit;
        }
    }
    
    // Fecha a conexão master após usá-la
    $connMaster->close();


    // 🔹 5. Carregar tenant na sessão e GARANTIR USUÁRIO
    $idUsuarioTenant = null; 
    $nivelAcessoTenant = 'padrao'; // Default inicial

    if ($tenant) {
        $_SESSION['tenant_db'] = [
            "db_host"     => $tenant['db_host'],
            "db_user"     => $tenant['db_user'],
            "db_password" => $tenant['db_password'],
            "db_database" => $tenant['db_database']
        ];

        ensureTenantDatabaseExists(
            $tenant['db_host'],
            $tenant['db_user'],
            $tenant['db_password'],
            $tenant['db_database']
        );

        $tenantConn = getTenantConnection();
        if (!$tenantConn) {
             session_destroy();
             header("Location: ../pages/login.php?erro=db_tenant_login");
             exit();
        }

        // Executar schema.sql no tenant se a tabela usuarios não existir
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

        // 1. Verificar se o usuário existe no tenant
        $stmtTenant = $tenantConn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmtTenant->bind_param("s", $email);
        $stmtTenant->execute();
        $userTenant = $stmtTenant->get_result()->fetch_assoc();
        $stmtTenant->close();
        
        if (!$userTenant) {
            // 2. Se não existe, criar o usuário no tenant
            $nomeTenant = $userMaster['nome'];
            $senhaTenant = $userMaster['senha']; // A senha JÁ ESTÁ HASHED
            $nivelAcessoTenant = 'proprietario'; // Definido como proprietario
            $tipoPessoaTenant = $userMaster['tipo_pessoa'] ?? 'fisica'; // Default 'fisica'

            $stmtInsert = $tenantConn->prepare("
                INSERT INTO usuarios (nome, email, senha, nivel_acesso, tipo_pessoa) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtInsert->bind_param("sssss", $nomeTenant, $email, $senhaTenant, $nivelAcessoTenant, $tipoPessoaTenant);
            
            if (!$stmtInsert->execute()) {
                error_log("[LOGIN ERROR] Falha ao auto-provisionar usuário {$email} no tenant {$tenantId}. Erro: " . $stmtInsert->error);
                $_SESSION['login_erro'] = "Falha ao configurar sua conta de usuário no sistema. Contate o suporte.";
                $tenantConn->close();
                header("Location: ../pages/login.php");
                exit;
            }
            $idUsuarioTenant = $stmtInsert->insert_id; // Pegamos o ID recém-criado
            $stmtInsert->close();
            
        } else {
            // 3. Se já existe, apenas usar os dados
            $idUsuarioTenant = $userTenant['id'];
            $nivelAcessoTenant = $userTenant['nivel_acesso'];
        }
        
        $tenantConn->close(); // Fechamos a conexão do tenant
    }

    // 🔹 6. Sucesso: salvar sessão do usuário
    unset($_SESSION['login_erro']);
    $_SESSION['usuario_id']       = $idUsuarioTenant; // ID do usuário DENTRO do tenant
    $_SESSION['usuario_id_master']= $userMaster['id'];  // ID do usuário na tabela MASTER
    $_SESSION['nome']             = $userMaster['nome'];
    $_SESSION['email']            = $userMaster['email'];
    $_SESSION['tenant_id']        = $tenantId;
    $_SESSION['nivel_acesso']     = $nivelAcessoTenant; // Usa a variável correta
    $_SESSION['is_master_admin']  = $userMaster['is_master'] ? true : false;
    $_SESSION['usuario_logado']   = true;

    // Redireciona para a seleção de usuário
    header("Location: ../pages/selecionar_usuario.php");
    exit;

} catch (Exception $e) {
    error_log("[LOGIN ERROR] " . $e->getMessage());
    $_SESSION['login_erro'] = "Erro ao processar login. Tente novamente.";
    if (isset($connMaster) && $connMaster) $connMaster->close();
    if (isset($tenantConn) && $tenantConn) $tenantConn->close();
    header("Location: ../pages/login.php");
    exit;
}
?>