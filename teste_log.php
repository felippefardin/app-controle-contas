<?php
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/register_debug.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Teste manual OK\n", FILE_APPEND);
echo "Arquivo de log criado? Verifique em /logs/register_debug.log";
?>
