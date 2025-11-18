<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/config/config.php'; // Para pegar configurações de SMTP se houver
require_once '../vendor/autoload.php'; // Para PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Verifica Sessão
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. Captura dados da sessão com FALLBACK (Correção Principal)
// Tenta pegar 'email', se não existir, tenta 'usuario_email'
$email_usuario = $_SESSION['email'] ?? $_SESSION['usuario_email'] ?? null;
$id_usuario    = $_SESSION['usuario_id'] ?? null;

if (!$email_usuario) {
    $_SESSION['erro_perfil'] = 'Erro: E-mail do usuário não encontrado na sessão. Faça login novamente.';
    header('Location: ../pages/perfil.php');
    exit;
}

if (!$id_usuario) {
    $_SESSION['erro_perfil'] = 'Erro: ID do usuário não identificado.';
    header('Location: ../pages/perfil.php');
    exit;
}

// 3. Gera o Token de Exclusão (6 dígitos)
$token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expira_em = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$conn = getTenantConnection(); // Ou getMasterConnection() dependendo de onde você salva tokens
// Se a tabela de tokens ficar no banco do tenant:
if (!$conn) $conn = getMasterConnection(); 

if (!$conn) {
    $_SESSION['erro_perfil'] = 'Erro de conexão com o banco de dados.';
    header('Location: ../pages/perfil.php');
    exit;
}

// 4. Salva/Atualiza o Token no Banco
// Verifica se a tabela existe, se não, cria (opcional, mas bom para evitar erros)
$conn->query("CREATE TABLE IF NOT EXISTS solicitacoes_exclusao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(10) NOT NULL,
    expira_em DATETIME NOT NULL,
    UNIQUE KEY(id_usuario)
)");

// Remove solicitação anterior se houver
$stmt = $conn->prepare("DELETE FROM solicitacoes_exclusao WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();

// Insere nova
$stmt = $conn->prepare("INSERT INTO solicitacoes_exclusao (id_usuario, token, expira_em) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id_usuario, $token, $expira_em);

if ($stmt->execute()) {
    
    // 5. Envia o E-mail
    $mail = new PHPMailer(true);
    try {
        // Configurações do Servidor (Carregadas do .env ou config.php)
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom('no-reply@seusistema.com', 'Segurança - App Controle');
        $mail->addAddress($email_usuario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Código de Exclusão de Conta';
        $mail->Body    = "
            <h2>Solicitação de Exclusão de Conta</h2>
            <p>Você solicitou a exclusão da sua conta. Use o código abaixo para confirmar a ação:</p>
            <h1 style='color: #d9534f; letter-spacing: 5px;'>{$token}</h1>
            <p>Este código expira em 15 minutos.</p>
            <p>Se não foi você, altere sua senha imediatamente.</p>
        ";
        $mail->AltBody = "Seu código de exclusão é: {$token}";

        $mail->send();
        
        $_SESSION['sucesso_perfil'] = 'Código de confirmação enviado para seu e-mail.';
        // Redireciona para página de confirmar código
        header('Location: ../pages/confirmar_exclusao_conta.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['erro_perfil'] = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        header('Location: ../pages/perfil.php');
        exit;
    }

} else {
    $_SESSION['erro_perfil'] = 'Erro ao gerar código de exclusão.';
    header('Location: ../pages/perfil.php');
    exit;
}
?>