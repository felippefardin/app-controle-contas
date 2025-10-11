<?php
session_start();
include('../database.php');

// Inclui os arquivos do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Verifica se o autoload do Composer existe. Se você instalou via Composer, o caminho estará correto.
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} else {
    // Caminho alternativo caso você tenha colocado a pasta PHPMailer manualmente
    require '../PHPMailer/PHPMailer/src/Exception.php';
    require '../PHPMailer/PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/PHPMailer/src/SMTP.php';
}


if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_conta = $_POST['id_conta'];
    $email_cobranca = filter_var($_POST['email_cobranca'], FILTER_SANITIZE_EMAIL);
    $chave_pix_ou_codigo = $_POST['chave_pix_ou_codigo'];
    $id_usuario_logado = $_SESSION['usuario']['id'];

    // 1. Busca os dados da conta para segurança e para preencher o e-mail
    $stmt = $conn->prepare("SELECT * FROM contas_receber WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_conta, $id_usuario_logado);
    $stmt->execute();
    $result = $stmt->get_result();
    $conta = $result->fetch_assoc();
    $stmt->close();

    if (!$conta) {
        // Se a conta não existir ou não pertencer ao usuário, redireciona com erro
        header('Location: ../pages/contas_receber.php?erro=cobranca_invalida');
        exit;
    }

    // 2. Monta e envia o e-mail
    $mail = new PHPMailer(true);

    try {
        // ---------------------------------------------------------------------
        // ⚠️ ATENÇÃO: CONFIGURE SEU SERVIDOR DE E-MAIL (SMTP) AQUI ⚠️
        // ---------------------------------------------------------------------
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com'; // Ex: 'smtp.gmail.com' ou o SMTP do seu provedor
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seu_email@seudominio.com';   // Seu e-mail completo
        $mail->Password   = 'sua_senha_de_email_ou_app'; // Sua senha
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use 'tls' ou 'ssl'
        $mail->Port       = 465;                      // Porta do SMTP (587 para TLS, 465 para SSL)
        $mail->CharSet    = 'UTF-8';

        // Remetente (quem envia) e Destinatário (para quem vai)
        $mail->setFrom('seu_email@seudominio.com', 'Nome da Sua Empresa ou App'); // Use o mesmo e-mail do Username
        $mail->addAddress($email_cobranca, $conta['cliente']); // E-mail e nome do cliente

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = 'Cobrança referente a: ' . $conta['descricao'];
        
        // Corpo do e-mail
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2>Olá, " . htmlspecialchars($conta['cliente']) . "!</h2>
                <p>Este é um lembrete de cobrança referente à sua conta pendente:</p>
                <ul style='list-style-type: none; padding: 0;'>
                    <li><strong>Descrição:</strong> " . htmlspecialchars($conta['descricao']) . "</li>
                    <li><strong>Valor:</strong> R$ " . number_format($conta['valor'], 2, ',', '.') . "</li>
                    <li><strong>Vencimento:</strong> " . date('d/m/Y', strtotime($conta['data_vencimento'])) . "</li>
                </ul>
                <p>Para efetuar o pagamento, por favor, utilize a chave PIX ou o código abaixo:</p>
                <div style='background-color: #f0f0f0; padding: 15px; border-radius: 5px; border: 1px solid #ddd;'>
                    <pre style='white-space: pre-wrap; word-wrap: break-word; margin: 0;'>" . htmlspecialchars($chave_pix_ou_codigo) . "</pre>
                </div>
                <p>Agradecemos a sua atenção.<br>Atenciosamente,<br><strong>Sua Empresa/App</strong></p>
            </div>
        ";

        // Corpo alternativo em texto puro para clientes de e-mail que não suportam HTML
        $mail->AltBody = "Olá, " . htmlspecialchars($conta['cliente']) . "!\n\nEste é um lembrete sobre sua conta:\n- Descrição: " . htmlspecialchars($conta['descricao']) . "\n- Valor: R$ " . number_format($conta['valor'], 2, ',', '.') . "\n- Vencimento: " . date('d/m/Y', strtotime($conta['data_vencimento'])) . "\n\nPara pagar, utilize o código: " . htmlspecialchars($chave_pix_ou_codigo);

        $mail->send();
        header('Location: ../pages/contas_receber.php?sucesso=cobranca_enviada');

    } catch (Exception $e) {
        // Em caso de erro, redireciona com uma mensagem para depuração
        header("Location: ../pages/contas_receber.php?erro=email_falhou&msg=" . urlencode($mail->ErrorInfo));
    }
    exit;
} else {
    header('Location: ../pages/contas_receber.php');
    exit;
}
?>