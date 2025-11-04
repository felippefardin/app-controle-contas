<?php
// 1. Iniciar a sessão PRIMEIRO DE TUDO
require_once '../includes/session_init.php'; 

// 2. Carregar o banco de dados
require_once '../database.php';

// 3. Importar as classes necessárias (PHPMailer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 4. VERIFICAR A SESSÃO (Usando as chaves corretas)
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['tenant_db'])) {
    header('Location: ../pages/login.php');
    exit;
}

// 5. Pegar a conexão (Funciona, pois depende de 'tenant_db')
$conn = getTenantConnection(); 
if ($conn === null) {
    header('Location: ../pages/perfil.php?erro=Falha de conexão BD.');
    exit;
}

// 6. Pegar dados do usuário da sessão
$usuario_logado = $_SESSION['usuario_logado'];
$id_usuario = $usuario_logado['id'];
$email_usuario = $usuario_logado['email'];
$nome_usuario = $usuario_logado['nome'];

// 7. ✅ PEGAR O NOME DO BANCO DE DADOS DA SESSÃO (A NOVA LÓGICA)
$tenant_db_info = $_SESSION['tenant_db'];
$tenant_db_name = $tenant_db_info['db_database']; // Ex: 'tenant_123_felippe'

if (empty($tenant_db_name)) {
     header('Location: ../pages/perfil.php?erro=Erro de sessão (Nome do BD não encontrado).');
    exit;
}
// AGORA TEMOS O $tenant_db_name CORRETAMENTE

// 8. Gerar e salvar o token no banco de dados do tenant
// (Suas colunas token_reset e token_expira_em já existem, como vimos pelo erro 1060)
$token = bin2hex(random_bytes(32));
$expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $conn->prepare("UPDATE usuarios SET token_reset = ?, token_expira_em = ? WHERE id = ?");
$stmt->bind_param("ssi", $token, $expiracao, $id_usuario);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: ../pages/perfil.php?erro=Erro ao gerar token.');
    exit;
}
$stmt->close();

// 9. Enviar o e-mail
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION']; 
    $mail->Port       = (int)$_ENV['MAIL_PORT']; 

    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($email_usuario, $nome_usuario);
    $mail->CharSet = 'UTF-8';

    $mail->isHTML(true);
    $mail->Subject = 'Redefinição de senha (Usuário)';
    
    $appUrl = rtrim($_ENV['APP_URL'], '/'); 
    
    // ✅ O LINK AGORA USA "tenant_db_name"
    $link = $appUrl . "/pages/resetar_senha_usuario.php?token=" . $token . "&tenant_db_name=" . urlencode($tenant_db_name);
    
    $mail->Body = "Olá $nome_usuario,<br><br>Você solicitou a redefinição de sua senha. Clique no link abaixo para continuar:<br>
                   <a href='$link'>Redefinir Minha Senha</a><br><br>
                   Se você não conseguir clicar no link, copie e cole a seguinte URL no seu navegador:<br>
                   $link<br><br>Este link expira em 1 hora.";

    $mail->send();
    $conn->close();
    header('Location: ../pages/perfil.php?mensagem=Link de redefinição enviado para o seu e-mail!');
    exit;

} catch (Exception $e) {
    $conn->close();
    header('Location: ../pages/perfil.php?erro=Erro ao enviar e-mail: ' . $mail->ErrorInfo);
    exit;
}
?>