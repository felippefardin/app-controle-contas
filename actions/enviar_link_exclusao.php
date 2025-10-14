<?php
require_once '../includes/session_init.php';
include('../database.php');
include('../actions/enviar_email.php'); // Incluímos o arquivo que envia e-mails

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$email = $_SESSION['usuario']['email'];
$nome = $_SESSION['usuario']['nome'];

// Gera um token único e seguro
$token = bin2hex(random_bytes(32));
$expira_em = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

// Salva o token no banco de dados
$stmt = $conn->prepare("INSERT INTO solicitacoes_exclusao (id_usuario, token, expira_em) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id_usuario, $token, $expira_em);

if ($stmt->execute()) {
    // Envia o e-mail com o link de confirmação
    if (enviarLinkExclusao($email, $nome, $token)) {
        header("Location: ../pages/perfil.php?mensagem=Email de confirmação enviado com sucesso!");
    } else {
        header("Location: ../pages/perfil.php?erro=Falha ao enviar o e-mail de confirmação.");
    }
} else {
    header("Location: ../pages/perfil.php?erro=Ocorreu um erro ao processar sua solicitação.");
}

$stmt->close();
$conn->close();
exit;
?>