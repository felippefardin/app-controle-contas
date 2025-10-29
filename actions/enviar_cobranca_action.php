<?php
require_once '../includes/session_init.php';
require_once '../database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. VERIFICA O AUTOLOAD E CARREGA O .ENV
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        die("ERRO CRÍTICO: Arquivo .env não encontrado. Verifique se ele está na pasta raiz do projeto.");
    }
} else {
    die("ERRO CRÍTICO: vendor/autoload.php não encontrado. Execute 'composer install'.");
}

// 2. VERIFICA O LOGIN E A CONEXÃO COM O BANCO DO CLIENTE
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
    header('Location: ../pages/contas_receber.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/contas_receber.php');
    exit;
}

// 3. COLETA E VALIDA OS DADOS
$id_usuario = $_SESSION['usuario_logado']['id'];
$id_conta_receber = filter_input(INPUT_POST, 'conta_id', FILTER_VALIDATE_INT);
$pessoa_id = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
$conta_bancaria_id = filter_input(INPUT_POST, 'banco_id', FILTER_VALIDATE_INT);
$email_destinatario = filter_input(INPUT_POST, 'email_destinatario', FILTER_VALIDATE_EMAIL);

if (!$id_conta_receber || !$pessoa_id || !$conta_bancaria_id || !$email_destinatario) {
    $_SESSION['error_message'] = "Dados inválidos ou faltando. Tente novamente.";
    header('Location: ../pages/contas_receber.php');
    exit;
}

// 4. BUSCA OS DADOS NO BANCO DE FORMA SEGURA
$stmt_conta = $conn->prepare("SELECT * FROM contas_receber WHERE id = ? AND usuario_id = ?");
$stmt_conta->bind_param("ii", $id_conta_receber, $id_usuario);
$stmt_conta->execute();
$conta = $stmt_conta->get_result()->fetch_assoc();
$stmt_conta->close();

$stmt_pessoa = $conn->prepare("SELECT * FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
$stmt_pessoa->bind_param("ii", $pessoa_id, $id_usuario);
$stmt_pessoa->execute();
$pessoa = $stmt_pessoa->get_result()->fetch_assoc();
$stmt_pessoa->close();

$stmt_banco = $conn->prepare("SELECT * FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
$stmt_banco->bind_param("ii", $conta_bancaria_id, $id_usuario);
$stmt_banco->execute();
$banco = $stmt_banco->get_result()->fetch_assoc();
$stmt_banco->close();

if (!$conta || !$pessoa || !$banco) {
    $_SESSION['error_message'] = "Não foi possível encontrar todos os dados para gerar a cobrança (verifique se a conta, o cliente e o banco pertencem a você).";
    header('Location: ../pages/contas_receber.php');
    exit;
}

// 5. LÓGICA DE ENVIO DE E-MAIL
$mail = new PHPMailer(true);
try {
    // Configurações do servidor
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
    $mail->Port       = (int)$_ENV['MAIL_PORT'];
    $mail->CharSet    = 'UTF-8';

    // Remetente e Destinatário
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($email_destinatario, $pessoa['nome']);

    // Anexo
    if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] == UPLOAD_ERR_OK) {
        $mail->addAttachment($_FILES['anexo']['tmp_name'], $_FILES['anexo']['name']);
    }

    // Conteúdo do E-mail
    $mail->isHTML(true);
    $mail->Subject = 'Lembrete de Cobrança: ' . htmlspecialchars($conta['responsavel']);
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h1>Olá, " . htmlspecialchars($pessoa['nome']) . "!</h1>
            <p>Este é um lembrete de cobrança referente a <strong>" . htmlspecialchars($conta['responsavel']) . "</strong>.</p>
            <p><strong>Valor:</strong> R$ " . number_format($conta['valor'], 2, ',', '.') . "</p>
            <p><strong>Vencimento:</strong> " . date('d/m/Y', strtotime($conta['data_vencimento'])) . "</p>
            <hr>
            <h3>Dados para Pagamento via PIX</h3>
            <p><strong>Chave PIX:</strong> " . htmlspecialchars($banco['chave_pix'] ?? 'Não informada') . "</p>
            <p><i>Caso prefira, utilize os dados bancários abaixo:</i></p>
            <p><strong>Banco:</strong> " . htmlspecialchars($banco['nome_banco'] ?? '') . "</p>
            <p><strong>Agência:</strong> " . htmlspecialchars($banco['agencia'] ?? 'N/A') . " / <strong>Conta:</strong> " . htmlspecialchars($banco['conta'] ?? '') . "</p>
            <br>
            <p>Qualquer dúvida, estamos à disposição.</p>
        </div>
    ";

    $mail->send();
    $_SESSION['success_message'] = 'E-mail de cobrança enviado com sucesso!';
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "O e-mail não pôde ser enviado. Erro do Mailer: " . $mail->ErrorInfo;
}

header('Location: ../pages/contas_receber.php');
exit();
?>