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
 * ✅ FUNÇÃO ADICIONADA
 * Obtém uma conexão com o banco de dados de um tenant específico,
 * buscando as credenciais no banco de dados principal (master).
 * Esta função NÃO depende de SESSÃO.
 *
 * @param int $tenant_id O ID do tenant (da tabela 'tenants').
 * @return mysqli|null A conexão mysqli com o banco do tenant ou null em caso de falha.
 */
function getTenantConnectionById($tenant_id) {
    // Pega as credenciais do banco de dados master do .env
    $main_servername = $_ENV['DB_HOST'] ?? 'localhost';
    $main_username   = $_ENV['DB_USER'] ?? 'root';
    $main_password   = $_ENV['DB_PASSWORD'] ?? '';
    $main_database   = $_ENV['DB_DATABASE'] ?? 'app_controle_contas'; // Banco Master

    $main_conn = null;
    try {
        $main_conn = new mysqli($main_servername, $main_username, $main_password, $main_database);
        $main_conn->set_charset("utf8mb4");
    } catch (mysqli_sql_exception $e) {
        // Não foi possível conectar ao banco master
        return null;
    }

    // 2. Buscar as credenciais do banco de dados do tenant
    // (Assumindo que a tabela 'tenants' no banco master tem estas colunas)
    $db_host = null;
    $db_name = null;
    $db_user = null;
    $db_pass = null;

    // Ajuste o nome da tabela e colunas se for diferente no seu banco MASTER
    $stmt = $main_conn->prepare("SELECT db_host, db_name, db_user, db_password FROM tenants WHERE id = ?");
    if (!$stmt) {
        $main_conn->close();
        return null; // Falha ao preparar a consulta
    }
    
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
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


// Inicializa a conexão master (se você usa $conn como global em outros lugares)
$conn = getMasterConnection();
?>