<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/config/config.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/perfil.php');
    exit;
}

// 🔹 USA CONEXÃO MASTER (Pois a tabela suporte_usage está lá)
$conn = getMasterConnection();

if (!$conn) {
    $_SESSION['perfil_erro'] = "Erro ao conectar com o servidor central.";
    header("Location: ../pages/perfil.php");
    exit;
}

$tenant_id = $_SESSION['tenant_id'];
$mes_atual = date('Y-m');
$plano = $_SESSION['plano'] ?? 'basico';
$email_usuario = $_SESSION['email'];
$nome_usuario = $_SESSION['nome'];

$limite = ($plano == 'essencial') ? 3 : 1;

// Busca contador atual
$stmt = $conn->prepare("SELECT contador FROM suporte_usage WHERE tenant_id = ? AND mes_ano = ?");
$stmt->bind_param("ss", $tenant_id, $mes_atual);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$uso_atual = $row['contador'] ?? 0;
$stmt->close();

$is_pago = false;

if ($uso_atual >= $limite) {
    if (!isset($_POST['pagamento_aceito'])) {
        $_SESSION['perfil_erro'] = "Erro na validação do pagamento.";
        header("Location: ../pages/perfil.php");
        exit;
    }
    $is_pago = true;
}

// Atualiza contador no banco
if ($row) {
    $stmt = $conn->prepare("UPDATE suporte_usage SET contador = contador + 1 WHERE tenant_id = ? AND mes_ano = ?");
    $stmt->bind_param("ss", $tenant_id, $mes_atual);
} else {
    $stmt = $conn->prepare("INSERT INTO suporte_usage (tenant_id, mes_ano, contador) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $tenant_id, $mes_atual);
}
$stmt->execute();
$stmt->close();
$conn->close(); // Fecha conexão Master

// Envia E-mail
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
    $mail->Port       = $_ENV['MAIL_PORT'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress('contatotech.tecnologia@gmail.com');

    $tipo_suporte = $is_pago ? "EXTRA (Cobrar R$ 20,99)" : "GRATUITO";

    $mail->isHTML(true);
    $mail->Subject = "Solicitação de Suporte [{$plano}] - {$tipo_suporte}";
    $mail->Body    = "
        <h2>Nova Solicitação de Treinamento/Suporte</h2>
        <p><strong>Cliente:</strong> {$nome_usuario}</p>
        <p><strong>Email:</strong> {$email_usuario}</p>
        <p><strong>Plano:</strong> {$plano}</p>
        <p><strong>Tipo:</strong> {$tipo_suporte}</p>
        <p><strong>Uso no mês:</strong> " . ($uso_atual + 1) . "</p>
    ";

    $mail->send();
    $_SESSION['perfil_msg'] = "Solicitação de suporte enviada com sucesso!";

} catch (Exception $e) {
    $_SESSION['perfil_erro'] = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
}

header("Location: ../pages/perfil.php");
exit;
?>