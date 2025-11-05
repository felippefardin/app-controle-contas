<?php
// webhook_mp.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database.php';

// --- CORREÇÃO AQUI ---
use MercadoPago\Client\Subscription\SubscriptionClient;
use MercadoPago\MercadoPagoConfig;

// ✅ Configuração do SDK
MercadoPagoConfig::setAccessToken("APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376");

// ✅ 1. Ler o corpo da notificação (JSON recebido)
$data = json_decode(file_get_contents('php://input'), true);

// (Opcional) salvar log local para debug
// file_put_contents(__DIR__ . '/../logs/webhook_mp.log', date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n", FILE_APPEND);

// --- CORREÇÃO AQUI ---
// O tipo da notificação da nova API é 'subscription'
if (isset($data['type']) && $data['type'] === 'subscription') {
// --- FIM DA CORREÇÃO ---

    $subscriptionId = $data['data']['id'] ?? null;

    if ($subscriptionId) {
        try {
            // ✅ 2. Buscar dados da assinatura na API do Mercado Pago
            // --- CORREÇÃO AQUI ---
            $client = new SubscriptionClient(); // Cliente correto
            // --- FIM DA CORREÇÃO ---
            $subscription = $client->get($subscriptionId);

            if ($subscription && isset($subscription->status)) {
                $status = $subscription->status; // Ex: authorized, paused, cancelled
                $mp_subscription_id = $subscription->id;

                // ✅ 3. Mapear o status do Mercado Pago para o seu sistema
                // (Seu mapeamento parece bom)
                $novoStatusApp = match ($status) {
                    'authorized' => 'active',
                    'paused'     => 'paused',
                    'cancelled'  => 'cancelled',
                    default      => 'inactive', // Cobre 'pending', 'expired', etc.
                };

                // ✅ 4. Atualizar status da assinatura no banco de dados
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET status_assinatura = ? 
                    WHERE mp_subscription_id = ?
                ");
                $stmt->execute([$novoStatusApp, $mp_subscription_id]);
            }

        } catch (Exception $e) {
            error_log("Erro no webhook MP (Subscription): " . $e->getMessage());
        }
    }
}

// ✅ 5. Responder 200 OK para o Mercado Pago
http_response_code(200);
echo json_encode(["status" => "ok"]);
exit;