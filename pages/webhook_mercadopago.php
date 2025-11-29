<?php
// pages/webhook_mercadopago.php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Garante Dotenv

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 🔹 SELEÇÃO DE AMBIENTE
$modo = $_ENV['MERCADOPAGO_MODE'] ?? 'sandbox';
$mp_access_token = ($modo === 'producao') ? $_ENV['MP_ACCESS_TOKEN_PRODUCAO'] : $_ENV['MP_ACCESS_TOKEN_SANDBOX'];

$input = file_get_contents("php://input");
// Log para debug
file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", date('Y-m-d H:i:s') . " - RECEBIDO: " . $input . PHP_EOL, FILE_APPEND);

$data = json_decode($input, true);

if (!$data || !isset($data['type'])) {
    http_response_code(200); // Responde 200 para o MP não ficar reenviando lixo
    exit;
}

$conn = getMasterConnection();

if ($data['type'] === 'subscription_preapproval' || $data['type'] === 'updated') {
    $subscriptionId = $data['data']['id'] ?? $data['id'] ?? null;
    
    if ($subscriptionId) {
        $url = "https://api.mercadopago.com/preapproval/" . $subscriptionId;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $mp_access_token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $subData = json_decode($response, true);
            $statusMp = $subData['status']; 
            $tenant_ref = $subData['external_reference'];

            $statusSistema = 'inativo';
            if ($statusMp === 'authorized') $statusSistema = 'ativo';
            elseif ($statusMp === 'pending') $statusSistema = 'pendente';

            // ATUALIZA NO BANCO
            $stmt = $conn->prepare("UPDATE tenants SET status_assinatura = ?, id_assinatura_mp = ? WHERE tenant_id = ?");
            $stmt->bind_param("sss", $statusSistema, $subscriptionId, $tenant_ref);
            $stmt->execute();
            
            file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", "SUCESSO: Tenant $tenant_ref -> $statusSistema" . PHP_EOL, FILE_APPEND);
        }
    }
}

http_response_code(200);
?>