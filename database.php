<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Carrega o .env corretamente (com suporte a aspas e comentários)
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // não quebra se já tiver sido carregado

// Lê as variáveis do ambiente
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'saas_master';

// Ativa tratamento de erro estruturado
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Conexão principal (Master) ---
function getMasterConnection() {
    global $host, $user, $password, $database;
    try {
        $master_conn = new mysqli($host, $user, $password, $database);
        $master_conn->set_charset("utf8mb4");
        return $master_conn;
    } catch (mysqli_sql_exception $e) {
        die("Erro fatal de conexão com o sistema: " . $e->getMessage());
    }
}

// --- Conexão do Cliente (Tenant) ---
function getTenantConnection() {
    if (isset($_SESSION['tenant_db'])) {
        $db_info = $_SESSION['tenant_db'];
        try {
            $tenant_conn = new mysqli(
                $db_info['db_host'],
                $db_info['db_user'],
                $db_info['db_password'],
                $db_info['db_database']
            );
            $tenant_conn->set_charset("utf8mb4");
            return $tenant_conn;
        } catch (mysqli_sql_exception $e) {
            session_destroy();
            header('Location: ../pages/login.php?erro=db_tenant');
            exit;
        }
    }
    return null;
}

// Inicializa a conexão principal
$conn = getMasterConnection();
?>
