<?php
session_start();
require_once '../includes/config/config.php';
require_once 'enviar_email.php'; 

if (!isset($_SESSION['user_id']) || !isset($_POST['opcao_cancelamento'])) {
    header('Location: ../pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$opcao = $_POST['opcao_cancelamento']; // 'desativar' ou 'excluir'

$conn = getMasterConnection();

// Atualiza a intenção de cancelamento no banco
$sql = "UPDATE usuarios SET tipo_cancelamento = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $opcao, $user_id);
$stmt->execute();
$stmt->close();

// Busca dados do usuário para o email
$sqlUser = "SELECT nome, email, data_validade FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();
$stmtUser->close();

$data_vencimento = date('d/m/Y', strtotime($user['data_validade']));

if ($opcao == 'excluir') {
    $assunto = "Aviso de Exclusão de Conta Agendada";
    $mensagem = "Olá " . $user['nome'] . ",<br><br>";
    $mensagem .= "Recebemos sua solicitação de cancelamento com exclusão de dados.<br>";
    $mensagem .= "Você terá acesso ao sistema até: <strong>$data_vencimento</strong>.<br><br>";
    $mensagem .= "<strong style='color:red;'>IMPORTANTE:</strong> Salve seus dados antes desta data.<br>";
    
    enviarEmail($user['email'], $user['nome'], $assunto, $mensagem);
} else {
    $assunto = "Cancelamento de Renovação Automática";
    $mensagem = "Olá " . $user['nome'] . ",<br><br>";
    $mensagem .= "Sua solicitação para não renovar a assinatura foi recebida.<br>";
    $mensagem .= "Sua conta ficará ativa até $data_vencimento.<br>";
    
    enviarEmail($user['email'], $user['nome'], $assunto, $mensagem);
}

$conn->close();

$_SESSION['sucesso'] = "Solicitação de cancelamento registrada com sucesso.";
header('Location: ../pages/minha_assinatura.php');
exit;
?>