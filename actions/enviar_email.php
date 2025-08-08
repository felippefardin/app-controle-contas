<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

function enviarCodigoRecuperacao($emailDestino, $nomeUsuario, $codigo) {
    $mail = new PHPMailer(true);
    try {
        // Configurações SMTP (exemplo com Gmail)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';         // servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'seu-email@gmail.com';  // seu email
        $mail->Password = 'sua-senha-app';       // senha do app ou sua senha
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ou PHPMailer::ENCRYPTION_SMTPS
        $mail->Port = 587;

        // Remetente e destinatário
        $mail->setFrom('seu-email@gmail.com', 'App Controle de Contas');
        $mail->addAddress($emailDestino, $nomeUsuario);

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de senha - Código de verificação';
        $mail->Body = "
            <p>Olá <strong>{$nomeUsuario}</strong>,</p>
            <p>Seu código para recuperação de senha é:</p>
            <h2 style='color: #007bff;'>{$codigo}</h2>
            <p>Este código é válido por 30 minutos.</p>
            <p>Se você não solicitou essa recuperação, ignore este e-mail.</p>
            <br>
            <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
        ";

        $mail->AltBody = "Olá {$nomeUsuario}, seu código para recuperação de senha é: {$codigo}. Este código é válido por 30 minutos.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail: ' . $mail->ErrorInfo);
        return false;
    }
}
