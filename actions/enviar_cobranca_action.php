<?php
session_start();
include('../database.php');

// Inclui os arquivos do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica se o autoload do Composer existe
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} else {
    // Caminho alternativo se você não usa o Composer
    require '../lib/PHPMailer/src/Exception.php';
    require '../lib/PHPMailer/src/PHPMailer.php';
    require '../lib/PHPMailer/src/SMTP.php';
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id'];
    $id_conta_receber = $_POST['id_conta'];
    $pessoa_id = $_POST['pessoa_id'];
    $conta_bancaria_id = $_POST['conta_bancaria_id'];
    $email_destinatario = filter_var($_POST['email_destinatario'], FILTER_VALIDATE_EMAIL);

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

    if (!$conta || !$pessoa || !$banco || !$email_destinatario) {
        header('Location: ../pages/contas_receber.php?erro=dados_invalidos');
        exit;
    }

    // 4. Lógica de E-mail com PHPMailer
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
        $mail->addAddress($email_destinatario, $pessoa['nome']);

        // Anexo (Boleto)
        if (isset($_FILES['boleto_anexo']) && $_FILES['boleto_anexo']['error'] == UPLOAD_ERR_OK) {
            $mail->addAttachment($_FILES['boleto_anexo']['tmp_name'], $_FILES['boleto_anexo']['name']);
        }

        // --- CORREÇÃO AQUI: Usando as chaves corretas e tratando valores nulos ---
        $descricaoConta = htmlspecialchars($conta['responsavel'] ?? 'Serviço/Produto');

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = 'Lembrete de Cobranca: ' . $descricaoConta;
        $mail->Body    = "
            <h1>Olá, " . htmlspecialchars($pessoa['nome']) . "!</h1>
            <p>Segue os detalhes da cobrança referente a <strong>" . $descricaoConta . "</strong>.</p>
            <p><strong>Valor:</strong> R$ " . number_format($conta['valor'], 2, ',', '.') . "</p>
            <p><strong>Vencimento:</strong> " . date('d/m/Y', strtotime($conta['data_vencimento'])) . "</p>
            <hr>
            <h3>Dados para Pagamento</h3>
            <p><strong>Banco:</strong> " . htmlspecialchars($banco['nome_banco'] ?? '') . "</p>
            <p><strong>Agência:</strong> " . htmlspecialchars($banco['agencia'] ?? 'N/A') . "</p>
            <p><strong>Conta:</strong> " . htmlspecialchars($banco['conta'] ?? '') . "</p>
            <p><strong>Chave PIX:</strong> " . htmlspecialchars($banco['chave_pix'] ?? 'Não informada') . "</p>
        ";

        $mail->send();
        $_SESSION['success_message'] = 'E-mail de cobrança enviado com sucesso!';
        header('Location: ../pages/contas_receber.php');
        
    } catch (Exception $e) {
        // Redireciona com uma mensagem de erro clara para depuração
        header('Location: ../pages/contas_receber.php?erro=email_falhou&msg=' . urlencode($mail->ErrorInfo));
    }

} else {
    header('Location: ../pages/contas_receber.php');
}
exit();
?>