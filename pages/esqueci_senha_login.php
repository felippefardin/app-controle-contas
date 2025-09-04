<?php
session_start();
require_once('../database.php');

// Incluir PHPMailer manualmente
require __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/../lib/PHPMailer/SMTP.php';
require __DIR__ . '/../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!$email) {
        $erro = "Preencha o e-mail.";
    } else {
        // 🔹 Conexão com o banco (mesma de contas_pagar.php)
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "app_controle_contas";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

        $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $nome, $email_db);
            $stmt->fetch();

            // Gerar token único e salvar na tabela de recuperação de senha
            $token = bin2hex(random_bytes(16));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmtToken = $conn->prepare("INSERT INTO recuperacao_senha (usuario_id, token, expira_em) VALUES (?, ?, ?)");
            $stmtToken->bind_param("iss", $id, $token, $expiracao);
            $stmtToken->execute();
            $stmtToken->close();

            // Enviar e-mail com link de recuperação
           $mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'felippefardin@gmail.com';
    $mail->Password   = 'kwrsaszsoyblypcf'; // senha de app sem espaços
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // melhor prática
    $mail->Port       = 587;

    $mail->setFrom('felippefardin@gmail.com', 'App Controle de Contas');
    $mail->addAddress($email_db, $nome);

                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de senha';
                $link = "http://localhost/app-controle-contas/pages/resetar_senha.php?token=$token";
                $mail->Body = "Olá $nome,<br><br>Clique no link abaixo para redefinir sua senha:<br>
                               <a href='$link'>$link</a><br><br>Esse link expira em 1 hora.";

                $mail->send();
                $sucesso = "E-mail de recuperação enviado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao enviar e-mail: " . $mail->ErrorInfo;
            }
        } else {
            $erro = "Usuário não encontrado.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Esqueci minha senha</title>
<style>
    body { background-color:#121212; color:#eee; font-family:Arial, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
    form { background:#222; padding:25px 30px; border-radius:8px; width:320px; display:flex; flex-direction:column; }
    h2 { text-align:center; color:#00bfff; margin-bottom:20px; }
    input { margin-top:10px; padding:10px; border:none; border-radius:4px; font-size:1rem; }
    input:focus { outline:2px solid #00bfff; background:#333; color:#fff; }
    button { margin-top:20px; padding:12px; border:none; border-radius:5px; background:#007bff; color:#fff; font-weight:bold; cursor:pointer; }
    button:hover { background:#0056b3; }
    .mensagem { text-align:center; padding:10px; border-radius:5px; margin-bottom:15px; }
    .erro { background:#cc4444; }
    .sucesso { background:#27ae60; }
</style>
</head>
<body>
<form method="POST">
    <h2>Esqueci minha senha</h2>

    <?php if ($erro): ?>
        <div class="mensagem erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mensagem sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required autofocus>

    <button type="submit">Enviar</button>   
</form>
</body>
</html>
