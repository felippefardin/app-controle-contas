<?php
// actions/processar_trial.php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;

// 1. Configura o Access Token (ideal: usar variável do .env)
// ✅ CORREÇÃO: Access Token atualizado.
MercadoPagoConfig::setAccessToken("APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376");

// 2. Verifica se há e-mail na sessão
if (!isset($_SESSION['registration_email'])) {
    header("Location: ../pages/registro.php?msg=sessao_expirada");
    exit;
}

$userEmail = $_SESSION['registration_email'];
$cardToken = $_POST['card_token'] ?? null;

// ⚙️ ID do plano (criado previamente no painel ou via API)
// ⚠️ IMPORTANTE: Substitua "SEU_PLAN_ID_VEM_AQUI" pelo ID real do seu plano no MercadoPago.
$planId = "384cff9ca9df422bb2378aa2009e8ed1"; // substitua pelo ID real

if ($cardToken === null) {
    // Redireciona de volta se o token do cartão não foi enviado
    header("Location: ../pages/assinar_trial.php?msg=token_invalido");
    exit;
}

try {
    $client = new PreApprovalClient();

    // 3. Cria a assinatura com período de teste (trial)
    $subscription = $client->create([
        "preapproval_plan_id" => $planId,
        "reason" => "Assinatura Plano Premium (30 dias grátis)",
        "external_reference" => uniqid("trial_", true),
        "payer_email" => $userEmail,
        "card_token_id" => $cardToken, // ✅ Adicionado o token do cartão
        "auto_recurring" => [
            "frequency" => 1,
            "frequency_type" => "months",
            "transaction_amount" => 49.90,
            "currency_id" => "BRL",
            "start_date" => date('c', strtotime("+30 days")), // começa depois do trial
            "end_date" => date('c', strtotime("+1 year")),
        ],
        "back_url" => "https://seusite.com/retorno_assinatura.php"
    ]);

    // 4. Atualiza o usuário no banco
    if (!empty($subscription->id)) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET mp_subscription_id = ?, status_assinatura = 'trial' 
            WHERE email = ?
        ");
        $stmt->execute([$subscription->id, $userEmail]);

        unset($_SESSION['registration_email']);
        unset($_SESSION['nome_usuario_temp']); // Limpa também o nome temporário
        header("Location: ../pages/login.php?msg=trial_sucesso");
        exit;
    } else {
        // Se a API não retornou um ID, mas não gerou exceção
        header("Location: ../pages/assinar_trial.php?msg=falha_assinatura_api");
        exit;
    }

} catch (Exception $e) {
    // Captura erros da API do MercadoPago ou do Banco de Dados
    error_log("Erro ao processar assinatura: " . $e->getMessage());
    header("Location: ../pages/assinar_trial.php?msg=erro_processamento");
    exit;
}