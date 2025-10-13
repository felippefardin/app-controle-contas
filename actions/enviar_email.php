<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica se o autoload do Composer existe para carregar as bibliotecas e o .env
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';

    // Carrega as variáveis de ambiente do arquivo .env na raiz do projeto
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        // Se o .env não for encontrado, encerra a execução com uma mensagem clara.
        die("ERRO CRÍTICO: Não foi possível encontrar o arquivo .env. Certifique-se de que ele está na pasta raiz do seu projeto.");
    }

} else {
    // Se não usar Composer, o .env não pode ser carregado.
    die("ERRO CRÍTICO: O arquivo vendor/autoload.php não foi encontrado. Por favor, execute 'composer install' para instalar as dependências.");
}


function enviarCodigoRecuperacao($emailDestino, $nomeUsuario, $codigo) {
    $mail = new PHPMailer(true);
    try {
        // Configurações do servidor a partir do .env
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = (int)$_ENV['MAIL_PORT'];

        // Remetente e destinatário
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);
        $mail->CharSet = 'UTF-8';

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
?>