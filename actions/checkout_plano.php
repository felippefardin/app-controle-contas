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
$usuario_atual_id = $_SESSION['user_id'];

$novo_cupom = isset($_POST['cupom']) ? strtoupper(trim($_POST['cupom'])) : '';
$ind_email  = filter_input(INPUT_POST, 'ind_email', FILTER_SANITIZE_EMAIL);
$ind_doc    = preg_replace('/[^0-9]/', '', $_POST['ind_doc'] ?? '');

$planos_validos = ['basico', 'plus', 'essencial'];
if (!in_array($plano, $planos_validos)) {
    header("Location: ../pages/assinar.php");
    exit;
}

$conn = getMasterConnection();

try {
    $conn->begin_transaction();

    $data_renovacao = date('Y-m-d', strtotime('+30 days'));
    
    $stmt = $conn->prepare("UPDATE tenants SET plano_atual = ?, status_assinatura = 'ativo', data_renovacao = ? WHERE tenant_id = ?");
    $stmt->bind_param("sss", $plano, $data_renovacao, $tenant_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar plano.");
    }
    $stmt->close();

    if (!empty($novo_cupom)) {
        $stmtCupom = $conn->prepare("UPDATE tenants SET cupom_registro = ?, msg_cupom_visto = 0 WHERE tenant_id = ?");
        $stmtCupom->bind_param("ss", $novo_cupom, $tenant_id);
        $stmtCupom->execute();
        $stmtCupom->close();
    }

    if (!empty($ind_email) && !empty($ind_doc)) {
        $sqlInd = "SELECT id, tenant_id FROM usuarios WHERE email = ? AND documento_clean = ? AND id != ? LIMIT 1";
        $stmtInd = $conn->prepare($sqlInd);
        $stmtInd->bind_param("ssi", $ind_email, $ind_doc, $usuario_atual_id);
        $stmtInd->execute();
        $resInd = $stmtInd->get_result();
        
        if ($resInd->num_rows > 0) {
            $indicador = $resInd->fetch_assoc();
            $id_indicador = $indicador['id'];
            $tenant_indicador = $indicador['tenant_id'];

            $sqlCheck = "SELECT id FROM indicacoes WHERE id_indicado = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $usuario_atual_id);
            $stmtCheck->execute();
            
            if ($stmtCheck->get_result()->num_rows == 0) {
                $stmtInsert = $conn->prepare("INSERT INTO indicacoes (id_indicador, id_indicado) VALUES (?, ?)");
                $stmtInsert->bind_param("ii", $id_indicador, $usuario_atual_id);
                $stmtInsert->execute();
                $stmtInsert->close();

                $conn->query("UPDATE tenants SET msg_indicacao_visto = 0 WHERE tenant_id = '$tenant_id'");

                $sqlCount = "SELECT COUNT(*) as total FROM indicacoes WHERE id_indicador = ?";
                $stmtCount = $conn->prepare($sqlCount);
                $stmtCount->bind_param("i", $id_indicador);
                $stmtCount->execute();
                $total = $stmtCount->get_result()->fetch_assoc()['total'];
                $stmtCount->close();

                if ($total > 0 && $total % 3 == 0) {
                    $conn->query("UPDATE tenants SET data_renovacao = DATE_ADD(IF(data_renovacao > CURDATE(), data_renovacao, CURDATE()), INTERVAL 30 DAY) WHERE tenant_id = '$tenant_indicador'");
                }
            }
            $stmtCheck->close();
        }
        $stmtInd->close();
    }

    $conn->commit();
    $_SESSION['sucesso_pagamento'] = "Plano " . ucfirst($plano) . " ativado com sucesso!";
    
    // REDIRECIONAMENTO CORRIGIDO PARA O DASHBOARD
    header("Location: ../pages/minha_assinatura.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['erro_assinatura'] = "Erro ao processar: " . $e->getMessage();
    header("Location: ../pages/assinar.php");
    exit;
}

$conn->close();
?>