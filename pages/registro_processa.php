<?php
// pages/registro_processa.php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database.php'; 
require_once __DIR__ . '/../includes/utils.php'; // Importa sistema Flash Message

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$conn = getMasterConnection();

// Captura dados Pessoais
$nome        = trim($_POST['nome'] ?? '');
$email       = trim($_POST['email'] ?? '');
$senha       = trim($_POST['senha'] ?? '');
$tipo_pessoa = trim($_POST['tipo_pessoa'] ?? 'fisica');
$documento   = trim($_POST['documento'] ?? '');
$telefone    = trim($_POST['telefone'] ?? '');
$plano_post  = trim($_POST['plano'] ?? 'basico');

$cupom_codigo = isset($_POST['cupom']) && !empty($_POST['cupom']) ? strtoupper(trim($_POST['cupom'])) : null;
$codigo_indicacao_recebido = isset($_POST['codigo_indicacao']) && !empty($_POST['codigo_indicacao']) ? strtoupper(trim($_POST['codigo_indicacao'])) : null;

// --- NOVA FUNÇÃO E ARMAZENAMENTO DE DADOS ANTIGOS ---
$form_data = [
    'nome' => $nome,
    'email' => $email,
    'tipo_pessoa' => $tipo_pessoa,
    'documento' => $documento,
    'telefone' => $telefone,
    'plano' => $plano_post,
    'cupom' => $_POST['cupom'] ?? '',
    'codigo_indicacao' => $_POST['codigo_indicacao'] ?? ''
];

function return_error($msg, $data) {
    global $conn;
    if(isset($conn)) $conn->close();
    $_SESSION['form_data'] = $data; // Salva os dados na sessão
    set_flash_message('danger', $msg);
    header("Location: ../pages/registro.php");
    exit;
}
// ----------------------------------------------------

// Regras de Plano
if ($plano_post === 'essencial') {
    $dias_teste = 30;
    $plano_escolhido = 'essencial';
} elseif ($plano_post === 'plus') {
    $dias_teste = 15;
    $plano_escolhido = 'plus';
} else {
    $dias_teste = 15;
    $plano_escolhido = 'basico';
}

if (!$nome || !$email || !$senha || !$documento) {
    return_error("Preencha todos os campos obrigatórios.", $form_data);
}

// Verifica E-mail
$stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    $stmtCheck->close();
    return_error("Este e-mail já está cadastrado. Tente fazer login.", $form_data);
}
$stmtCheck->close();

