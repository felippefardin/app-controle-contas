<?php
require_once '../includes/session_init.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
include('../database.php'); // Inclua seu arquivo de conexão

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } else {
        // Buscar usuário pelo email
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id_usuario);
            $stmt->fetch();

            // Gerar código de recuperação
            $codigo = rand(100000, 999999);

            // Inserir código na tabela recuperacao_senha
            $stmt_insert = $conn->prepare("INSERT INTO recuperacao_senha (usuario_id, codigo, usado, criado_em, data_geracao) VALUES (?, ?, 0, NOW(), NOW())");
            $stmt_insert->bind_param("is", $id_usuario, $codigo);

            if ($stmt_insert->execute()) {
                // Salvar email e código na sessão (opcional)
                $_SESSION['recuperacao_email'] = $email;
                $_SESSION['codigo_recuperacao'] = $codigo;

                // Enviar e-mail com o código
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'felippefardin@gmail.com';      // seu email SMTP
                    $mail->Password   = 'mwtz cwor zfji yygw';          // senha de app SMTP
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('felippefardin@gmail.com', 'App Controle de Contas');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
$mail->Subject = 'Nova senha - Seu código';

$mail->Body = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Recuperação de Senha</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
                   Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
      background-color: #f4f6f8;
      color: #333333;
      margin: 0; padding: 0;
    }
    .container {
      max-width: 600px;
      background: #ffffff;
      margin: 30px auto;
      padding: 20px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    h1 {
      color: #007BFF;
      font-weight: 600;
      font-size: 24px;
      margin-bottom: 10px;
    }
    p {
      font-size: 16px;
      line-height: 1.5;
      margin-top: 0;
    }
    .code {
      display: inline-block;
      background: #007BFF;
      color: white;
      font-weight: 700;
      font-size: 22px;
      padding: 10px 18px;
      border-radius: 6px;
      letter-spacing: 4px;
      margin: 15px 0;
      user-select: all;
    }
    footer {
      font-size: 14px;
      color: #666666;
      margin-top: 30px;
      border-top: 1px solid #eeeeee;
      padding-top: 10px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Recuperação de Senha</h1>
    <p>Olá,</p>
    <p>Seu código para recuperação de senha é:</p>
    <p class="code">' . htmlspecialchars($codigo) . '</p>
    <p>Este código é válido por 30 minutos.</p>
    <p>Atenciosamente,<br>Equipe App Controle de Contas</p>
    <footer>
      &copy; ' . date('Y') . ' App Controle de Contas. Todos os direitos reservados.
    </footer>
  </div>
</body>
</html>
';


                    $mail->send();

                    $sucesso = "Código enviado para seu e-mail.";
                } catch (Exception $e) {
                    $erro = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
                }
            } else {
                $erro = "Erro ao salvar código no banco.";
            }
            $stmt_insert->close();
        } else {
            $erro = "E-mail não cadastrado no sistema.";
        }
        $stmt->close();
    }
}
?>
<?php include('../includes/header.php'); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Esqueci minha senha</title>
  <style>
    body { font-family: sans-serif; padding: 20px; }
    form { max-width: 400px; margin: auto; }
    input, button { width: 100%; padding: 10px; margin-top: 10px; }
  </style>
</head>
<body>
  <form method="POST">
    <h2>Esqueci minha senha</h2>

    <?php if ($erro): ?>
      <div style="color:red;"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
      <div style="color:green;"><?= htmlspecialchars($sucesso) ?></div>
      <p><a href="nova_senha.php">Clique aqui para criar nova senha</a></p>
    <?php else: ?>
      <label for="email">Digite seu e-mail:</label>
      <input type="email" id="email" name="email" required autofocus />
      <button type="submit">Enviar código</button>
    <?php endif; ?>
  </form>
</body>
</html>
