<?php
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'saas_master';

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
                $db_info['db_host'],
                $db_info['db_user'],
                $db_info['db_password'],
                $db_info['db_database']
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

// Inicializa a conexão master
$conn = getMasterConnection();
?>
