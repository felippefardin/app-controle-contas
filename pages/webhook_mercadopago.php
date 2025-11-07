<?php
$input = file_get_contents("php://input");
file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", date('Y-m-d H:i:s') . " - " . $input . PHP_EOL, FILE_APPEND);
http_response_code(200);
