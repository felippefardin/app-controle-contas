<?php
require_once '../includes/session_init.php';

// Carrega o autoload do Composer para PHPMailer e Dotenv
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
    die("ERRO CRÍTICO: O arquivo .env não foi encontrado.");
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
        
        // Conexão com o banco de dados usando variáveis de ambiente
        $servername = $_ENV['DB_HOST'] ?? 'localhost';
        $username   = $_ENV['DB_USER'] ?? 'root';
        $password   = $_ENV['DB_PASSWORD'] ?? ''; 
        $database   = "app_controle_contas"; // Banco de dados principal

        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }

        // --- CORREÇÃO DE LÓGICA ---
        // A consulta agora usa as colunas corretas da tabela 'tenants'
        $stmt = $conn->prepare("SELECT id, nome_empresa, admin_email FROM tenants WHERE admin_email = ?");
        // --- FIM DA CORREÇÃO ---

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // $id será o ID do tenant, $nome será o nome_empresa
            $stmt->bind_result($id, $nome, $email_db); 
            $stmt->fetch();

            // Gerar token
            $token = bin2hex(random_bytes(16));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // --- CORREÇÃO DE LÓGICA ---
            // Insere o 'id' do tenant na coluna 'usuario_id'.
            // (Se você quiser renomear 'usuario_id' para 'tenant_id' na tabela 'recuperacao_senha',
            // você precisará alterar esta consulta SQL também)
            $stmtToken = $conn->prepare("INSERT INTO recuperacao_senha (usuario_id, token, expira_em) VALUES (?, ?, ?)");
            $stmtToken->bind_param("iss", $id, $token, $expiracao);
            $stmtToken->execute();
            $stmtToken->close();

            // Enviar e-mail
            $mail = new PHPMailer(true);

            try {
                // Descomente a linha abaixo para ver o log do Gmail se ainda falhar
                // $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;

                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'];
                $mail->Password   = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION']; 
                $mail->Port       = (int)$_ENV['MAIL_PORT']; 

                $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                $mail->addAddress($email_db, $nome);
                $mail->CharSet = 'UTF-8';

                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de senha';
                
                $appUrl = rtrim($_ENV['APP_URL'], '/'); 
                $link = $appUrl . "/pages/resetar_senha.php?token=" . $token;
                
                $mail->Body = "Olá $nome,<br><br>Você solicitou a redefinição de sua senha. Clique no link abaixo para continuar:<br>
                               <a href='$link'>Redefinir Minha Senha</a><br><br>
                               Se você não conseguir clicar no link, copie e cole a seguinte URL no seu navegador:<br>
                               $link<br><br>Este link expira em 1 hora.";

                $mail->send();
                $sucesso = "Um e-mail de recuperação foi enviado para sua caixa de entrada!";
            } catch (Exception $e) {
                $erro = "Erro ao enviar e-mail: " . $mail->ErrorInfo;
            }
        } else {
            // Mensagem genérica
            $sucesso = "Se o e-mail informado estiver em nosso sistema, um link de recuperação será enviado.";
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
    form { background:#222; padding:25px 30px; border-radius:8px; width:100%; max-width: 350px; display:flex; flex-direction:column; box-shadow: 0 0 15px rgba(0, 191, 255, 0.2); }
    h2 { text-align:center; color:#00bfff; margin-bottom:20px; }
    label { margin-bottom: 5px; color: #ccc; }
    input { margin-bottom:15px; padding:12px; border:1px solid #444; border-radius:4px; font-size:1rem; background-color: #333; color: #eee; }
    input:focus { outline:2px solid #00bfff; background:#333; color:#fff; }
    button { margin-top:10px; padding:12px; border:none; border-radius:5px; background:#007bff; color:#fff; font-weight:bold; cursor:pointer; transition: background-color 0.3s; }
    button:hover { background:#0056b3; }
    .mensagem { text-align:center; padding:12px; border-radius:5px; margin-bottom:15px; font-weight: bold; }
    .erro { background:#cc4444; color: white; }
    .sucesso { background:#27ae60; color: white; }
</style>
</head>
<body>
<form method="POST">
    <h2>Recuperar Senha</h2>

    <?php if ($erro): ?>
        <div class="mensagem erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mensagem sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <label for="email">Digite seu e-mail de cadastro</label>
    <input type="email" id="email" name="email" required autofocus>

    <button type="submit">Enviar Link de Recuperação</button>   
</form>
</body>
</html>