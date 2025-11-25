<?php
session_start();
require_once '../includes/config/config.php';
require_once 'enviar_email.php'; // Certifique-se de que este arquivo existe

$email = $_POST['email_reativacao'] ?? '';

if (empty($email)) {
    $_SESSION['erro'] = "Por favor, informe o e-mail.";
    header('Location: ../pages/login.php');
    exit;
}

// Obtém conexão (ajuste para getTenantConnection() se a tabela usuarios for por cliente, 
// mas geralmente reativação/pagamento fica no Master)
$conn = getMasterConnection(); 

// Verifica se o usuário existe e está suspenso
$sql = "SELECT id, nome FROM usuarios WHERE email = ? AND status = 'suspenso'";
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
    // Certifique-se de ter criado as colunas token_recovery e token_expiry na tabela usuarios
    $updateSql = "UPDATE usuarios SET token_recovery = ?, token_expiry = ? WHERE id = ?";
    $stmtUpd = $conn->prepare($updateSql);
    $stmtUpd->bind_param("ssi", $token, $expira, $user['id']);
    $stmtUpd->execute();
    
    $link = $_ENV['APP_URL'] . "/pages/reativar_processo.php?token=" . $token;
    
    $assunto = "Reativar Conta - App Controle Contas";
    $mensagem = "Olá " . $user['nome'] . ",<br><br>";
    $mensagem .= "Recebemos uma solicitação para reativar sua conta.<br>";
    $mensagem .= "Clique no link abaixo para redefinir sua senha e escolher um novo plano:<br><br>";
    $mensagem .= "<a href='$link'>$link</a><br><br>";
    $mensagem .= "Este link expira em 1 hora.";
    
    // Função de envio de email existente no seu sistema
    enviarEmail($email, $user['nome'], $assunto, $mensagem);
    
    $_SESSION['sucesso'] = "E-mail de reativação enviado! Verifique sua caixa de entrada.";
} else {
    $_SESSION['erro'] = "E-mail não encontrado ou a conta não está suspensa.";
}

$stmt->close();
if(isset($stmtUpd)) $stmtUpd->close();
$conn->close();

header('Location: ../pages/login.php');
exit;
?>