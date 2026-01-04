<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica se o autoload do Composer existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Carrega as variáveis de ambiente
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        // Continua mesmo se der erro, pois o servidor pode ter variáveis de ambiente reais
    }

} else {
    die("ERRO CRÍTICO: O arquivo vendor/autoload.php não foi encontrado. Execute 'composer install'.");
}

/**
 * Configuração padrão do PHPMailer para reutilização
 */
function getMailerInstance() {
    $mail = new PHPMailer(true);
    
    // Configurações do servidor
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
    $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
    $mail->CharSet    = 'UTF-8';

    // --- CORREÇÃO IMPORTANTE PARA LOCALHOST ---
    // Ignora verificação de certificado SSL em ambiente local
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Descomente a linha abaixo para ver erros detalhados na tela se o envio falhar
    // $mail->SMTPDebug = 2; 

    // Remetente padrão
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

    return $mail;
}

/**
 * Envia o link de exclusão de conta
 */
function enviarLinkExclusao($emailDestino, $nomeUsuario, $token) {
    try {
        $mail = getMailerInstance();
        
        // Ajuste a URL base
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost/app-controle-contas';
        // Garante que não tenha barra duplicada
        $baseUrl = rtrim($baseUrl, '/');
        $link = $baseUrl . "/pages/confirmar_exclusao_conta.php?token=" . $token;

        $mail->addAddress($emailDestino, $nomeUsuario);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmação de Exclusão de Conta';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #dc3545;'>Solicitação de Exclusão</h2>
                <p>Olá <strong>{$nomeUsuario}</strong>,</p>
                <p>Recebemos uma solicitação para excluir sua conta permanentemente.</p>
                <p>Se você tem certeza, clique no botão abaixo:</p>
                <p>
                    <a href='{$link}' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                        Confirmar Exclusão da Conta
                    </a>
                </p>
                <p style='font-size: 12px; color: #777;'>Link válido por 1 hora.</p>
                <p style='font-size: 12px; color: #777;'>Importante: Após o cancelamento, todos os seus dados serão apagados.</p>
                <hr>
                <p style='font-size: 11px;'>Se o botão não funcionar, copie e cole este link no navegador:<br>{$link}</p>
            </div>
        ";
        
        $mail->AltBody = "Para excluir sua conta, acesse: {$link}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Grava o erro real no log de erros do PHP (verifique seu error_log)
        error_log('Erro PHPMailer (Exclusão): ' . $mail->ErrorInfo);
        return false;
    }
}

// Funções auxiliares mantidas para compatibilidade
function enviarEmail($emailDestino, $nomeUsuario, $assunto, $mensagem) {
    try {
        $mail = getMailerInstance();
        $mail->addAddress($emailDestino, $nomeUsuario);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $mensagem));
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro genérico envio email: ' . $mail->ErrorInfo);
        return false;
    }
}
?>