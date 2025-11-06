<?php
// pages/webhook_mercadopago.php

header('Content-Type: application/json');

// Lê o corpo do POST (JSON enviado pelo Mercado Pago)
$input = file_get_contents('php://input');

// Salva em um log para depuração
file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " - " . $input . PHP_EOL, FILE_APPEND);

// Retorna sucesso
echo json_encode(['status' => 'ok', 'received' => json_decode($input, true)]);
