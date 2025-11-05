<?php
// --- 1. INCLUDES E CONFIGURAÇÕES ---

// --- CORREÇÃO AQUI ---
// Importa as classes necessárias do SDK (ESSENCIAL!)
use MercadoPago\Client\Subscription\SubscriptionClient;
use MercadoPago\MercadoPagoConfig;
// --- FIM DA CORREÇÃO ---

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
    $client = new PreApprovalClient(); // Esta linha agora vai funcionar
    
    // 4. Cria a assinatura enviando a requisição
    $subscription = $client->create($request);

    // 5. Verifica a resposta
    if (isset($subscription->id) && ($subscription->status == 'authorized' || $subscription->status == 'pending')) {
        
        // --- 6. ATUALIZA O SEU BANCO DE DADOS ---
        $pdo = $db->getConnection(); 
        
        $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ativo', 
                                                  mp_subscription_id = :sub_id, 
                                                  data_assinatura = NOW() 
                                              WHERE id = :user_id");
        
        $stmt->execute([
            ':sub_id' => $subscription->id,
            ':user_id' => $user_id
        ]);

        // Redireciona para uma página de sucesso
        header('Location: ../pages/perfil.php?status=success&msg=assinatura_criada');
        exit;
        
    } else {
        // O pagamento foi recusado ou falhou
        error_log("Pagamento recusado (user_id: $user_id). Status: " . ($subscription->status ?? 'DESCONHECIDO'));
        header('Location: ../pages/assinar.php?status=error&msg=pagamento_recusado');
        exit;
    }

} catch (\MercadoPago\Exceptions\MPApiException $e) {
    // Captura erros específicos da API do Mercado Pago
    error_log("Erro API Mercado Pago (user_id: $user_id): " . $e->getMessage());
    header('Location: ../pages/assinar.php?status=error&msg=erro_mp_api');
    exit;
} catch (Exception $e) {
    // Captura outros erros gerais
    error_log("Erro Geral (user_id: $user_id): " . $e->getMessage());
    header('Location: ../pages/assinar.php?status=error&msg=erro_geral');
    exit;
}