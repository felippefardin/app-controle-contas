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