// Gerar Código Único de Indicação
$codigo_novo_usuario = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$checkCode = $conn->prepare("SELECT id FROM usuarios WHERE codigo_indicacao = ?");
$checkCode->bind_param("s", $codigo_novo_usuario);
$checkCode->execute();
while ($checkCode->get_result()->num_rows > 0) {
    $codigo_novo_usuario = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $checkCode->bind_param("s", $codigo_novo_usuario);
    $checkCode->execute();
}
$checkCode->close();

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {
    // 1. Insert Master User
    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo_pessoa, documento, telefone, nivel_acesso, perfil, tipo, status, is_master, codigo_indicacao)
        VALUES (?, ?, ?, ?, ?, ?, 'proprietario', 'admin', 'admin', 'ativo', 1, ?)
    ");
    $stmtUser->bind_param("sssssss", $nome, $email, $senha_hash, $tipo_pessoa, $documento, $telefone, $codigo_novo_usuario);
    $stmtUser->execute();
    $new_usuario_id = $conn->insert_id;
    $stmtUser->close();

    // 2. Tenant Setup
    $tenantId = 'T' . substr(md5(uniqid($email, true)), 0, 32);
    $dbHost     = $_ENV['DB_HOST'] ?? 'localhost';
    $dbDatabase = 'tenant_db_' . $new_usuario_id;
    $dbUser     = 'dbuser_' . $new_usuario_id;
    $dbPassword = bin2hex(random_bytes(16));
    $nome_empresa = $nome; 

    $stmtTenant = $conn->prepare("
        INSERT INTO tenants (
            tenant_id, usuario_id, nome, nome_empresa, admin_email, senha, 
            status_assinatura, data_inicio_teste, plano_atual, 
            db_host, db_database, db_user, db_password,
            cupom_registro, msg_cupom_visto, msg_indicacao_visto
        ) VALUES (?, ?, ?, ?, ?, ?, 'trial', NOW(), ?, ?, ?, ?, ?, ?, 0, 0)
    ");

    $stmtTenant->bind_param(
        "sissssssssss", 
        $tenantId, $new_usuario_id, $nome, $nome_empresa, $email, $senha_hash,
        $plano_escolhido,
        $dbHost, $dbDatabase, $dbUser, $dbPassword,
        $cupom_codigo
    );
    $stmtTenant->execute();
    $stmtTenant->close();

    // 3. Processar Indicação
    if ($codigo_indicacao_recebido) {
        $sqlInd = "SELECT id FROM usuarios WHERE codigo_indicacao = ? LIMIT 1";
        $stmtInd = $conn->prepare($sqlInd);
        $stmtInd->bind_param("s", $codigo_indicacao_recebido);
        $stmtInd->execute();
        $resInd = $stmtInd->get_result();
        
        if ($resInd->num_rows > 0) {
            $indicador = $resInd->fetch_assoc();
            $id_indicador = $indicador['id'];
            
            if ($id_indicador != $new_usuario_id) {
                $stmtInsInd = $conn->prepare("INSERT INTO indicacoes (id_indicador, id_indicado) VALUES (?, ?)");
                $stmtInsInd->bind_param("ii", $id_indicador, $new_usuario_id);
                $stmtInsInd->execute();
                $stmtInsInd->close();
            }
        }
        $stmtInd->close();
    }

    // 4. Update User Tenant ID
    $conn->query("UPDATE usuarios SET tenant_id = '$tenantId' WHERE id = $new_usuario_id");

    // 5. Create Tenant DB & Schema
    $rootConn = new mysqli($dbHost, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $safeDbName = $rootConn->real_escape_string($dbDatabase);
    $safeDbUser = $rootConn->real_escape_string($dbUser);
    $safeDbPass = $rootConn->real_escape_string($dbPassword);

    $rootConn->query("CREATE DATABASE IF NOT EXISTS `$safeDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $rootConn->query("CREATE USER '$safeDbUser'@'localhost' IDENTIFIED BY '$safeDbPass'");
    $rootConn->query("GRANT ALL PRIVILEGES ON `$safeDbName`.* TO '$safeDbUser'@'localhost'");
    $rootConn->query("FLUSH PRIVILEGES");

    $schemaPath = __DIR__ . '/../schema.sql';
    if (file_exists($schemaPath)) {
        $tenantConn = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
        $schemaSql = file_get_contents($schemaPath);
        if ($tenantConn->multi_query($schemaSql)) {
            do { if ($res = $tenantConn->store_result()) $res->free(); } while ($tenantConn->more_results() && $tenantConn->next_result());
        }
        
        $stmtTI = $tenantConn->prepare("INSERT INTO usuarios (nome, email, senha, tipo_pessoa, documento, telefone, nivel_acesso, perfil, status, is_master, tenant_id) VALUES (?, ?, ?, ?, ?, ?, 'proprietario', 'admin', 'ativo', 1, ?)");
        $stmtTI->bind_param("sssssss", $nome, $email, $senha_hash, $tipo_pessoa, $documento, $telefone, $tenantId);
        $stmtTI->execute();
        $stmtTI->close();
        $tenantConn->close();
    }
    $rootConn->close();

    $conn->commit();

    // SUCESSO: Limpa os dados salvos pois deu certo
    unset($_SESSION['form_data']);

    // MENSAGEM DE SUCESSO E REDIRECIONA PARA LOGIN
    set_flash_message('success', "Cadastro realizado com sucesso!<br>Teste Grátis de $dias_teste dias ativado.");
    header("Location: ../pages/login.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    return_error("Erro ao registrar: " . $e->getMessage(), $form_data);
}
?>