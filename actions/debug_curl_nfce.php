<?php
// actions/debug_curl_nfce.php

// 1. Inicie a sessão para pegar o cookie atual (você deve rodar isso no navegador JÁ LOGADO)
session_start();

if (!isset($_SESSION['usuario_logado']['id'])) {
    die("❌ Faça login no sistema primeiro, depois acesse este arquivo.");
}

// 2. ID da venda que você quer testar (verifique um ID existente no seu banco)
$id_venda_teste = 123; // <--- TROQUE PELO ID DE UMA VENDA EXISTENTE

// 3. Preparar chamada cURL para o próprio localhost
$url = "http://localhost/felippefardin/app-controle-contas/app-controle-contas-aed15d46fd4ab59fc1daedf45e49c92b8524b289/actions/emitir_nfce.php"; // Ajuste o caminho conforme sua URL local

$ch = curl_init($url);

// Configurar POST
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['id_venda' => $id_venda_teste]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Importante: Passar o Cookie da Sessão atual para o cURL
// Isso faz o emitir_nfce.php achar que é o usuário logado
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

// Executar
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo 'Erro cURL: ' . curl_error($ch);
} else {
    echo "<h1>Status HTTP: $httpCode</h1>";
    echo "<h2>Resposta do Servidor:</h2>";
    echo "<pre>";
    // Tenta formatar o JSON para leitura
    $json = json_decode($response, true);
    if ($json) {
        print_r($json);
    } else {
        echo $response; // Mostra erro bruto se não for JSON
    }
    echo "</pre>";
}

curl_close($ch);
?>