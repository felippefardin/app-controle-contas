<?php
require_once '../includes/session_init.php';

// Carrega o autoload do Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    die("ERRO CRÍTICO: O arquivo vendor/autoload.php não foi encontrado.");
}

// Carrega as variáveis de ambiente
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Silencioso se não achar, pois pode estar usando variáveis de servidor
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!$email) {
        $erro = "Preencha o campo de e-mail.";
    } else {
        
        // 1. Conexão com o banco de dados MASTER (onde estão os usuários)
        $servername = $_ENV['DB_HOST'] ?? 'localhost';
        $username   = $_ENV['DB_USER'] ?? 'root';
        $password   = $_ENV['DB_PASSWORD'] ?? ''; 
        $database   = $_ENV['DB_DATABASE'] ?? 'app_controle_contas';

        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Falha na conexão com o banco de dados: " . $conn->connect_error);
        }

        // 2. Busca na tabela USUARIOS (Corrigido de 'tenants' para 'usuarios')
        $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            $id_usuario = $usuario['id'];
            $nome_usuario = $usuario['nome'];
            $email_usuario = $usuario['email'];

            // 3. Gera token e expiração
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 4. Atualiza a tabela USUARIOS com o token (Corrigido para usar colunas existentes)
            $stmtUpdate = $conn->prepare("UPDATE usuarios SET token_reset = ?, token_expira_em = ? WHERE id = ?");
            $stmtUpdate->bind_param("ssi", $token, $expiracao, $id_usuario);
            
            if ($stmtUpdate->execute()) {
                // 5. Enviar e-mail
                $mail = new PHPMailer(true);
                try {
                    // Configurações de Servidor
                    $mail->isSMTP();
                    $mail->Host       = $_ENV['MAIL_HOST'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['MAIL_USERNAME'];
                    $mail->Password   = $_ENV['MAIL_PASSWORD'];
                    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls'; 
                    $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587); 
                    $mail->CharSet    = 'UTF-8';

                    // Remetente e Destinatário
                    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                    $mail->addAddress($email_usuario, $nome_usuario);

                    // Conteúdo
                    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/app-controle-contas', '/'); 
                    $link = $appUrl . "/pages/nova_senha.php?token=" . $token;

                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperação de Senha - App Controle';
                    
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; color: #333;'>
                            <h2>Recuperação de Senha</h2>
                            <p>Olá, <strong>$nome_usuario</strong>.</p>
                            <p>Recebemos uma solicitação para redefinir sua senha. Se não foi você, ignore este e-mail.</p>
                            <p>Para criar uma nova senha, clique no botão abaixo:</p>
                            <p>
                                <a href='$link' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Redefinir Senha</a>
                            </p>
                            <p>Ou copie e cole o link abaixo no seu navegador:</p>
                            <p>$link</p>
                            <p><em>Este link expira em 1 hora.</em></p>
                        </div>
                    ";
                    $mail->AltBody = "Olá $nome_usuario. Use o link a seguir para redefinir sua senha: $link";

                    $mail->send();
                    $sucesso = "Um e-mail com as instruções foi enviado para você!";
                } catch (Exception $e) {
                    $erro = "Erro ao enviar e-mail. Tente novamente mais tarde. (Erro: {$mail->ErrorInfo})";
                }
            } else {
                $erro = "Erro ao gerar o token de recuperação.";
            }
            $stmtUpdate->close();
        } else {
            // Por segurança, mostramos a mesma mensagem mesmo se o e-mail não existir
            $sucesso = "Se o e-mail estiver cadastrado, você receberá as instruções em instantes.";
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Esqueci minha senha</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
    body { background-color:#121212; color:#eee; font-family:'Segoe UI', Arial, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
    .container { background:#1e1e1e; padding:30px; border-radius:12px; width:100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; }
    h2 { color:#00bfff; margin-bottom:20px; font-weight: 600; }
    p.desc { color: #aaa; font-size: 0.9rem; margin-bottom: 20px; }
    label { display: block; text-align: left; margin-bottom: 8px; color: #ccc; font-weight: bold; }
    input[type="email"] { width: 100%; padding:12px; border:1px solid #333; border-radius:6px; font-size:1rem; background-color: #2c2c2c; color: #fff; box-sizing: border-box; transition: 0.3s; }
    input[type="email"]:focus { outline: none; border-color: #00bfff; box-shadow: 0 0 8px rgba(0, 191, 255, 0.3); }
    button { width: 100%; margin-top:20px; padding:12px; border:none; border-radius:6px; background: linear-gradient(135deg, #007bff, #00bfff); color:#fff; font-weight:bold; font-size: 1rem; cursor:pointer; transition: transform 0.2s, box-shadow 0.2s; }
    button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4); }
    .mensagem { text-align:center; padding:12px; border-radius:6px; margin-bottom:20px; font-size: 0.95rem; }
    .erro { background-color: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid #dc3545; }
    .sucesso { background-color: rgba(40, 167, 69, 0.2); color: #2ecc71; border: 1px solid #28a745; }
    .back-link { display: block; margin-top: 20px; color: #888; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
    .back-link:hover { color: #fff; }
</style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-lock"></i> Recuperar Senha</h2>
    <p class="desc">Digite seu e-mail para receber o link de redefinição.</p>

    <?php if ($erro): ?>
        <div class="mensagem erro"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mensagem sucesso"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="email">E-mail cadastrado</label>
        <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus>

        <button type="submit">Enviar Link</button>   
    </form>

    <a href="login.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Voltar para o Login</a>
</div>

</body>
</html>