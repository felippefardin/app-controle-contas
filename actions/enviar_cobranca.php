<?php
session_start();
include('../database.php');
require_once('../lib/fpdf/fpdf.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/../lib/PHPMailer/SMTP.php';
require __DIR__ . '/../lib/PHPMailer/Exception.php';

$conta_id = $_POST['conta_id'] ?? null;
$email = $_POST['email'] ?? null;
$pix = $_POST['pix'] ?? null; // Recebe a chave PIX do modal

if (!$conta_id || !$email) {
    die('Dados insuficientes.');
}

// Buscar dados da conta
$stmt = $conn->prepare("SELECT * FROM contas_receber WHERE id = ?");
$stmt->bind_param("i", $conta_id);
$stmt->execute();
$result = $stmt->get_result();
$conta = $result->fetch_assoc();
$stmt->close();

if (!$conta) {
    die('Conta não encontrada.');
}

$responsavel = $conta['responsavel'];
$numero = $conta['numero'];
$valor = $conta['valor'];
$data_vencimento = $conta['data_vencimento'];

// Criar diretório tmp se não existir
$tmpDir = __DIR__ . '/../tmp';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

// Gerar PDF da cobrança
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, "Cobrança - Conta a Receber", 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, "Olá $responsavel,", 0, 1);
$pdf->Cell(0, 8, "Número da Conta: $numero", 0, 1);
$pdf->Cell(0, 8, "Valor: R$ " . number_format($valor, 2, ',', '.'), 0, 1);
$pdf->Cell(0, 8, "Vencimento: " . date('d/m/Y', strtotime($data_vencimento)), 0, 1);

// Adiciona PIX se preenchido
if (!empty($pix)) {
    $pdf->Cell(0, 8, "PIX: $pix", 0, 1);
}

$pdfFile = $tmpDir . "/cobranca_$conta_id.pdf";
$pdf->Output('F', $pdfFile);

// Enviar e-mail com PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'felippefardin@gmail.com';
    $mail->Password   = 'mwtz cwor zfji yygw'; // App Password do Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('felippefardin@gmail.com', 'App Controle de Contas');
    $mail->addAddress($email, $responsavel);

    $mail->isHTML(true);
    $mail->Subject = "Cobrança - Conta #$numero";
    $mail->Body    = "
        Olá <b>$responsavel</b>,<br><br>
        Segue sua cobrança:<br>
        <b>Numero da Conta:</b> $numero<br>
        <b>Valor:</b> R$ " . number_format($valor, 2, ',', '.') . "<br>
        <b>Vencimento:</b> " . date('d/m/Y', strtotime($data_vencimento)) . "<br>" .
        (!empty($pix) ? "<b>PIX:</b> $pix<br>" : "") . "
        <br>PDF anexo para impressão ou registro.
    ";
    $mail->addAttachment($pdfFile);

    $mail->send();
    echo "Cobrança enviada com sucesso para $responsavel ($email)!";
} catch (Exception $e) {
    echo "Erro ao enviar cobrança: {$mail->ErrorInfo}";
}
