
<?php
require_once '../vendor/autoload.php';
require_once '../includes/session_init.php';
require_once '../database.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Customer\CustomerClient;
use MercadoPago\Client\Preapproval\PreapprovalClient;
use MercadoPago\Exceptions\MPApiException;

// Configuração do SDK
MercadoPagoConfig::setAccessToken('APP_USR-724044855614997-090410-93f6ade3025cb335eedfc97998612d89-2411601376'); // ⚠️ substitua pelo seu token do Mercado Pago

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

// Obtém o e-mail do usuário logado
$userEmail = $_SESSION['usuario_logado']['email'] ?? null;
if (!$userEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'E-mail do usuário não encontrado.']);
    exit;
}

try {
    $customerClient = new CustomerClient();

    // ✅ Busca o cliente por e-mail
    // $existingCustomers = $customerClient->search(["email" => $userEmail]);

    // ✅ Compatibilidade entre versões antigas e novas do SDK
    $results = $existingCustomers->results ?? ($existingCustomers["results"] ?? []);

    if (!empty($results)) {
        $customer = is_array($results[0]) ? $results[0] : (array)$results[0];
    } else {
        // Cria o cliente se não existir
        $customer = $customerClient->create([
            "email" => $userEmail
        ]);
    }

    // Cria uma assinatura mensal
    $preapprovalClient = new PreapprovalClient();

    $preapproval = $preapprovalClient->create([
        "payer_email" => $userEmail,
        "auto_recurring" => [
            "frequency" => 1,
            "frequency_type" => "months",
            "transaction_amount" => 49.90,
            "currency_id" => "BRL",
            "start_date" => date('c'),
            "end_date" => date('c', strtotime('+1 year'))
        ],
        "back_url" => "http://localhost/app-controle-contas/sucesso.php",
        "reason" => "Assinatura mensal do App Controle de Contas"
    ]);

    // Salva o ID da assinatura no banco
    $pdo = getMasterConnection();
    $stmt = $pdo->prepare("UPDATE usuarios SET mp_subscription_id = ? WHERE email = ?");
    $stmt->execute([$preapproval->id, $userEmail]);

    echo json_encode([
        'success' => true,
        'message' => 'Assinatura criada com sucesso!',
        'init_point' => $preapproval->init_point ?? null
    ]);

} catch (MPApiException $e) {
    error_log("Erro API Mercado Pago: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar a assinatura.']);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno.']);
}
