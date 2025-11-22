<?php
// includes/automacao_lembretes.php

// Verifica se a conexão PDO já existe, senão conecta
if (!isset($pdo)) {
    // Se este arquivo for rodado via cron ou CLI, precisará dos includes.
    // Se for include da home, $conn (mysqli) ou $pdo já deve existir.
    // Adaptando para usar o padrão do seu sistema (supondo que database.php cria conexão)
    require_once __DIR__ . '/../database.php';
    // Convertendo conexão para PDO se necessário ou criando nova para este script isolado
    // Para simplificar, vou assumir que você tem $pdo ou vou criar uma conexão simples aqui se não existir.
    // NOTA: No seu sistema, você usa getTenantConnection() que retorna mysqli.
    // Vamos usar mysqli para manter consistência com o resto do código que vi.
}

// Detectar URL base para o link
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Ajuste este caminho conforme sua estrutura real se necessário
$baseUrl = $protocol . "://" . $host . "/app-controle-contas/pages/lembrete.php";

$today = date('Y-m-d');

// Usa a conexão $conn (mysqli) que vem do index/home
if (isset($conn) && $conn instanceof mysqli) {
    
    // 1. Selecionar lembretes de HOJE que ainda não enviaram email
    $sql = "SELECT l.*, u.nome as nome_usuario 
            FROM lembretes l
            JOIN usuarios u ON l.usuario_id = u.id
            WHERE l.data_lembrete = ? 
            AND l.email_enviado = 0";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $lembretes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($lembretes) > 0) {
        require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
        require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

        foreach ($lembretes as $lem) {
            // Se não tiver email específico, pula (ou usa o do usuário se preferir)
            if (empty($lem['email_notificacao'])) continue;

            // Explode para suportar múltiplos emails (separados por vírgula)
            $destinatarios = explode(',', $lem['email_notificacao']);

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                // Configurações do servidor (Pegando do .env via $_ENV se disponível, ou hardcoded)
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'];
                $mail->Password   = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
                $mail->Port       = $_ENV['MAIL_PORT'];
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                
                // Adiciona todos os destinatários
                foreach($destinatarios as $email) {
                    $email = trim($email);
                    if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                        $mail->addAddress($email);
                    }
                }

                $mail->isHTML(true);
                $mail->Subject = "Lembrete: " . $lem['titulo'];

                // CORPO DO EMAIL COM AVISO E LINK
                $body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #00bfff;'>Olá, {$lem['nome_usuario']}!</h2>
                    <p>Você tem um lembrete agendado para hoje:</p>
                    
                    <div style='background: #f4f4f4; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                        <h3 style='margin-top: 0;'>{$lem['titulo']}</h3>
                        <p style='font-size: 14px;'>Horário: ".date('H:i', strtotime($lem['hora_lembrete']))."</p>
                        <p>".nl2br(htmlspecialchars($lem['descricao']))."</p>
                    </div>

                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>

                    <div style='color: #dc3545; font-weight: bold; font-size: 14px;'>
                        ⚠ ATENÇÃO: Seu lembrete será deletado automaticamente no dia seguinte.
                    </div>
                    
                    <p style='margin-top: 10px;'>
                        Se deseja manter este lembrete, clique no botão abaixo e atualize a data para um dia futuro:
                    </p>

                    <p style='text-align: center;'>
                        <a href='{$baseUrl}' style='background-color: #00bfff; color: #fff; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                            Acessar e Atualizar Data
                        </a>
                    </p>
                    
                    <p style='font-size: 12px; color: #999; margin-top: 30px;'>
                        Se o botão não funcionar, acesse: <br> $baseUrl
                    </p>
                </div>
                ";

                $mail->Body = $body;
                $mail->send();

                // Marca como enviado para não enviar de novo no mesmo dia
                $upd = $conn->prepare("UPDATE lembretes SET email_enviado = 1 WHERE id = ?");
                $upd->bind_param("i", $lem['id']);
                $upd->execute();

            } catch (Exception $e) {
                error_log("Erro ao enviar email lembrete ID {$lem['id']}: {$mail->ErrorInfo}");
            }
        }
    }

    // 2. Deletar lembretes VENCIDOS (Data menor que hoje)
    // Isso garante que o aviso do email seja verdadeiro: se ele não mudou a data ontem, hoje (dia seguinte) apaga.
    $ontem = date('Y-m-d', strtotime('-1 day'));
    // Apaga lembretes que são menores que hoje (ou seja, de ontem pra trás)
    $conn->query("DELETE FROM lembretes WHERE data_lembrete < '$today'");
}
?>