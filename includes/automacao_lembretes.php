<?php
// includes/automacao_lembretes.php

if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // 1. Excluir lembretes vencidos (data anterior a hoje)
    $stmtDel = $pdo->prepare("DELETE FROM lembretes WHERE data_lembrete < ? AND usuario_id = ?");
    $stmtDel->execute([$today, $current_user_id]);

    // 2. Verificar lembretes de HOJE para enviar E-mail
    // Pegamos apenas os que ainda não foram notificados por email (email_enviado = 0)
    $stmtEmail = $pdo->prepare("SELECT * FROM lembretes WHERE data_lembrete = ? AND usuario_id = ? AND email_enviado = 0");
    $stmtEmail->execute([$today, $current_user_id]);
    $lembretesParaEmail = $stmtEmail->fetchAll(PDO::FETCH_ASSOC);

    if (count($lembretesParaEmail) > 0) {
        // Buscar e-mail do usuário
        $stmtUser = $pdo->prepare("SELECT email, nome FROM usuarios WHERE id = ?");
        $stmtUser->execute([$current_user_id]);
        $dadosUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($dadosUser) {
            require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
            require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
            // Se você tiver um wrapper pronto em actions/enviar_email.php, use-o aqui.
            // Abaixo é um exemplo genérico usando a lógica do seu sistema:
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                // Configurações do servidor (Ajuste conforme seu config.php)
                $mail->isSMTP();
                $mail->Host = 'smtp.seuprovedor.com'; // Configure aqui ou pegue do banco
                $mail->SMTPAuth = true;
                $mail->Username = 'seu@email.com';
                $mail->Password = 'sua_senha';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('no-reply@seuapp.com', 'Sistema Lembretes');
                $mail->addAddress($dadosUser['email'], $dadosUser['nome']);
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';

                $conteudoEmail = "<h2>Olá, {$dadosUser['nome']}!</h2>";
                $conteudoEmail .= "<p>Você tem lembretes para hoje ($today). Eles serão excluídos automaticamente amanhã.</p><ul>";
                
                foreach ($lembretesParaEmail as $lem) {
                    $conteudoEmail .= "<li><strong>{$lem['titulo']}</strong>: {$lem['descricao']} às {$lem['hora_lembrete']}</li>";
                    
                    // Marcar como enviado para não enviar duplicado a cada refresh
                    $upd = $pdo->prepare("UPDATE lembretes SET email_enviado = 1 WHERE id = ?");
                    $upd->execute([$lem['id']]);
                }
                $conteudoEmail .= "</ul>";

                $mail->Subject = 'Lembretes do Dia - Exclusão Programada';
                $mail->Body    = $conteudoEmail;
                
                // Descomente para ativar envio real
                // $mail->send(); 

            } catch (Exception $e) {
                // Log de erro se necessário, mas não pare a execução da home
            }
        }
    }
}
?>