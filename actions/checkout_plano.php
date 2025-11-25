<?php
// actions/checkout_plano.php
session_start();
require_once '../database.php';

if (!isset($_SESSION['usuario_logado']) || !$_SESSION['tenant_id']) {
    header("Location: ../pages/login.php");
    exit;
}

$plano = $_POST['plano'] ?? '';
$tenant_id = $_SESSION['tenant_id'];

// Valida planos permitidos
$planos_validos = ['basico', 'plus', 'essencial'];
if (!in_array($plano, $planos_validos)) {
    header("Location: ../pages/assinar.php");
    exit;
}

$conn = getMasterConnection();

try {
    // ATUALIZAÇÃO DO PLANO
    // Em produção: Redirecionar para Gateway de Pagamento. 
    // Após callback (webhook), executar este update.
    
    // Atualiza plano_atual e define status como 'ativo' (reativando se estivesse bloqueado)
    // Reseta a data de renovação para daqui a 30 dias
    $data_renovacao = date('Y-m-d', strtotime('+30 days'));
    
    $stmt = $conn->prepare("UPDATE tenants SET plano_atual = ?, status_assinatura = 'ativo' WHERE tenant_id = ?");
    $stmt->bind_param("ss", $plano, $tenant_id);
    
    if ($stmt->execute()) {
        $_SESSION['sucesso_pagamento'] = "Plano atualizado para " . ucfirst($plano) . " com sucesso!";
        header("Location: ../pages/minha_assinatura.php");
    } else {
        throw new Exception("Erro ao atualizar plano.");
    }
    $stmt->close();

} catch (Exception $e) {
    // Log erro
    header("Location: ../pages/assinar.php?erro=1");
}

$conn->close();
exit;
?>