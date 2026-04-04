<?php
// actions/gerar_insights_ia.php
require_once '../database.php';
require_once '../includes/session_init.php';

$tenant_id = $_SESSION['tenant_id'];
$conn = getTenantConnection();

// Buscar gastos dos últimos 30 dias
$sql = "SELECT descricao, valor, categoria FROM contas_pagar 
        WHERE tenant_id = ? AND status = 'baixada' 
        AND data_pagamento >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tenant_id);
$stmt->execute();
$gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$dados_texto = json_encode($gastos);
$apiKey = "AIzaSyCPVntnZUQuferc7P4gM-kCd-kVMkif2Ns"; // Sua chave inserida aqui
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$prompt = "Com base nestes dados JSON de gastos de uma empresa: $dados_texto. Forneça um resumo curto em HTML (use <ul> e <li>) com 3 dicas práticas de economia e aponte se algum gasto parece anormal.";

$payload = ["contents" => [["parts" => [["text" => $prompt]]]]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$result = json_decode($response, true);

echo $result['candidates'][0]['content']['parts'][0]['text'] ?? "Dica: Mantenha suas contas em dia para evitar juros!";