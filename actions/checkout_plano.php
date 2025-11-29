<?php
// actions/checkout_plano.php
session_start();
require_once '../database.php';
require_once '../includes/utils.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// ---------------------------------------------------------
// 🔹 LÓGICA DE SELEÇÃO DE AMBIENTE (FIX PARA SEU .ENV)
// ---------------------------------------------------------
$modo = $_ENV['MERCADOPAGO_MODE'] ?? 'sandbox';
$mp_access_token = ($modo === 'producao') ? $_ENV['MP_ACCESS_TOKEN_PRODUCAO'] : $_ENV['MP_ACCESS_TOKEN_SANDBOX'];
$mp_back_url     = ($modo === 'producao') ? $_ENV['MP_BACK_URL_PRODUCAO']     : $_ENV['MP_BACK_URL_SANDBOX'];

if (empty($mp_access_token)) {
    set_flash_message('danger', "Erro de Configuração: Token do Mercado Pago não encontrado no .env");
    header("Location: ../pages/assinar.php");
    exit;
}
// ---------------------------------------------------------

if (!isset($_SESSION['usuario_logado']) || !$_SESSION['tenant_id']) {
    header("Location: ../pages/login.php");
    exit;
}

$plano = $_POST['plano'] ?? 'basico';
$email_usuario = $_SESSION['email'];
$tenant_id = $_SESSION['tenant_id'];

// Valores dos planos
$valor_plano = match($plano) {
    'plus' => 39.00,
    'essencial' => 59.00,
    default => 19.00
};

// URL da API
$url = "https://api.mercadopago.com/preapproval";

$data = [
    "payer_email" => $email_usuario,
    "back_url" => $mp_back_url, // Usa a URL do .env
    "reason" => "Assinatura " . ucfirst($plano) . " - App Controle",
    "external_reference" => $tenant_id,
    "auto_recurring" => [
        "frequency" => 1,
        "frequency_type" => "months",
        "transaction_amount" => $valor_plano,
        "currency_id" => "BRL"
    ],
    "status" => "pending"
];

// Chamada cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $mp_access_token // Usa o token selecionado
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$mp_response = json_decode($response, true);

// Verifica sucesso
if (isset($mp_response['init_point'])) {
    // IMPORTANTE: Atualizamos o plano no banco ANTES de enviar para garantir
    // (O status fica 'pendente' até o webhook confirmar)
    $conn = getMasterConnection();
    $stmt = $conn->prepare("UPDATE tenants SET plano_atual = ? WHERE tenant_id = ?");
    $stmt->bind_param("ss", $plano, $tenant_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: " . $mp_response['init_point']);
    exit;
} else {
    // Tratamento de erro detalhado
    $msg_erro = "Erro Mercado Pago: " . ($mp_response['message'] ?? 'Resposta desconhecida');
    
    if(isset($mp_response['error']) && $mp_response['error'] == 'bad_request') {
        $msg_erro = "Erro: Verifique se o e-mail ($email_usuario) é válido ou se você não está tentando pagar com a mesma conta que criou a aplicação no MP (erro comum em Sandbox).";
    }

    error_log("Falha Checkout MP: " . $response);
    set_flash_message('danger', $msg_erro);
    header("Location: ../pages/assinar.php");
    exit;
}
?>