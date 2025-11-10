<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/session_init.php';

use Dotenv\Dotenv;
use MercadoPago\MercadoPagoConfig;

// 🔹 Carrega variáveis de ambiente corretamente da raiz do projeto
$dotenvPath = realpath(__DIR__ . '/');
if (!file_exists($dotenvPath . '/.env')) {
    $dotenvPath = realpath(__DIR__ . '/../'); // sobe um nível se não encontrar
}
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->safeLoad();

// 🔹 Configura Mercado Pago
if (!empty($_ENV['MP_ACCESS_TOKEN'])) {
    MercadoPagoConfig::setAccessToken($_ENV['MP_ACCESS_TOKEN']);
}

// 🔹 Banco de dados master
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function getMasterConnection() {
    global $host, $user, $password, $database;
    try {
        $conn = new mysqli($host, $user, $password, $database);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (mysqli_sql_exception $e) {
        die("❌ Erro de conexão: " . $e->getMessage());
    }
}

// ... (código existente para getMasterConnection e outras funções de banco)

/**
 * Cria e retorna a conexão com o banco de dados específico do Tenant (cliente).
 *
 * Utiliza as credenciais armazenadas na sessão.
 * @return mysqli|null A conexão mysqli ou null em caso de falha.
 */
function getTenantConnection() {
    // 1. Verifica se as informações de conexão do tenant estão na sessão
    if (!isset($_SESSION['tenant_db'])) {
        // Isso pode acontecer se a sessão expirar
        return null;
    }
    
    $db_info = $_SESSION['tenant_db'];
    
    // 2. Tenta conectar com as credenciais do tenant
    try {
        $tenant_conn = new mysqli(
            $db_info['db_host'],
            $db_info['db_user'],
            $db_info['db_password'],
            $db_info['db_database']
        );
        $tenant_conn->set_charset("utf8mb4");

        // 3. Verifica erro de conexão
        if ($tenant_conn->connect_error) {
            // Se falhar a conexão, retorna null para que a página possa tratar.
            error_log("Falha ao conectar ao banco do tenant: " . $tenant_conn->connect_error);
            return null; 
        }
        
        // 4. Retorna a conexão bem-sucedida
        return $tenant_conn;

    } catch (Exception $e) {
        // Loga a exceção e retorna null
        error_log("Exceção ao conectar ao banco do tenant: " . $e->getMessage());
        return null;
    }
}
