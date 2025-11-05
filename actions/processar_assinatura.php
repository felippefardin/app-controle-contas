<?php
// --- 1. INCLUDES E CONFIGURAÇÕES ---

// Importa as classes necessárias do SDK (ESSENCIAL!)
use MercadoPago\Client\Subscription\SubscriptionClient;
use MercadoPago\MercadoPagoConfig;

// Garanta que os caminhos para seus arquivos de inicialização estejam corretos
require_once('../includes/session_init.php');
require_once('../includes/config/config.php');
require_once('../database.php'); 
require_once('../vendor/autoload.php'); // Essencial para carregar o SDK

// --- 2. VERIFICAÇÃO DE DADOS ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido.");
}

$token = $_POST['card_token'] ?? null;
$email = $_POST['payer_email'] ?? null;
$user_id = $_POST['user_id'] ?? null; 

if (!$token || !$email || !$user_id || $user_id == 0) {
    header('Location: ../pages/assinar.php?status=error&msg=dados_invalidos');
    exit;
}

// --- 3. CONFIGURAÇÃO DO MERCADO PAGO (SDK V3) ---

// ** SEU ACCESS TOKEN DE TESTE (SECRETO) JÁ ESTÁ AQUI **
$accessToken = 'APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376'; 

// ** IMPORTANTE: COLOQUE O ID DO PLANO QUE VOCÊ CRIOU NO DASHBOARD **
// (Este ID deve ter sido criado em "Planos e Assinaturas" no dashboard do MP)
$plan_id = 'SEU_ID_DO_PLANO_DE_TESTE_VEM_AQUI'; // EX: 2c9380848f...

if ($plan_id == 'SEU_ID_DO_PLANO_DE_TESTE_VEM_AQUI') {
     // Erro para o desenvolvedor: plano não configurado
     header('Location: ../pages/assinar.php?status=error&msg=plan_id_faltando');
     exit;
}


try {
    // 1. Configura o Access Token (Nova forma)
    MercadoPagoConfig::setAccessToken($accessToken);

    // 2. Cria o corpo (payload) da requisição
    $request = [
        "preapproval_plan_id" => $plan_id,
        "reason" => "Assinatura Plano Premium - Controle de Contas",
        "payer" => [
            "email" => $email
        ],
        "card_token_id" => $token,
        "status" => "authorized" // Tenta autorizar a assinatura imediatamente
    ];

    // 3. Cria o cliente de Assinatura
    // $client = new SubscriptionClient(); 
    
    // 4. Cria a assinatura enviando a requisição
    $subscription = $client->create($request);

    // 5. Verifica a resposta
    if (isset($subscription->id) && ($subscription->status == 'authorized' || $subscription->status == 'pending')) {
        
        // --- 6. ATUALIZA O SEU BANCO DE DADOS ---
        
        // Mapeia o status para ser consistente com o webhook
        $statusApp = ($subscription->status == 'authorized') ? 'active' : 'pending';
        
        $pdo = $db->getConnection(); 
        
        // Atualiza a coluna 'status_assinatura' para consistência
        $stmt = $pdo->prepare("UPDATE usuarios SET status_assinatura = :status, 
                                                  mp_subscription_id = :sub_id, 
                                                  data_assinatura = NOW() 
                                              WHERE id = :user_id");
        
        $stmt->execute([
            ':status' => $statusApp, // Status mapeado
            ':sub_id' => $subscription->id,
            ':user_id' => $user_id
        ]);

        // Redireciona para uma página de sucesso
        header('Location: ../pages/perfil.php?status=success&msg=assinatura_criada');
        exit;
        
    } else {
        // O pagamento foi recusado ou falhou
        $statusResposta = $subscription->status ?? 'DESCONHECIDO';
        $errorMessage = $subscription->error_message ?? 'Pagamento recusado pela operadora.';
        
        error_log("Pagamento recusado (user_id: $user_id). Status: $statusResposta. Msg: $errorMessage");
        header('Location: ../pages/assinar.php?status=error&msg=pagamento_recusado');
        exit;
    }

} catch (\MercadoPago\Exceptions\MPApiException $e) {
    // Captura erros específicos da API do Mercado Pago
    $errorMessage = $e->getApiResponse()->getContent();
    error_log("Erro API Mercado Pago (user_id: $user_id): " . $e->getMessage() . " | Response: " . json_encode($errorMessage));
    header('Location: ../pages/assinar.php?status=error&msg=erro_mp_api');
    exit;
} catch (Exception $e) {
    // Captura outros erros gerais
    error_log("Erro Geral (user_id: $user_id): " . $e->getMessage());
    header('Location: ../pages/assinar.php?status=error&msg=erro_geral');
    exit;
}
?>