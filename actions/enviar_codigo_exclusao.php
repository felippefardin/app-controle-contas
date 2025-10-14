<?php
require_once '../includes/session_init.php';
include('../database.php');

// Incluir PHPMailer
require __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/../lib/PHPMailer/SMTP.php';
require __DIR__ . '/../lib/PHPMailer/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'admin') {
    echo "Acesso negado";
    exit;
}

$id_conta = $_GET['id'] ?? null;
if (!$id_conta) {
    echo "ID da conta não informado";
    exit;
}

// E-mail do administrador (usuário logado)
$email_admin = $_SESSION['usuario']['email'];

// Gerar código aleatório de 6 dígitos
$codigo = rand(100000, 999999);

// Salvar código no banco
$stmt = $conn->prepare("INSERT INTO codigos_confirmacao (codigo, email_admin) VALUES (?, ?)");
$stmt->bind_param("ss", $codigo, $email_admin);
$stmt->execute();

// Criar o objeto PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuração SMTP Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'felippefardin@gmail.com';      
    $mail->Password   = 'ejlg wslz aulp zzgw';         
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('nao-responda@seudominio.com', 'App Controle de Contas');
    $mail->addAddress($email_admin);

    $mail->Subject = 'Código de confirmação para exclusão';
    $mail->Body    = "Olá,\n\nSeu código para confirmar a exclusão da conta é:\n\n$codigo\n\nEste código é válido por 10 minutos.\n\nAtenciosamente,\nEquipe Controle de Contas";

    $mail->send();

} catch (Exception $e) {
    echo "Erro ao enviar código: {$mail->ErrorInfo}";
    exit;
}

// Exibir formulário para digitar o código
?>

<h2>Digite o código enviado ao seu e-mail para confirmar a exclusão</h2>
<form method="POST" action="../actions/validar_codigo_exclusao.php">
  <input type="hidden" name="id_conta" value="<?=htmlspecialchars($id_conta)?>">
  <input type="text" name="codigo" placeholder="Código" required>
  <button type="submit">Confirmar exclusão</button>
</form>
