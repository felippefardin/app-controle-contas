<?php
// Carrega as variáveis de ambiente do arquivo .env
$env = parse_ini_file('.env');

$host = $env['DB_HOST'];
$user = $env['DB_USER'];
$password = $env['DB_PASSWORD'];
$database = $env['DB_DATABASE'];

// Melhora o tratamento de erros
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Em um ambiente de produção, você poderia logar o erro em vez de exibi-lo.
    // Por enquanto, uma mensagem genérica é mais segura.
    die("Erro fatal de conexão com o sistema. Por favor, tente novamente mais tarde.");
}
?>