<?php
// webhook_assinatura.php

require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../database.php';

// 🔹 Captura o JSON enviado pelo Mercado Pago
$input = file_get_contents("php://input");
file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", date('Y-m-d H:i:s') . " - RECEBIDO: " . $input . PHP_EOL, FILE_APPEND);

// 🔹 Decodifica JSON
$data = json_decode($input, true);

if (!$data || !isset($data['type'])) {
    file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", date('Y-m-d H:i:s') . " - ERRO: JSON inválido" . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

// 🔹 Conexão com banco
$conn = getMasterConnection();

// 🔹 Processa eventos
switch ($data['type']) {

    case 'preapproval': // Evento de assinatura (recorrente)
    case 'subscription.preapproval.updated': // Dependendo do plano/SDK
        $subscriptionId = $data['data']['id'] ?? null;
        $statusMp = strtolower($data['data']['status'] ?? 'pendente'); // ex: authorized, cancelled

        if ($subscriptionId) {
            // Mapeamento de status Mercado Pago para status do sistema
            switch ($statusMp) {
                case 'authorized':
                case 'active':
                    $statusSistema = 'ativa';
                    break;
                case 'cancelled':
                case 'paused':
                    $statusSistema = 'cancelada';
                    break;
                case 'pending':
                    $statusSistema = 'pendente';
                    break;
                default:
                    $statusSistema = 'pendente';
            }

            // Atualiza assinatura no banco
            $stmt = $conn->prepare("UPDATE assinaturas SET status = ? WHERE mp_preapproval_id = ?");
            $stmt->bind_param("ss", $statusSistema, $subscriptionId);
            $stmt->execute();

            file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", date('Y-m-d H:i:s') . " - ATUALIZADO: assinatura $subscriptionId para status $statusSistema" . PHP_EOL, FILE_APPEND);
        }
        break;

    default:
        // Evento desconhecido
        file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", date('Y-m-d H:i:s') . " - IGNORADO: tipo de evento " . $data['type'] . PHP_EOL, FILE_APPEND);
        break;
}

// 🔹 Retorna HTTP 200 para confirmar recebimento
http_response_code(200);
