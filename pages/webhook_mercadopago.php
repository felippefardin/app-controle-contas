<?php
// pages/webhook_mercadopago.php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 🔹 CONFIGURAÇÃO
$modo = $_ENV['MERCADOPAGO_MODE'] ?? 'sandbox';
$mp_access_token = ($modo === 'producao') ? $_ENV['MP_ACCESS_TOKEN_PRODUCAO'] : $_ENV['MP_ACCESS_TOKEN_SANDBOX'];

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Log para debug
$log_entry = date('Y-m-d H:i:s') . " - TIPO: " . ($data['type'] ?? 'desconhecido') . " - ID: " . ($data['data']['id'] ?? 'n/a') . PHP_EOL;
file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", $log_entry, FILE_APPEND);

if (!$data || !isset($data['type'])) {
    http_response_code(200); 
    exit;
}

$conn = getMasterConnection();

// ==============================================================================
// CASO 1: ATUALIZAÇÃO DE ASSINATURA (Ativa/Suspende o Tenant)
// ==============================================================================
if ($data['type'] === 'subscription_preapproval' || $data['type'] === 'updated') {
    $subscriptionId = $data['data']['id'] ?? $data['id'] ?? null;
    
    if ($subscriptionId) {
        $url = "https://api.mercadopago.com/preapproval/" . $subscriptionId;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $mp_access_token]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $subData = json_decode($response, true);
            $statusMp = $subData['status']; 
            $tenant_ref = $subData['external_reference']; // Tenant ID

            $statusSistema = 'inativo';
            if ($statusMp === 'authorized') $statusSistema = 'ativo';
            elseif ($statusMp === 'pending') $statusSistema = 'pendente';
            elseif ($statusMp === 'cancelled') $statusSistema = 'cancelado';

            // Atualiza status do Tenant
            $stmt = $conn->prepare("UPDATE tenants SET status_assinatura = ?, id_assinatura_mp = ? WHERE tenant_id = ?");
            $stmt->bind_param("sss", $statusSistema, $subscriptionId, $tenant_ref);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// ==============================================================================
// CASO 2: PAGAMENTO RECEBIDO (Gera o Financeiro no Dashboard)
// ==============================================================================
if ($data['type'] === 'payment') {
    $paymentId = $data['data']['id'] ?? $data['id'] ?? null;

    if ($paymentId) {
        $url = "https://api.mercadopago.com/v1/payments/" . $paymentId;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $mp_access_token]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $payData = json_decode($response, true);
            
            // Dados para inserir no banco
            $tenant_id = $payData['external_reference']; // Tenant ID enviado no checkout
            $valor = $payData['transaction_amount'];
            $status_mp = $payData['status']; // approved, pending, rejected
            $forma_pagamento = $payData['payment_type_id']; // credit_card, ticket, etc
            $data_pagamento = date('Y-m-d', strtotime($payData['date_approved'] ?? 'now'));
            $data_vencimento = date('Y-m-d', strtotime($payData['date_created'])); // Data que gerou a cobrança

            // Mapear status do MP para o Banco
            $status_db = 'pendente';
            if ($status_mp === 'approved') {
                $status_db = 'pago';
            } elseif ($status_mp === 'cancelled' || $status_mp === 'rejected') {
                $status_db = 'cancelado';
            }

            // Verifica se essa transação já existe para não duplicar
            $check = $conn->prepare("SELECT id FROM faturas_assinatura WHERE transacao_id = ?");
            $check->bind_param("s", $paymentId);
            $check->execute();
            $check->store_result();

            if ($check->num_rows == 0) {
                // INSERE NA TABELA QUE ALIMENTA O DASHBOARD
                $ins = $conn->prepare("
                    INSERT INTO faturas_assinatura 
                    (tenant_id, valor, data_vencimento, data_pagamento, status, forma_pagamento, transacao_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                // Se não estiver pago, data_pagamento pode ser null, mas aqui simplificamos
                $ins->bind_param("sdsssss", 
                    $tenant_id, 
                    $valor, 
                    $data_vencimento, 
                    $data_pagamento, 
                    $status_db, 
                    $forma_pagamento, 
                    $paymentId
                );
                
                if ($ins->execute()) {
                    file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", "SUCESSO FINANCEIRO: Pagamento $paymentId gravado para tenant $tenant_id" . PHP_EOL, FILE_APPEND);
                } else {
                    file_put_contents(__DIR__ . "/../logs/webhook_assinatura.log", "ERRO SQL: " . $conn->error . PHP_EOL, FILE_APPEND);
                }
                $ins->close();
            }
            $check->close();
        }
    }
}

$conn->close();
http_response_code(200);
?>