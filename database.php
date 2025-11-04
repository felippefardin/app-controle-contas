<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'saas_master'; // Ajustei para o nome que você usou

// --- Adicione este array $env para compatibilidade ---
$env = [
    'DB_HOST' => $host,
    'DB_USER' => $user,
    'DB_PASSWORD' => $password,
    'DB_DATABASE' => $database,
    'DB_ADMIN_USER' => $_ENV['DB_ADMIN_USER'] ?? 'root',
    'DB_ADMIN_PASS' => $_ENV['DB_ADMIN_PASS'] ?? ''
];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function getMasterConnection() {
    global $host, $user, $password, $database;
    try {
        $conn = new mysqli($host, $user, $password, $database);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (mysqli_sql_exception $e) {
        die("Erro fatal de conexão com o sistema: " . $e->getMessage());
    }
}

function getTenantConnection() {
    if (isset($_SESSION['tenant_db'])) {
        $db_info = $_SESSION['tenant_db'];
        try {
            $conn = new mysqli(
                $db_info['db_host'], // Corrigido para usar o host da sessão
                $db_info['db_user'],
                $db_info['db_password'],
                $db_info['db_database'] // Corrigido para db_database
            );
            $conn->set_charset("utf8mb4");
            return $conn;
        } catch (mysqli_sql_exception $e) {
            session_destroy();
            header('Location: ../pages/login.php?erro=db_tenant');
            exit;
        }
    }
    return null;
}

/**
 * ✅ FUNÇÃO EXISTENTE (CORRIGIDA)
 * Obtém uma conexão com o banco de dados de um tenant específico,
 * buscando as credenciais no banco de dados principal (master).
 * Esta função NÃO depende de SESSÃO.
 *
 * (Eu corrigi a consulta SQL de 'db_name' para 'db_database' para bater com seu schema)
 *
 * @param int $tenant_id O ID do tenant (da tabela 'tenants').
 * @return mysqli|null A conexão mysqli com o banco do tenant ou null em caso de falha.
 */
function getTenantConnectionById($tenant_id) {
    // Pega a conexão principal
    $main_conn = getMasterConnection();
    if ($main_conn === null) {
        return null;
    }

    // 2. Buscar as credenciais do banco de dados do tenant
    // ✅ CORRIGIDO: Trocado 'db_name' por 'db_database'
    $stmt = $main_conn->prepare("SELECT db_host, db_database, db_user, db_password FROM tenants WHERE id = ?");
    if (!$stmt) {
        $main_conn->close();
        return null; // Falha ao preparar a consulta
    }
    
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    
    // $db_name é a variável que recebe o valor de db_database
    $stmt->bind_result($db_host, $db_name, $db_user, $db_pass); 
    
    if (!$stmt->fetch()) {
        // Tenant não encontrado
        $stmt->close();
        $main_conn->close();
        return null;
    }
    
    $stmt->close();
    $main_conn->close(); // Fechamos a conexão principal

    // 3. Conectar ao Banco de Dados Específico do Tenant
    if ($db_host && $db_name && $db_user) {
        try {
            // Usamos a variável $db_name que recebeu o valor
            $tenant_conn = new mysqli($db_host, $db_user, $db_pass, $db_name); 
            $tenant_conn->set_charset("utf8mb4");
            return $tenant_conn; // SUCESSO!
        } catch (mysqli_sql_exception $e) {
            // Falha ao conectar no banco do tenant
            return null;
        }
    }

    return null; // Caso algo tenha faltado
}

// ✅ ==========================================================
// ✅ FUNÇÃO QUE FALTAVA (ADICIONADA AGORA)
// ✅ Esta é a função que o script 'resetar_senha_usuario.php' está tentando chamar.
// ✅ ==========================================================
function getTenantConnectionByName($tenant_db_name) {
    try {
        // 1. Conectar ao banco principal para encontrar as credenciais do tenant
        $mainConn = getMasterConnection(); // Reutiliza sua função de conexão principal
        if ($mainConn === null) {
            return null; 
        }

        // 2. Buscar as credenciais do tenant
        // (Baseado no seu schema.sql, a coluna é db_database)
        $stmt = $mainConn->prepare("SELECT db_host, db_user, db_password, db_database FROM tenants WHERE db_database = ?");
        if (!$stmt) {
            $mainConn->close();
            return null;
        }
        
        $stmt->bind_param("s", $tenant_db_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $tenant_info = $result->fetch_assoc();
        
        $stmt->close();
        $mainConn->close();

        if (!$tenant_info) {
            // Tenant não encontrado
            return null;
        }

        // 3. Criar e retornar a conexão com o banco do tenant
        $tenantConn = new mysqli(
            $tenant_info['db_host'],
            $tenant_info['db_user'],
            $tenant_info['db_password'],
            $tenant_info['db_database']
        );

        if ($tenantConn->connect_error) {
            return null;
        }

        $tenantConn->set_charset("utf8mb4");
        return $tenantConn;

    } catch (Exception $e) {
        return null;
    }
}


// Inicializa a conexão master (se você usa $conn como global em outros lugares)
$conn = getMasterConnection();
?>