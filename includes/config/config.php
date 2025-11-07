<?php
use Dotenv\Dotenv;

// 🔹 Carrega o autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// 🔹 Caminho do arquivo .env
$dotenvPath = realpath(__DIR__ . '/../../');
if (!$dotenvPath || !file_exists($dotenvPath . '/.env')) {
    die("❌ Arquivo .env não encontrado em: " . $dotenvPath);
}

// 🔹 Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->safeLoad();

// 🔹 Verifica variáveis importantes
$requiredVars = [
    'APP_URL',
    'MERCADOPAGO_MODE',
    'MP_ACCESS_TOKEN_SANDBOX',
    'MP_ACCESS_TOKEN_PRODUCAO'
];
foreach ($requiredVars as $var) {
    if (empty($_ENV[$var])) {
        echo "<pre>⚠️ Variável $var não encontrada no .env</pre>";
    }
}

// 🔹 Inclui conexão com banco e configurações gerais
require_once __DIR__ . '/../../database.php';
