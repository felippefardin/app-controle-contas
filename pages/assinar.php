<?php
// actions/cancelar_assinatura.php

require_once '../includes/session_init.php';
require_once '../database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\Client\Subscription\SubscriptionClient; // Cliente correto
use MercadoPago\MercadoPagoConfig;

// ✅ Configuração do SDK
MercadoPagoConfig::setAccessToken("APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376");

// --- CORREÇÃO DE SESSÃO ---
// Seus outros scripts usam 'usuario_logado', então padronizei aqui.
$userId = $_SESSION['usuario_logado']['id'] ?? null;
// --- FIM DA CORREÇÃO ---

if (!$userId) {
    header("Location: ../pages/login.php?msg=erro_sessao_expirada");
    exit;
}

try {
    // ✅ 1. Buscar ID da assinatura no banco
    $pdo = getDbConnection(); // Usando sua função de conexão
    $stmt = $pdo->prepare("SELECT mp_subscription_id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['mp_subscription_id'])) {
        header("Location: ../pages/minha_assinatura.php?msg=erro_assinatura_nao_encontrada");
        exit;
    }

    $mp_subscription_id = $user['mp_subscription_id'];

    // ✅ 2. Cancelar a assinatura via API do Mercado Pago
    // --- CORREÇÃO DA LINHA COMENTADA ---
    $client = new SubscriptionClient(); // ESTA LINHA FOI DESCOMENTADA
    
    // O método 'update' é usado para alterar o status da assinatura (inclusive cancelar)
    $subscription = $client->update($mp_subscription_id, [
        "status" => "cancelled"
    ]);
    // --- FIM DA CORREÇÃO ---
    
    // Verifica se o cancelamento foi bem-sucedido (status retornado pela API)
    if (isset($subscription->status) && $subscription->status === 'cancelled') {

        // ✅ 3. Atualizar status no banco local
        $stmt = $pdo->prepare("UPDATE usuarios SET status_assinatura = 'cancelled' WHERE id = ?");
        $stmt->execute([$userId]);

        // ✅ 4. Redirecionar com sucesso
        header("Location: ../pages/minha_assinatura.php?msg=cancelamento_sucesso");
        exit;
    } else {
         throw new Exception("A API do Mercado Pago não confirmou o cancelamento. Status: " . ($subscription->status ?? 'desconhecido'));
    }

} catch (Exception $e) {
    error_log("Erro ao cancelar assinatura MP (Subscription): " . $e->getMessage());
    header("Location: ../pages/minha_assinatura.php?msg=erro_mp_cancelar");
    exit;
}
?>

