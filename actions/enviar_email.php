<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica se o autoload do Composer existe para carregar as bibliotecas e o .env
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Carrega as variáveis de ambiente do arquivo .env na raiz do projeto
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        die("ERRO CRÍTICO: Não foi possível encontrar o arquivo .env.");
    }

} else {
    die("ERRO CRÍTICO: O arquivo vendor/autoload.php não foi encontrado.");
}

/**
 * Função genérica para envio de e-mail.
 * Utilizada por: solicitar_cancelamento.php, processar_cancelamentos.php, solicitar_reativacao.php
 */
function enviarEmail($emailDestino, $nomeUsuario, $assunto, $mensagem) {
    $mail = new PHPMailer(true);
    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = (int)$_ENV['MAIL_PORT'];
        $mail->CharSet    = 'UTF-8';

        // Remetente e destinatário
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;
        // Cria um texto simples removendo as tags HTML para clientes de email antigos
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $mensagem));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail genérico: ' . $mail->ErrorInfo);
        return false;
    }
}

function enviarCodigoRecuperacao($emailDestino, $nomeUsuario, $codigo) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = (int)$_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);
        $mail->CharSet = 'UTF-8';

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
        $mail->AltBody = "Olá {$nomeUsuario}, seu código: {$codigo}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail: ' . $mail->ErrorInfo);
        return false;
    }
}

function enviarLinkExclusao($emailDestino, $nomeUsuario, $token) {
    $mail = new PHPMailer(true);
    // Ajuste a URL base conforme seu ambiente (localhost ou domínio real)
    $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost/app-controle-contas';
    $link = $baseUrl . "/pages/confirmar_exclusao_conta.php?token=" . $token;

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port       = (int)$_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);
        $mail->CharSet = 'UTF-8';

        $mail->isHTML(true);
        $mail->Subject = 'Confirmação de Exclusão de Conta';
        
        // CORREÇÃO: Adicionada a mensagem solicitada no corpo do e-mail
        $mail->Body = "
            <p>Olá <strong>{$nomeUsuario}</strong>,</p>
            <p>Recebemos uma solicitação para excluir sua conta. Se você realmente deseja continuar, clique no link abaixo:</p>
            <p><a href='{$link}' style='color: #dc3545;'>Confirmar Exclusão da Conta</a></p>
            <p>Este link é válido por 1 hora.</p>
            <p>Importante lembrar que após o cancelamento total da conta, nós não guardamos nenhum dado do usuário. Caso seja necessário, onde o sistema permitir, realizar o download em pdf, excel ou csv. Obrigado.</p>
            <br>
            <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
        ";
        
        $mail->AltBody = "Acesse o link para excluir: {$link}. Importante: após o cancelamento, não guardamos dados.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail de exclusão: ' . $mail->ErrorInfo);
        return false;
    }
}

function enviarEmailAssinaturaConfirmada($emailDestino, $nomeUsuario, $nomePlano) {
    // Reutiliza a lógica genérica ou implementa específica se desejar template diferente
    $assunto = 'Sua assinatura foi confirmada!';
    $mensagem = "
        <p>Olá <strong>{$nomeUsuario}</strong>,</p>
        <p>Sua assinatura do <strong>Plano {$nomePlano}</strong> foi confirmada com sucesso!</p>
        <p>Você já pode aproveitar todos os recursos.</p>
        <br>
        <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
    ";
    return enviarEmail($emailDestino, $nomeUsuario, $assunto, $mensagem);
}

function enviarEmailPagamentoFalhou($emailDestino, $nomeUsuario) {
    $assunto = 'Falha no pagamento da sua assinatura';
    $mensagem = "
        <p>Olá <strong>{$nomeUsuario}</strong>,</p>
        <p>Não conseguimos processar o pagamento da sua assinatura.</p>
        <p>Por favor, atualize suas informações de pagamento.</p>
        <br>
        <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
    ";
    return enviarEmail($emailDestino, $nomeUsuario, $assunto, $mensagem);
}

function enviarEmailAssinaturaCancelada($emailDestino, $nomeUsuario) {
    $assunto = 'Sua assinatura foi cancelada';
    $mensagem = "
        <p>Olá <strong>{$nomeUsuario}</strong>,</p>
        <p>Recebemos sua solicitação e sua assinatura foi cancelada com sucesso.</p>
        <br>
        <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
    ";
    return enviarEmail($emailDestino, $nomeUsuario, $assunto, $mensagem);
}
?>