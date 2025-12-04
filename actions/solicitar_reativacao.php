<?php
session_start();
require_once '../includes/config/config.php';
require_once '../includes/utils.php'; // Adicionado: Importar utils para usar set_flash_message
require_once 'enviar_email.php'; 

$email = $_POST['email_reativacao'] ?? '';

if (empty($email)) {
    // CORREÇÃO: Usar set_flash_message em vez de $_SESSION['erro']
    set_flash_message('error', "Por favor, informe o e-mail.");
    header('Location: ../pages/login.php');
    exit;
}

$conn = getMasterConnection(); 

// Verifica se o usuário existe e se o status permite reativação
$sql = "SELECT id, nome FROM usuarios WHERE email = ? AND status IN ('suspenso', 'cancelado', 'inativo')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Gera token único
    $token = bin2hex(random_bytes(32));
    // Expira em 1 hora
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Atualiza token no banco
    $updateSql = "UPDATE usuarios SET token_recovery = ?, token_expiry = ? WHERE id = ?";
    $stmtUpd = $conn->prepare($updateSql);
    $stmtUpd->bind_param("ssi", $token, $expira, $user['id']);
    $stmtUpd->execute();
    
    // Define URL base
    $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost/app-controle-contas';
    $link = $baseUrl . "/pages/reativar_processo.php?token=" . $token;
    
    $assunto = "Reativar Conta - App Controle Contas";
    $mensagem = "Olá " . $user['nome'] . ",<br><br>";
    $mensagem .= "Recebemos uma solicitação para reativar sua conta.<br>";
    $mensagem .= "Clique no link abaixo para redefinir sua senha e escolher um novo plano:<br><br>";
    $mensagem .= "<a href='$link'>Clique aqui para reativar</a><br><br>";
    $mensagem .= "Este link expira em 1 hora.";
    
    if (enviarEmail($email, $user['nome'], $assunto, $mensagem)) {
        // CORREÇÃO: Usar set_flash_message('success', ...)
        set_flash_message('success', "E-mail de reativação enviado! Verifique sua caixa de entrada.");
    } else {
        // CORREÇÃO: Usar set_flash_message('error', ...)
        set_flash_message('error', "Erro ao tentar enviar o e-mail. Tente novamente mais tarde.");
    }
    
} else {
    // CORREÇÃO: Usar set_flash_message('error', ...)
    set_flash_message('error', "E-mail não encontrado ou a conta não está elegível para reativação.");
}

$stmt->close();
if(isset($stmtUpd)) $stmtUpd->close();
$conn->close();

header('Location: ../pages/login.php');
exit;
?>