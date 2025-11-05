<?php
// actions/cancelar_assinatura.php

require_once '../includes/session_init.php';
require_once '../database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

// ✅ Configuração do SDK
MercadoPagoConfig::setAccessToken("APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376");

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: ../pages/login.php?msg=erro_sessao_expirada");
    exit;
}

try {
    // ✅ 1. Buscar ID da assinatura no banco
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT mp_subscription_id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['mp_subscription_id'])) {
        header("Location: ../pages/minha_assinatura.php?msg=erro_assinatura_nao_encontrada");
        exit;
    }

    $mp_subscription_id = $user['mp_subscription_id'];

    // ✅ 2. Cancelar a assinatura via API do Mercado Pago
    $client = new PreApprovalClient();
    $client->update($mp_subscription_id, [
        "status" => "cancelled"
    ]);

    // ✅ 3. Atualizar status no banco local
    $stmt = $pdo->prepare("UPDATE usuarios SET status_assinatura = 'cancelled' WHERE id = ?");
    $stmt->execute([$userId]);

    // ✅ 4. Redirecionar com sucesso
    header("Location: ../pages/minha_assinatura.php?msg=cancelamento_sucesso");
    exit;

} catch (Exception $e) {
    error_log("Erro ao cancelar assinatura MP: " . $e->getMessage());
    header("Location: ../pages/minha_assinatura.php?msg=erro_mp_cancelar");
    exit;
}
