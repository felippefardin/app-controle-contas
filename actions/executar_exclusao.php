<?php
// Inclui o $env e as funções de conexão
require_once '../database.php'; 

// Força o mysqli a lançar exceções
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Conexão principal (para tabelas tenants, usuarios, solicitacoes_exclusao)
$conn = getMasterConnection();
if ($conn === null) {
    die("Falha ao conectar ao banco de dados principal.");
}

$token = $_POST['token'] ?? '';
$id_usuario = null;
$tenant_id = null;
$db_name = null;
$db_user = null;

if (empty($token)) {
    header("Location: ../pages/login.php?erro=token_invalido");
    exit;
}

try {
    // 1️⃣ Valida o token e busca o ID do usuário (na tabela principal 'usuarios')
    $stmt = $conn->prepare("SELECT id_usuario FROM solicitacoes_exclusao WHERE token = ? AND expira_em > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($id_usuario);
    if (!$stmt->fetch()) {
        throw new Exception("Token inválido ou expirado.");
    }
    $stmt->close();

    // 2️⃣ Busca o ID do tenant (na tabela principal 'usuarios')
    $stmt_tenant = $conn->prepare("SELECT tenant_id FROM usuarios WHERE id = ?");
    $stmt_tenant->bind_param("i", $id_usuario);
    $stmt_tenant->execute();
    $stmt_tenant->bind_result($tenant_id);
    if (!$stmt_tenant->fetch() || $tenant_id === null) {
        throw new Exception("Não foi possível encontrar o tenant_id para este usuário.");
    }
    $stmt_tenant->close();

    // 3️⃣ Busca as credenciais do banco de dados do tenant (na tabela 'tenants')
    $stmt_creds = $conn->prepare("SELECT db_database, db_user FROM tenants WHERE id = ?");
    $stmt_creds->bind_param("i", $tenant_id);
    $stmt_creds->execute();
    $stmt_creds->bind_result($db_name, $db_user);
    if (!$stmt_creds->fetch() || empty($db_name) || empty($db_user)) {
        throw new Exception("Não foi possível encontrar as credenciais do banco de dados do tenant.");
    }
    $stmt_creds->close();

    // 4️⃣ Conecta-se como admin do MySQL para apagar o banco e o usuário
    // (Usa as variáveis $env carregadas pelo 'database.php')
    $admin_conn = new mysqli($env['DB_HOST'], $env['DB_ADMIN_USER'], $env['DB_ADMIN_PASS']);
    if ($admin_conn->connect_error) {
        throw new Exception("Falha ao conectar como admin do MySQL: " . $admin_conn->connect_error);
    }
    
    // Apaga o banco de dados do tenant (ex: tenant_47b79207a6e7b08d)
    $admin_conn->query("DROP DATABASE IF EXISTS `{$db_name}`");
    
    // Apaga o usuário do MySQL associado a esse banco
    $admin_conn->query("DROP USER IF EXISTS '{$db_user}'@'localhost'");
    
    $admin_conn->close();

    
    // 5️⃣ Apaga os registros das tabelas principais (tenants e usuarios)
    //    A linha em 'solicitacoes_exclusao' será apagada pelo 'ON DELETE CASCADE'
    
    $conn->begin_transaction();
    
    // Apaga da tabela 'tenants'
    $stmt_del_tenant = $conn->prepare("DELETE FROM tenants WHERE id = ?");
    $stmt_del_tenant->bind_param("i", $tenant_id);
    $stmt_del_tenant->execute();
    $stmt_del_tenant->close();
    
    // Apaga da tabela 'usuarios'
    $stmt_del_user = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt_del_user->bind_param("i", $id_usuario);
    $stmt_del_user->execute();
    $stmt_del_user->close();
    
    $conn->commit();

    // 6️⃣ Finaliza a sessão e redireciona
    session_start();
    session_destroy();
    header("Location: ../pages/login.php?sucesso=conta_excluida");
    exit;

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback(); // Desfaz a transação se algo falhar
    }
    error_log("Falha na exclusão da conta: " . $e->getMessage());
    header("Location: ../pages/login.php?erro=falha_exclusao_completa");
    exit;
} finally {
    if ($conn) $conn->close();
}
?>