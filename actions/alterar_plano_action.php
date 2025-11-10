<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/config/config.php'; // Assumindo que aqui estão suas credenciais MP
// Inclua a biblioteca do Mercado Pago SDK (necessária para interagir com a API)
// require_once '../vendor/autoload.php';

// 1. Configuração e Segurança
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php");
    exit();
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

$usuarioId = $_SESSION['usuario_logado']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/perfil.php");
    exit;
}

// Dados recebidos do formulário
$novoPlanoIdInterno = $_POST['novo_plano_id'] ?? null;

if (!$novoPlanoIdInterno) {
    $_SESSION['error_message'] = "ID do novo plano não fornecido.";
    header("Location: ../pages/perfil.php");
    exit;
}

// 2. Buscar dados da Assinatura Atual e do Novo Plano
// Busca a assinatura ativa do usuário (assumindo que há uma coluna usuario_id na tabela assinaturas)
$stmt = $conn->prepare("SELECT a.mp_preapproval_id, a.id 
                        FROM assinaturas a
                        WHERE a.usuario_id = ? AND a.status = 'ativa' LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$assinatura = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assinatura || empty($assinatura['mp_preapproval_id'])) {
    $_SESSION['error_message'] = "Nenhuma assinatura ativa encontrada para alteração.";
    header("Location: ../pages/perfil.php");
    exit;
}

// Busca o ID do plano do Mercado Pago a partir do ID interno
$stmt = $conn->prepare("SELECT mercadopago_plan_id FROM planos WHERE id = ?");
$stmt->bind_param("i", $novoPlanoIdInterno);
$stmt->execute();
$planoInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$planoInfo || empty($planoInfo['mercadopago_plan_id'])) {
    $_SESSION['error_message'] = "ID do plano Mercado Pago não encontrado para o plano selecionado.";
    header("Location: ../pages/perfil.php");
    exit;
}

$preapprovalId = $assinatura['mp_preapproval_id'];
$novoMpPlanId = $planoInfo['mercadopago_plan_id'];

// 3. Chamar a API do Mercado Pago para Alterar o Plano

// Inicialização do SDK do Mercado Pago
// Substitua pelas suas credenciais (Client ID ou Access Token)
// SDK::setAccessToken(MP_ACCESS_TOKEN);

// Dados para atualização (o plano_id é o único campo obrigatório para a migração)
$data = [
    'plan_id' => $novoMpPlanId,
];

// O Mercado Pago utiliza o método PUT para atualizar uma pre-aprovação (assinatura)
$ch = curl_init();
$url = "https://api.mercadopago.com/preapproval/{$preapprovalId}";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . MP_ACCESS_TOKEN, // MP_ACCESS_TOKEN deve estar definido em seu config.php
    'Content-Type: application/json',
));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$mpResponse = json_decode($response, true);

// 4. Tratar Resposta e Atualizar Banco de Dados Local
if ($httpCode >= 200 && $httpCode < 300) {
    // Sucesso na alteração na API do MP
    
    // Atualizar o plano interno na tabela de assinaturas local
    $stmtUpdate = $conn->prepare("UPDATE assinaturas SET plano = ?, valor = (SELECT valor FROM planos WHERE id = ?), data_migracao = NOW() WHERE id = ?");
    
    // O campo 'plano' na tabela 'assinaturas' no schema.sql é VARCHAR(50). 
    // Você precisará do nome do plano. Reexecutei a busca para obter o nome.
    $nomePlano = $conn->query("SELECT nome FROM planos WHERE id = " . $novoPlanoIdInterno)->fetch_assoc()['nome'];
    
    $stmtUpdate->bind_param("sii", $nomePlano, $novoPlanoIdInterno, $assinatura['id']);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    $_SESSION['success_message'] = "Seu plano de assinatura foi alterado com sucesso!";
} else {
    // Erro na API do MP
    $errorMessage = $mpResponse['message'] ?? "Erro desconhecido ao comunicar com Mercado Pago.";
    $_SESSION['error_message'] = "Falha ao alterar o plano: " . htmlspecialchars($errorMessage);
}

header("Location: ../pages/perfil.php");
exit;
?>