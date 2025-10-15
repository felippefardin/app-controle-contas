<?php

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carrega as configurações e variáveis de ambiente (do .env)
require_once '../includes/config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cria uma instância do PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP a partir do .env
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = $_ENV['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';

        // Coleta os dados do formulário
        $nome = isset($_POST['anonimo']) ? 'Anônimo' : filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
        $whatsapp = isset($_POST['anonimo']) ? '' : filter_var($_POST['whatsapp'], FILTER_SANITIZE_STRING);
        $email_usuario = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mensagem = filter_var($_POST['mensagem'], FILTER_SANITIZE_STRING);

        // Destinatários
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress('contatotech.tecnologia@gmail.com', 'Contato Tech Suporte');
        $mail->addReplyTo($email_usuario, $nome);

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = 'Solicitação de Suporte - App Controle de Contas';
        $mail->Body    = "
            <h2>Solicitação de Suporte Recebida</h2>
            <p><strong>Nome:</strong> {$nome}</p>
            <p><strong>WhatsApp:</strong> {$whatsapp}</p>
            <p><strong>E-mail para Retorno:</strong> {$email_usuario}</p>
            <hr>
            <p><strong>Mensagem:</strong><br>" . nl2br($mensagem) . "</p>
        ";
        $mail->AltBody = "Nome: {$nome}\nWhatsApp: {$whatsapp}\nE-mail para Retorno: {$email_usuario}\nMensagem:\n{$mensagem}";

        $mail->send();
        echo 'Mensagem enviada com sucesso.';
    } catch (Exception $e) {
        echo "A mensagem não pôde ser enviada. Erro do Mailer: {$mail->ErrorInfo}";
    }
}
?>