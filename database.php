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

// 🔹 Banco de dados master (padrão)
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * 🔹 Conexão principal (banco master)
 * Inclui suporte a SSL e tratamento completo de exceções
 */
function getMasterConnection() {
    global $host, $user, $password, $database;

    try {
        $conn = mysqli_init();

        // SSL opcional — não falha se o servidor não suportar SSL
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

        if (!mysqli_real_connect($conn, $host, $user, $password, $database)) {
            throw new mysqli_sql_exception("❌ Falha ao conectar: " . mysqli_connect_error());
        }

        if (!$conn->set_charset("utf8mb4")) {
            throw new mysqli_sql_exception("❌ Erro ao definir charset: " . $conn->error);
        }

        return $conn;
    } catch (mysqli_sql_exception $e) {
        error_log("❌ Erro de conexão MASTER: " . $e->getMessage());
        die("❌ Erro ao conectar ao banco de dados master: " . htmlspecialchars($e->getMessage()));
    }
}

/**
 * 🔹 Conexão do banco de dados do Tenant (cliente)
 * Lê credenciais da sessão ou usa o master se preferido
 */
function getTenantConnection() {
    // Se as informações do tenant não estiverem na sessão, usa o banco master
    if (!isset($_SESSION['tenant_db'])) {
        error_log("⚠️ Sessão do tenant ausente — conectando ao banco principal.");
        return getMasterConnection();
    }

    $db_info = $_SESSION['tenant_db'];

    try {
        $conn = mysqli_init();

        // SSL opcional
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

        if (!mysqli_real_connect(
            $conn,
            $db_info['db_host'],
            $db_info['db_user'],
            $db_info['db_password'],
            $db_info['db_database']
        )) {
            throw new mysqli_sql_exception("❌ Falha ao conectar: " . mysqli_connect_error());
        }

        if (!$conn->set_charset("utf8mb4")) {
            throw new mysqli_sql_exception("❌ Erro ao definir charset: " . $conn->error);
        }

        return $conn;

    } catch (mysqli_sql_exception $e) {
        error_log("❌ Erro de conexão TENANT: " . $e->getMessage());
        // Retorna null para que o sistema possa tratar a falha sem quebrar
        return null;
    }
}

/**
 * 🔹 Garante que o banco de um tenant exista — cria se necessário
 */
function ensureTenantDatabaseExists($db_host, $db_user, $db_password, $db_database) {
    try {
        $conn = mysqli_init();
        mysqli_real_connect($conn, $db_host, $db_user, $db_password);

        $exists = $conn->query("SHOW DATABASES LIKE '{$db_database}'")->num_rows > 0;
        if (!$exists) {
            $conn->query("CREATE DATABASE `{$db_database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            error_log("✅ Banco de tenant criado: {$db_database}");
        }

        $conn->close();
    } catch (mysqli_sql_exception $e) {
        error_log("❌ Erro ao verificar/criar banco do tenant: " . $e->getMessage());
    }
    
}

