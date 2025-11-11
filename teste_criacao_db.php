<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database.php';

$logFile = __DIR__ . '/logs/register_debug.log';
file_put_contents($logFile, "===== TESTE DE CRIAÇÃO DE BANCO =====\n", FILE_APPEND);

try {
    $conn = getMasterConnection();
    file_put_contents($logFile, "Conexão master OK\n", FILE_APPEND);

    $tenantId = uniqid('T', true);
    $dbDatabase = 'tenant_db_' . md5($tenantId);
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbDatabase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    
    if ($conn->query($sql)) {
        file_put_contents($logFile, "Banco $dbDatabase criado com sucesso ✅\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "Erro ao criar banco: " . $conn->error . "\n", FILE_APPEND);
    }

    $conn->close();
} catch (Exception $e) {
    file_put_contents($logFile, "Exceção: " . $e->getMessage() . "\n", FILE_APPEND);
}

echo "Verifique o log em /logs/register_debug.log";
