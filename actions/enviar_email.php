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

function enviarLinkExclusao($emailDestino, $nomeUsuario, $token) {
    $mail = new PHPMailer(true);
    $link = "http://localhost/app-controle-contas/pages/confirmar_exclusao_conta.php?token=" . $token;

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
        $mail->Subject = 'Confirmação de Exclusão de Conta';
        $mail->Body = "
            <p>Olá <strong>{$nomeUsuario}</strong>,</p>
            <p>Recebemos uma solicitação para excluir sua conta. Se você realmente deseja continuar, clique no link abaixo:</p>
            <p><a href='{$link}' style='color: #dc3545;'>Confirmar Exclusão da Conta</a></p>
            <p>Este link é válido por 1 hora. Se você não solicitou isso, por favor, ignore este e-mail.</p>
            <br>
            <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
        ";
        $mail->AltBody = "Olá {$nomeUsuario}, para confirmar a exclusão da sua conta, acesse o seguinte link: {$link}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail de exclusão: ' . $mail->ErrorInfo);
        return false;
    }
}
// ===============================================
// === INÍCIO - NOVAS FUNÇÕES DE E-MAIL ===
// ===============================================

/**
 * Envia um e-mail de boas-vindas quando a assinatura é confirmada.
 */
function enviarEmailAssinaturaConfirmada($emailDestino, $nomeUsuario, $nomePlano) {
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
        $mail->CharSet = 'UTF-8';

        // Remetente e destinatário
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Sua assinatura foi confirmada!';
        $mail->Body = "
            <p>Olá <strong>{$nomeUsuario}</strong>,</p>
            <p>Sua assinatura do <strong>Plano {$nomePlano}</strong> foi confirmada com sucesso!</p>
            <p>Você já pode aproveitar todos os recursos.</p>
            <br>
            <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
        ";
        $mail->AltBody = "Olá {$nomeUsuario}, sua assinatura do Plano {$nomePlano} foi confirmada com sucesso!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail de confirmação de assinatura: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envia um e-mail de falha no pagamento.
 */
function enviarEmailPagamentoFalhou($emailDestino, $nomeUsuario) {
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
        $mail->CharSet = 'UTF-8';

        // Remetente e destinatário
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Falha no pagamento da sua assinatura';
        $mail->Body = "
            <p>Olá <strong>{$nomeUsuario}</strong>,</p>
            <p>Não conseguimos processar o pagamento da sua assinatura. Isso pode ocorrer por falta de saldo ou dados de cartão desatualizados.</p>
            <p>Por favor, acesse seu perfil em nosso site para atualizar suas informações de pagamento e manter seu acesso ativo.</p>
            <br>
            <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
        ";
        $mail->AltBody = "Olá {$nomeUsuario}, não conseguimos processar o pagamento da sua assinatura. Acesse seu perfil para atualizar seus dados.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail de falha de pagamento: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envia um e-mail de confirmação de cancelamento.
 */
function enviarEmailAssinaturaCancelada($emailDestino, $nomeUsuario) {
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
        $mail->CharSet = 'UTF-8';

        // Remetente e destinatário
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($emailDestino, $nomeUsuario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Sua assinatura foi cancelada';
        $mail->Body = "
            <p>Olá <strong>{$nomeUsuario}</strong>,</p>
            <p>Recebemos sua solicitação e sua assinatura foi cancelada com sucesso.</p>
            <p>Você ainda terá acesso aos recursos do seu plano até o final do período de cobrança atual. Esperamos ver você de volta em breve!</p>
            <br>
            <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
        ";
        $mail->AltBody = "Olá {$nomeUsuario}, sua assinatura foi cancelada com sucesso.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail de cancelamento: ' . $mail->ErrorInfo);
        return false;
    }
}

// ===============================================
// === FIM - NOVAS FUNÇÕES DE E-MAIL ===
// ===============================================

?>