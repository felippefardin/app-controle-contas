<?php
// actions/atualizar_cartao.php

require_once '../includes/session_init.php';
require_once '../database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

// ✅ Configurar o SDK com o token de acesso
MercadoPagoConfig::setAccessToken("APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376");

// ✅ 1. Validar entrada
if (!isset($_POST['card_token']) || empty($_POST['card_token'])) {
    header("Location: ../pages/atualizar_cartao.php?msg=erro_token_vazio");
    exit;
}

$cardToken = $_POST['card_token'];
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: ../pages/login.php?msg=erro_sessao_expirada");
    exit;
}

try {
    // ✅ 2. Buscar assinatura no banco
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT mp_subscription_id, status_assinatura FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['mp_subscription_id'])) {
        header("Location: ../pages/minha_assinatura.php?msg=erro_assinatura_nao_encontrada");
        exit;
    }

    $mp_subscription_id = $user['mp_subscription_id'];

    // ✅ 3. Atualizar o cartão via API do Mercado Pago
    $client = new PreApprovalClient();
    $client->update($mp_subscription_id, [
        "card_token_id" => $cardToken
    ]);

    // ✅ 4. Atualizar status local se estava pausada
    if ($user['status_assinatura'] === 'paused') {
        $stmt = $pdo->prepare("UPDATE usuarios SET status_assinatura = 'active' WHERE id = ?");
        $stmt->execute([$userId]);
    }

    // ✅ 5. Redirecionar de volta com sucesso
    header("Location: ../pages/minha_assinatura.php?msg=cartao_atualizado");
    exit;

} catch (Exception $e) {
    error_log("Erro ao atualizar cartão MP: " . $e->getMessage());
    header("Location: ../pages/atualizar_cartao.php?msg=erro_mp_atualizar");
    exit;
}
