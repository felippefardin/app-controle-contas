<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/session_init.php';


use MercadoPago\MercadoPagoConfig;
use Dotenv\Dotenv;

// 🔹 Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 🔹 Configura Mercado Pago
if (!empty($_ENV['MP_ACCESS_TOKEN'])) {
    MercadoPagoConfig::setAccessToken($_ENV['MP_ACCESS_TOKEN']);
} else {
    error_log("⚠️ MP_ACCESS_TOKEN não encontrado no .env");
}

// 🔹 Banco de dados
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'saas_master';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function getMasterConnection() {
    global $host, $user, $password, $database;
    try {
        $conn = new mysqli($host, $user, $password, $database);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (mysqli_sql_exception $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}
