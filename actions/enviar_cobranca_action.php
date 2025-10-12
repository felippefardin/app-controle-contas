<?php
session_start();
include('../database.php');

// Inclui os arquivos do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica se o autoload do Composer existe para carregar as bibliotecas e o .env
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';

    // Carrega as variáveis de ambiente do arquivo .env na raiz do projeto
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        // Se o .env não for encontrado, encerra a execução com uma mensagem clara.
        die("ERRO CRÍTICO: Não foi possível encontrar o arquivo .env. Certifique-se de que ele está na pasta raiz do seu projeto.");
    }

} else {
    // Se não usar Composer, o .env não pode ser carregado.
    die("ERRO CRÍTICO: O arquivo vendor/autoload.php não foi encontrado. Por favor, execute 'composer install' para instalar as dependências, incluindo o phpdotenv.");
}


// Validação de sessão de usuário
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Validação do método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/contas_receber.php');
    exit;
}

// Coleta e validação dos dados do formulário
$id_usuario = $_SESSION['usuario']['id'];
$id_conta_receber = filter_input(INPUT_POST, 'id_conta', FILTER_VALIDATE_INT);
$pessoa_id = filter_input(INPUT_POST, 'pessoa_id', FILTER_VALIDATE_INT);
$conta_bancaria_id = filter_input(INPUT_POST, 'conta_bancaria_id', FILTER_VALIDATE_INT);
$email_destinatario = filter_input(INPUT_POST, 'email_destinatario', FILTER_VALIDATE_EMAIL);

// Se algum dado essencial for inválido, redireciona com erro
if (!$id_conta_receber || !$pessoa_id || !$conta_bancaria_id || !$email_destinatario) {
    $_SESSION['error_message'] = "Dados inválidos ou faltando. Tente novamente.";
    header('Location: ../pages/contas_receber.php');
    exit;
}

// --- Busca de dados no banco de dados ---

// 1. Buscar dados da conta a receber
$stmt_conta = $conn->prepare("SELECT * FROM contas_receber WHERE id = ? AND usuario_id = ?");
$stmt_conta->bind_param("ii", $id_conta_receber, $id_usuario);
$stmt_conta->execute();
$conta = $stmt_conta->get_result()->fetch_assoc();
$stmt_conta->close();

// 2. Buscar dados da pessoa/cliente
$stmt_pessoa = $conn->prepare("SELECT * FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
$stmt_pessoa->bind_param("ii", $pessoa_id, $id_usuario);
$stmt_pessoa->execute();
$pessoa = $stmt_pessoa->get_result()->fetch_assoc();
$stmt_pessoa->close();

// 3. Buscar dados da conta bancária
$stmt_banco = $conn->prepare("SELECT * FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
$stmt_banco->bind_param("ii", $conta_bancaria_id, $id_usuario);
$stmt_banco->execute();
$banco = $stmt_banco->get_result()->fetch_assoc();
$stmt_banco->close();

// Verifica se todos os dados foram encontrados
if (!$conta || !$pessoa || !$banco) {
    $_SESSION['error_message'] = "Não foi possível encontrar todos os dados para gerar a cobrança.";
    header('Location: ../pages/contas_receber.php');
    exit;
}

// --- Lógica de E-mail com PHPMailer ---
$mail = new PHPMailer(true);

try {
    // Configurações do servidor a partir do .env
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
    $mail->Port       = (int)$_ENV['MAIL_PORT']; // Converte a porta para inteiro

    // Remetente e Destinatário
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($email_destinatario, $pessoa['nome']);
    $mail->CharSet = 'UTF-8'; // Garante a codificação correta dos caracteres

    // Anexo (Boleto)
    if (isset($_FILES['boleto_anexo']) && $_FILES['boleto_anexo']['error'] == UPLOAD_ERR_OK) {
        $mail->addAttachment($_FILES['boleto_anexo']['tmp_name'], $_FILES['boleto_anexo']['name']);
    }

    $descricaoConta = htmlspecialchars($conta['responsavel'] ?? 'Serviço/Produto');

    // Conteúdo do E-mail
    $mail->isHTML(true);
    $mail->Subject = 'Lembrete de Cobrança: ' . $descricaoConta;
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h1>Olá, " . htmlspecialchars($pessoa['nome']) . "!</h1>
            <p>Este é um lembrete de cobrança referente a <strong>" . $descricaoConta . "</strong>.</p>
            <p><strong>Valor:</strong> R$ " . number_format($conta['valor'], 2, ',', '.') . "</p>
            <p><strong>Vencimento:</strong> " . date('d/m/Y', strtotime($conta['data_vencimento'])) . "</p>
            <hr>
            <h3>Dados para Pagamento via PIX</h3>
            <p><strong>Chave PIX:</strong> " . htmlspecialchars($banco['chave_pix'] ?? 'Não informada') . "</p>
            <p><i>Caso prefira, utilize os dados bancários abaixo:</i></p>
            <p><strong>Banco:</strong> " . htmlspecialchars($banco['nome_banco'] ?? '') . "</p>
            <p><strong>Agência:</strong> " . htmlspecialchars($banco['agencia'] ?? 'N/A') . "</p>
            <p><strong>Conta:</strong> " . htmlspecialchars($banco['conta'] ?? '') . "</p>
            <br>
            <p>Qualquer dúvida, estamos à disposição.</p>
        </div>
    ";

    $mail->send();
    $_SESSION['success_message'] = 'E-mail de cobrança enviado com sucesso!';
    
} catch (Exception $e) {
    // Redireciona com uma mensagem de erro clara para depuração
    $_SESSION['error_message'] = "O e-mail não pôde ser enviado. Erro: " . $mail->ErrorInfo;
}

// Redirecionamento final
header('Location: ../pages/contas_receber.php');
exit();
?>