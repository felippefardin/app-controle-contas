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

// 🔹 Hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// 🔹 Iniciar transação
$conn->begin_transaction();

try {
    // 🔹 1. Verificar e-mail duplicado
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

    // 🔹 2. Inserir usuário MASTER (admin do tenant)
    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo_pessoa, documento, telefone, nivel_acesso, perfil, tipo, status, is_master)
        VALUES (?, ?, ?, ?, ?, ?, 'admin', 'admin', 'admin', 'ativo', 1)
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

    // 🔹 3. Criar tenant
    $tenantId = 'T' . substr(md5(uniqid($email, true)), 0, 32);

    // Nomes curtos para evitar limite de 32 caracteres
    $dbHost     = $_ENV['DB_HOST'] ?? 'localhost';
    $dbDatabase = 'tenant_db_' . $new_usuario_id;
    $dbUser     = 'dbuser_' . $new_usuario_id;
    $dbPassword = bin2hex(random_bytes(16));

    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (
            tenant_id, usuario_id, nome, admin_email, senha, 
            status_assinatura, data_inicio_teste, plano_atual, 
            db_host, db_database, db_user, db_password
        ) VALUES (?, ?, ?, ?, ?, 'trial', NOW(), ?, ?, ?, ?, ?)
    ");
    $stmtTenant->bind_param(
        "sissssssss",
        $tenantId,
        $new_usuario_id,
        $nome,
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

    // 🔹 4. Atualizar tenant_id no usuário
    $stmtUpdateUser = $conn->prepare("UPDATE usuarios SET tenant_id = ? WHERE id = ?");
    $stmtUpdateUser->bind_param("si", $tenantId, $new_usuario_id);
    $stmtUpdateUser->execute();
    $stmtUpdateUser->close();

    // 🔹 5. Criar banco e usuário no MySQL
    $rootConn = new mysqli($dbHost, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $rootConn->query("CREATE DATABASE IF NOT EXISTS `$dbDatabase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $rootConn->query("CREATE USER IF NOT EXISTS '$dbUser'@'localhost' IDENTIFIED BY '$dbPassword'");
    $rootConn->query("GRANT ALL PRIVILEGES ON `$dbDatabase`.* TO '$dbUser'@'localhost'");
    $rootConn->query("FLUSH PRIVILEGES");

    // 🔹 6. Executar schema.sql no banco do tenant
    $schemaPath = __DIR__ . '/../schema.sql';
    if (file_exists($schemaPath)) {
        $tenantConn = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
        $schemaSql = file_get_contents($schemaPath);

        if ($tenantConn->multi_query($schemaSql)) {
            do {
                if ($result = $tenantConn->store_result()) {
                    $result->free();
                }
            } while ($tenantConn->more_results() && $tenantConn->next_result());
        }
        $tenantConn->close();
    } else {
        error_log("❌ schema.sql não encontrado em $schemaPath");
        throw new Exception("Erro interno: schema do tenant não encontrado.");
    }

    $rootConn->close();

    // 🔹 7. Commit da transação master
    $conn->commit();

    $_SESSION['registro_sucesso'] = "Cadastro realizado! Você ganhou $dias_teste dias de teste grátis.";
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro no registro: " . $e->getMessage());
    $_SESSION['erro_registro'] = "Erro ao registrar usuário. Tente novamente.";
    header("Location: ../pages/registro.php?msg=erro_db_fatal");
    exit;
}
?>
