<?php
$logPath = __DIR__ . '/../logs/register_debug.log';
if (file_exists($logPath)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logPath)) . "</pre>";
} else {
    echo "Log não encontrado.";
}
?>
