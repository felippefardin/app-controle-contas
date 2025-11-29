<?php
// actions/comprar_extra_action.php
session_start();
require_once '../database.php';
require_once '../includes/utils.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// ---------------------------------------------------------
// 🔹 SELEÇÃO DE AMBIENTE
// ---------------------------------------------------------
$modo = $_ENV['MERCADOPAGO_MODE'] ?? 'sandbox';
$mp_access_token = ($modo === 'producao') ? $_ENV['MP_ACCESS_TOKEN_PRODUCAO'] : $_ENV['MP_ACCESS_TOKEN_SANDBOX'];
// ---------------------------------------------------------

if (!isset($_SESSION['usuario_logado']) || !$_SESSION['tenant_id']) {
    header("Location: ../pages/login.php");
    exit;
}

$conn = getMasterConnection();
$tenant_id = $_SESSION['tenant_id'];
$qtd_extra_adicionar = (int)($_POST['qtd_extra'] ?? 1);
$preco_por_usuario = 4.00;

if ($qtd_extra_adicionar < 1) $qtd_extra_adicionar = 1;

try {
    $stmt = $conn->prepare("SELECT id_assinatura_mp, status_assinatura, plano_atual, usuarios_extras FROM tenants WHERE tenant_id = ?");
    $stmt->bind_param("s", $tenant_id);
    $stmt->execute();
    $tenant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tenant) throw new Exception("Tenant não encontrado.");

    $extras_atuais = (int)($tenant['usuarios_extras'] ?? 0);
    $novo_total_extras = $extras_atuais + $qtd_extra_adicionar;

    // --- CENÁRIO 1: TRIAL ---
    if ($tenant['status_assinatura'] === 'trial') {
        $stmtUp = $conn->prepare("UPDATE tenants SET usuarios_extras = ? WHERE tenant_id = ?");
        $stmtUp->bind_param("is", $novo_total_extras, $tenant_id);
        $stmtUp->execute();
        $stmtUp->close();
        set_flash_message('success', "Adicionado $qtd_extra_adicionar usuário(s) ao teste grátis.");
    } 
    // --- CENÁRIO 2: ASSINATURA PAGA ---
    else {
        if (empty($tenant['id_assinatura_mp'])) {
            $_SESSION['erro_assinatura'] = "Erro: Assinatura não localizada no sistema. Por favor, reative seu plano.";
            header("Location: ../pages/assinar.php?plano_selecionado=" . ($tenant['plano_atual'] ?? 'basico'));
            exit;
        }

        $precos_base = ['basico' => 19.00, 'plus' => 39.00, 'essencial' => 59.00];
        $plano_atual = $tenant['plano_atual'] ?? 'basico';
        $valor_base = $precos_base[$plano_atual] ?? 19.00;
        
        $novo_valor_total = $valor_base + ($novo_total_extras * $preco_por_usuario);

        // Atualiza MP
        $url = "https://api.mercadopago.com/preapproval/" . $tenant['id_assinatura_mp'];
        $data = ["auto_recurring" => ["transaction_amount" => (float)$novo_valor_total]];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $mp_access_token // Token correto
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 || $httpCode == 201) {
            $stmtUp = $conn->prepare("UPDATE tenants SET usuarios_extras = ? WHERE tenant_id = ?");
            $stmtUp->bind_param("is", $novo_total_extras, $tenant_id);
            $stmtUp->execute();
            $stmtUp->close();
            set_flash_message('success', "Sucesso! Valor atualizado para R$ " . number_format($novo_valor_total, 2, ',', '.'));
        } else {
            // Log do erro para você ver no servidor
            error_log("Erro MP Update ({$tenant['id_assinatura_mp']}): " . $response);
            throw new Exception("O Mercado Pago recusou a alteração. Status: $httpCode");
        }
    }

} catch (Exception $e) {
    set_flash_message('danger', "Erro: " . $e->getMessage());
}

$conn->close();
header("Location: ../pages/minha_assinatura.php");
exit;
?>