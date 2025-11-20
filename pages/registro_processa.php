<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database.php'; // getMasterConnection()

use Dotenv\Dotenv;

// 🔹 Carregar variáveis do .env (garantia)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 🔹 Conexão MASTER
$conn = getMasterConnection();

// 🔹 Captura dados do formulário
$nome        = trim($_POST['nome'] ?? '');
$email       = trim($_POST['email'] ?? '');
$senha       = trim($_POST['senha'] ?? '');
$tipo_pessoa = trim($_POST['tipo_pessoa'] ?? 'fisica');
$documento   = trim($_POST['documento'] ?? '');
$telefone    = trim($_POST['telefone'] ?? '');

// 🔹 Plano e dias de trial
$plano_escolhido = trim($_GET['plano'] ?? 'mensal');
$plano_escolhido = in_array($plano_escolhido, ['mensal', 'trimestral']) ? $plano_escolhido : 'mensal';
$dias_teste = ($plano_escolhido === 'trimestral') ? 30 : 15;

// 🔹 Validação mínima
if (!$nome || !$email || !$senha) {
    $_SESSION['erro_registro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/registro.php");
    exit;
}

// ⛔ IMPEDIR REGISTRO COMO SUPER ADMIN
// Ninguém pode se registrar externamente com o e-mail do super admin
if ($email === 'contatotech.tecnologia@gmail.com.br') {
    $_SESSION['erro_registro'] = "Este e-mail é reservado para administração do sistema.";
    header("Location: ../pages/registro.php?msg=email_reservado");
    exit;
}

// 🔹 Hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// 🔹 Iniciar transação
$conn->begin_transaction();

try {
    // 🔹 1. Verificar e-mail duplicado no banco Master
    $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['erro_registro'] = "Este e-mail já está cadastrado.";
        $conn->rollback();
        header("Location: ../pages/registro.php?msg=email_duplicado");
        exit;
    }
    $stmtCheck->close();

    // 🔹 2. Inserir usuário MASTER (tabela global de login)
    // is_master = 1 aqui significa "Dono da Conta/Tenant", não Super Admin do sistema.
    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo_pessoa, documento, telefone, nivel_acesso, perfil, tipo, status, is_master)
        VALUES (?, ?, ?, ?, ?, ?, 'proprietario', 'admin', 'admin', 'ativo', 1)
    ");
    $stmtUser->bind_param(
        "ssssss",
        $nome,
        $email,
        $senha_hash,
        $tipo_pessoa,
        $documento,
        $telefone
    );
    $stmtUser->execute();
    $new_usuario_id = $conn->insert_id;
    $stmtUser->close();

    // 🔹 3. Gerar ID do Tenant e Credenciais
    $tenantId = 'T' . substr(md5(uniqid($email, true)), 0, 32);

    // Dados do banco do tenant
    $dbHost     = $_ENV['DB_HOST'] ?? 'localhost';
    $dbDatabase = 'tenant_db_' . $new_usuario_id;
    $dbUser     = 'dbuser_' . $new_usuario_id;
    $dbPassword = bin2hex(random_bytes(16));

    // Inserir na tabela tenants
    // 🔹 CORREÇÃO: Adicionado 'nome_empresa' na lista de colunas e valores
    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (
            tenant_id, usuario_id, nome, nome_empresa, admin_email, senha, 
            status_assinatura, data_inicio_teste, plano_atual, 
            db_host, db_database, db_user, db_password
        ) VALUES (?, ?, ?, ?, ?, ?, 'trial', NOW(), ?, ?, ?, ?, ?)
    ");
    
    // Define o nome da empresa igual ao nome do usuário inicialmente
    $nome_empresa = $nome; 

    $stmtTenant->bind_param(
        "sisssssssss", // Ajustado o número de 's' (strings) e 'i' (inteiros)
        $tenantId,
        $new_usuario_id,
        $nome,
        $nome_empresa, // Valor novo
        $email,
        $senha_hash,
        $plano_escolhido,
        $dbHost,
        $dbDatabase,
        $dbUser,
        $dbPassword
    );
    $stmtTenant->execute();
    $stmtTenant->close();

    // 🔹 4. Atualizar tenant_id no usuário Master
    $stmtUpdateUser = $conn->prepare("UPDATE usuarios SET tenant_id = ? WHERE id = ?");
    $stmtUpdateUser->bind_param("si", $tenantId, $new_usuario_id);
    $stmtUpdateUser->execute();
    $stmtUpdateUser->close();

    // 🔹 5. Criar banco de dados físico e usuário MySQL
    $rootConn = new mysqli($dbHost, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    if ($rootConn->connect_error) {
        throw new Exception("Erro de conexão root: " . $rootConn->connect_error);
    }
    
    $safeDbName = $rootConn->real_escape_string($dbDatabase);
    $safeDbUser = $rootConn->real_escape_string($dbUser);
    $safeDbPass = $rootConn->real_escape_string($dbPassword);

    // Limpeza preventiva
    $rootConn->query("DROP USER IF EXISTS '$safeDbUser'@'localhost'");
    $rootConn->query("DROP USER IF EXISTS '$safeDbUser'@'%'");

    // Criar banco
    $rootConn->query("CREATE DATABASE IF NOT EXISTS `$safeDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Criar usuário MySQL específico para este tenant
    $rootConn->query("CREATE USER '$safeDbUser'@'localhost' IDENTIFIED BY '$safeDbPass'");
    $rootConn->query("GRANT ALL PRIVILEGES ON `$safeDbName`.* TO '$safeDbUser'@'localhost'");
    
    if ($dbHost !== 'localhost') {
        $rootConn->query("DROP USER IF EXISTS '$safeDbUser'@'$dbHost'");
        $rootConn->query("CREATE USER '$safeDbUser'@'$dbHost' IDENTIFIED BY '$safeDbPass'");
        $rootConn->query("GRANT ALL PRIVILEGES ON `$safeDbName`.* TO '$safeDbUser'@'$dbHost'");
    }

    $rootConn->query("FLUSH PRIVILEGES");

    // 🔹 6. Rodar o Schema no banco do Tenant
    $schemaPath = __DIR__ . '/../schema.sql';
    if (file_exists($schemaPath)) {
        // Conecta no banco NOVO usando as credenciais do TENANT
        $tenantConn = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
        
        if ($tenantConn->connect_error) {
            throw new Exception("Erro ao conectar no banco do tenant ($dbUser): " . $tenantConn->connect_error);
        }

        $schemaSql = file_get_contents($schemaPath);

        // Executa multi_query para criar as tabelas
        if ($tenantConn->multi_query($schemaSql)) {
            do {
                if ($result = $tenantConn->store_result()) {
                    $result->free();
                }
            } while ($tenantConn->more_results() && $tenantConn->next_result());
        } else {
            throw new Exception("Erro SQL no schema: " . $tenantConn->error);
        }

        // 🔹 6.1 Inserir o Usuário Proprietário DENTRO do Banco do Tenant
        // Este usuário poderá criar outros usuários (add_usuario.php) dentro deste banco
        $stmtTenantInsert = $tenantConn->prepare("
            INSERT INTO usuarios (
                nome, email, senha, tipo_pessoa, documento, telefone, 
                nivel_acesso, perfil, status, is_master, tenant_id
            ) VALUES (?, ?, ?, ?, ?, ?, 'proprietario', 'admin', 'ativo', 1, ?)
        ");
        
        if (!$stmtTenantInsert) {
             throw new Exception("Erro prepare insert tenant: " . $tenantConn->error);
        }

        $stmtTenantInsert->bind_param(
            "sssssss",
            $nome,
            $email,
            $senha_hash,
            $tipo_pessoa,
            $documento,
            $telefone,
            $tenantId
        );
        
        if (!$stmtTenantInsert->execute()) {
            throw new Exception("Erro execute insert tenant: " . $stmtTenantInsert->error);
        }
        $stmtTenantInsert->close();
        $tenantConn->close();

    } else {
        error_log("❌ schema.sql não encontrado.");
        throw new Exception("Erro interno: arquivo de banco de dados não encontrado.");
    }

    $rootConn->close();

    // 🔹 7. Commit final
    $conn->commit();

    $_SESSION['registro_sucesso'] = "Cadastro realizado com sucesso! Aproveite seus $dias_teste dias de teste.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro no registro: " . $e->getMessage());
    
    // Limpeza de emergência (Rollback manual)
    if (isset($rootConn) && isset($dbDatabase) && isset($dbUser)) {
        // Reconecta root se fechou para limpar
        $rootConn = new mysqli($dbHost, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
        $rootConn->query("DROP DATABASE IF EXISTS `$dbDatabase`");
        $rootConn->query("DROP USER IF EXISTS '$dbUser'@'localhost'");
        $rootConn->close();
    }

    $_SESSION['erro_registro'] = "Erro no sistema: " . $e->getMessage();
    header("Location: ../pages/registro.php?msg=erro_fatal");
    exit;
}
?>