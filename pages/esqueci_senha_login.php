<?php
session_start();
// O 'database.php' não é mais necessário no topo, pois a conexão é feita dentro do POST.

// --- INÍCIO DA ALTERAÇÃO ---
// Carrega o autoload do Composer para PHPMailer e Dotenv
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Se o autoload não existe, o Dotenv não funcionará. Encerra com um erro claro.
    die("ERRO CRÍTICO: O arquivo vendor/autoload.php não foi encontrado. Por favor, execute 'composer install' para instalar as dependências.");
}

// Carrega as variáveis de ambiente do arquivo .env que está na raiz do projeto
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("ERRO CRÍTICO: O arquivo .env não foi encontrado na pasta raiz do projeto. Verifique o local e o nome do arquivo.");
}
// --- FIM DA ALTERAÇÃO ---

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!$email) {
        $erro = "Preencha o campo de e-mail.";
    } else {
        // Conexão com o banco de dados
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
                // --- INÍCIO DA ALTERAÇÃO ---
                // Configurações do servidor a partir do .env
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'];
                $mail->Password   = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION']; // Usa 'tls' ou 'ssl' do .env
                $mail->Port       = (int)$_ENV['MAIL_PORT']; // Converte para inteiro

                // Remetente e Destinatário
                $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
                // --- FIM DA ALTERAÇÃO ---

                $mail->addAddress($email_db, $nome);
                $mail->CharSet = 'UTF-8';

                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de senha';
                
                // --- INÍCIO DA ALTERAÇÃO ---
                // Cria o link de recuperação dinamicamente a partir do .env
                $appUrl = rtrim($_ENV['APP_URL'], '/'); // Remove a barra final, se houver
                $link = $appUrl . "/pages/resetar_senha.php?token=" . $token;
                // --- FIM DA ALTERAÇÃO ---
                
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
            // Mensagem genérica para não informar se um e-mail existe ou não no sistema
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