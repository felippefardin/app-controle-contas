<?php
// Carrega as variáveis de ambiente do arquivo .env da raiz do projeto
// __DIR__ pega o diretório do arquivo atual (que é a raiz), e '/.env' aponta para o arquivo.
$env_path = __DIR__ . '/.env';

if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
} else {
    die("Erro: O arquivo de configuração .env não foi encontrado na raiz do projeto.");
}


$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? ''; // Pega a senha do .env
$database = $env['DB_DATABASE'] ?? 'saas_master';

// Melhora o tratamento de erros
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Conexão com o Banco de Dados Principal (Master)
function getMasterConnection() {
    global $host, $user, $password, $database;
    try {
        $master_conn = new mysqli($host, $user, $password, $database);
        $master_conn->set_charset("utf8mb4");
        return $master_conn;
    } catch (mysqli_sql_exception $e) {
        // Exibe uma mensagem mais detalhada do erro para depuração
        die("Erro fatal de conexão com o sistema: " . $e->getMessage());
    }
}

// Conexão com o Banco de Dados do Cliente (Tenant)
function getTenantConnection() {
    if (isset($_SESSION['tenant_db'])) {
        $db_info = $_SESSION['tenant_db'];
        try {
            $tenant_conn = new mysqli($db_info['db_host'], $db_info['db_user'], $db_info['db_password'], $db_info['db_database']);
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

// Inicializa a conexão principal para uso geral
$conn = getMasterConnection();
?>