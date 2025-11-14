<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/session_init.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

use Dotenv\Dotenv;
use MercadoPago\MercadoPagoConfig;

// -------------------------
// Carregar .env
// -------------------------
$dotenvPath = realpath(__DIR__ . '/');
if (!file_exists($dotenvPath . '/.env')) {
    $dotenvPath = realpath(__DIR__ . '/../');
}
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->safeLoad();

// -------------------------
// Variáveis MASTER
// -------------------------
$db_host_master = $_ENV['DB_HOST'] ?? 'localhost';
$db_user_master = $_ENV['DB_USER'] ?? 'root';
$db_pass_master = $_ENV['DB_PASSWORD'] ?? '';
$db_name_master = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

// -------------------------
// Conexão MASTER
// -------------------------
function getMasterConnection()
{
    global $db_host_master, $db_user_master, $db_pass_master, $db_name_master;

    try {
        $conn = mysqli_init();
        mysqli_real_connect(
            $conn,
            $db_host_master,
            $db_user_master,
            $db_pass_master,
            $db_name_master
        );
        $conn->set_charset("utf8mb4");
        return $conn;

    } catch (mysqli_sql_exception $e) {
        error_log("Erro MASTER: " . $e->getMessage());
        die("Erro ao conectar ao banco master.");
    }
}

// -------------------------
// Conexão TENANT
// -------------------------
function getTenantConnection()
{
    if (!isset($_SESSION['tenant_db'])) {
        return null;
    }

    $db = $_SESSION['tenant_db'];

    try {
        $conn = mysqli_init();
        mysqli_real_connect(
            $conn,
            $db['db_host'],
            $db['db_user'],
            $db['db_password'],
            $db['db_database']
        );
        $conn->set_charset("utf8mb4");
        return $conn;

    } catch (mysqli_sql_exception $e) {
        error_log("Erro TENANT: " . $e->getMessage());
        return null;
    }
}

// -------------------------
// Garantir banco do TENANT
// -------------------------
function ensureTenantDatabaseExists($db_host, $db_user, $db_password, $db_database)
{
    try {
        $conn = mysqli_init();
        mysqli_real_connect($conn, $db_host, $db_user, $db_password);

        $result = $conn->query("SHOW DATABASES LIKE '{$db_database}'");

        if (!$result || $result->num_rows == 0) {
            error_log("🔧 Criando banco do tenant: {$db_database}");
            $conn->query("CREATE DATABASE `$db_database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $conn->close();
    } catch (mysqli_sql_exception $e) {
        error_log("❌ ensureTenantDatabaseExists ERROR: " . $e->getMessage());
    }
}

// -------------------------
// FUNÇÕES DE TENANT
// -------------------------

if (!function_exists('log_debug')) {
    function log_debug($msg) {
        error_log("[TENANT_UTILS] " . $msg);
    }
}

if (!function_exists('getTenantByUserId')) {
    function getTenantByUserId($userId) {
        $conn = getMasterConnection();

        $sql = "SELECT * FROM tenants WHERE usuario_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($tenant) log_debug("Tenant encontrado para usuario_id={$userId}: {$tenant['tenant_id']}");
        else log_debug("Nenhum tenant encontrado para usuario_id={$userId}");

        return $tenant ?: null;
    }
}

if (!function_exists('validarStatusAssinatura')) {
    function validarStatusAssinatura($tenant) {
        if (!$tenant) return "erro";

        if (isset($tenant['status_assinatura']) && $tenant['status_assinatura'] === 'ativo') {
            return "ok";
        }

        if (isset($tenant['status_assinatura']) && in_array($tenant['status_assinatura'], ['inativo','cancelado'])) {
            return $tenant['status_assinatura'];
        }

        if (isset($tenant['status_assinatura']) && $tenant['status_assinatura'] === 'trial') {
            if (empty($tenant['data_inicio_teste'])) return "ok";

            try {
                $inicio = new DateTime($tenant['data_inicio_teste']);
                $hoje   = new DateTime();
                $dias_passados = $inicio->diff($hoje)->days;

                $dias_trial_permitidos = 15;
                if (isset($tenant['plano_atual']) && $tenant['plano_atual'] === 'trimestral') {
                    $dias_trial_permitidos = 30;
                }

                if ($dias_passados > $dias_trial_permitidos) return "trial_expirado";

            } catch (Exception $e) {
                log_debug("Erro ao validar data_inicio_teste: " . $e->getMessage());
                return "ok";
            }
        }

        return "ok";
    }
}

if (!function_exists('carregarTenantNaSessao')) {
    function carregarTenantNaSessao($tenant) {
        if (!$tenant) return false;

        $_SESSION['tenant_id'] = $tenant['tenant_id'];
        $_SESSION['tenant_db'] = [
            "db_host"     => $tenant['db_host'],
            "db_user"     => $tenant['db_user'],
            "db_password" => $tenant['db_password'],
            "db_database" => $tenant['db_database']
        ];

        log_debug("Credenciais do tenant salvas na sessão: " . ($tenant['db_database'] ?? 'n/a'));
        return true;
    }
}

if (!function_exists('getTenantById')) {
    function getTenantById($tenant_id, $masterConn) {
        if (!$masterConn) return null;

        $stmt = $masterConn->prepare("SELECT * FROM tenants WHERE tenant_id = ? LIMIT 1");
        $stmt->bind_param("s", $tenant_id);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $tenant;
    }
}
