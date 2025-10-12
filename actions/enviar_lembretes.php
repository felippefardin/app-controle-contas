<?php
require_once '../database.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$hoje = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+7 days'));

function enviarEmail($email, $nome, $assunto, $corpoHtml, $corpoAlt) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'felippefardin@gmail.com';
        $mail->Password   = 'kwrsaszsoyblypcf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('felippefardin@gmail.com', 'Sistema de Contas');
        $mail->addAddress($email, $nome);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpoHtml;
        $mail->AltBody = $corpoAlt;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Erro ao enviar e-mail para {$nome} ({$email}): {$mail->ErrorInfo}<br>";
        return false;
    }
}

// --------------------- CONTAS A PAGAR ---------------------
$stmt = $conn->prepare("
    SELECT cp.*, u.nome AS usuario_nome, u.email AS usuario_email
    FROM contas_pagar cp
    JOIN usuarios u ON cp.usuario_id = u.id
    WHERE cp.data_vencimento BETWEEN ? AND ? AND cp.status = 'pendente'
");
$stmt->bind_param("ss", $hoje, $data_limite);
$stmt->execute();
$result = $stmt->get_result();
$contasPagar = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "<h3>Contas a Pagar (Automático)</h3>";
if (count($contasPagar) === 0) {
    echo "Nenhuma conta a pagar encontrada.<br>";
} else {
    foreach ($contasPagar as $conta) {
        $nome = $conta['usuario_nome'];
        $email = $conta['usuario_email'];
        $descricao = $conta['fornecedor']; // Coluna existente
        $valor = number_format($conta['valor'], 2, ',', '.');
        $vencimento = date('d/m/Y', strtotime($conta['data_vencimento']));

        $assunto = "🔔 Lembrete de Conta a Pagar: $descricao";
        $corpoHtml = "Olá $nome,<br><br>Este é um lembrete de que a conta <b>$descricao</b> no valor de <b>R$ $valor</b> vence em <b>$vencimento</b>.<br><br>Atenciosamente,<br>Seu Sistema de Controle de Contas.";
        $corpoAlt  = "Olá $nome, Este é um lembrete de que a conta '$descricao' no valor de R$ $valor vence em $vencimento. Atenciosamente, Seu Sistema de Controle de Contas.";

        if (enviarEmail($email, $nome, $assunto, $corpoHtml, $corpoAlt)) {
            echo "Lembrete enviado para $nome ($email) - Conta a Pagar: $descricao<br>";
        }
    }
}

// --------------------- CONTAS A RECEBER ---------------------
$stmt = $conn->prepare("
    SELECT cr.*, u.nome AS cliente_nome, u.email AS cliente_email
    FROM contas_receber cr
    JOIN usuarios u ON cr.usuario_id = u.id
    WHERE cr.data_vencimento BETWEEN ? AND ? AND cr.status = 'pendente'
");
$stmt->bind_param("ss", $hoje, $data_limite);
$stmt->execute();
$result = $stmt->get_result();
$contasReceber = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "<h3>Contas a Receber (Automático)</h3>";
if (count($contasReceber) === 0) {
    echo "Nenhuma conta a receber encontrada.<br>";
} else {
    foreach ($contasReceber as $conta) {
        $nome = $conta['cliente_nome'];
        $email = $conta['cliente_email'];
        $descricao = isset($conta['descricao']) ? $conta['descricao'] : "Conta #" . $conta['id']; // fallback se não existir
        $valor = number_format($conta['valor'], 2, ',', '.');
        $vencimento = date('d/m/Y', strtotime($conta['data_vencimento']));

        $assunto = "🔔 Lembrete de Conta a Receber: $descricao";
        $corpoHtml = "Olá $nome,<br><br>Este é um lembrete de que a sua conta <b>$descricao</b> no valor de <b>R$ $valor</b> vence em <b>$vencimento</b>.<br><br>Atenciosamente,<br>Seu Sistema de Controle de Contas.";
        $corpoAlt  = "Olá $nome, Este é um lembrete de que a sua conta '$descricao' no valor de R$ $valor vence em $vencimento. Atenciosamente, Seu Sistema de Controle de Contas.";

        if (enviarEmail($email, $nome, $assunto, $corpoHtml, $corpoAlt)) {
            echo "Lembrete enviado para $nome ($email) - Conta a Receber: $descricao<br>";
        }
    }
}

// --------------------- ENVIO MANUAL ---------------------
if (isset($_GET['tipo'], $_GET['id'])) {
    $tipo = $_GET['tipo']; // 'pagar' ou 'receber'
    $id = intval($_GET['id']);

    if ($tipo === 'pagar') {
        $stmt = $conn->prepare("
            SELECT cp.*, u.nome AS usuario_nome, u.email AS usuario_email
            FROM contas_pagar cp
            JOIN usuarios u ON cp.usuario_id = u.id
            WHERE cp.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $conta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($conta) {
            $nome = $conta['usuario_nome'];
            $email = $conta['usuario_email'];
            $descricao = $conta['fornecedor'];
            $valor = number_format($conta['valor'], 2, ',', '.');
            $vencimento = date('d/m/Y', strtotime($conta['data_vencimento']));

            enviarEmail($email, $nome, "🔔 Lembrete Manual: $descricao", 
                "Olá $nome,<br>Este é um lembrete manual da conta <b>$descricao</b> no valor de <b>R$ $valor</b> que vence em <b>$vencimento</b>.", 
                "Olá $nome, Este é um lembrete manual da conta '$descricao' no valor de R$ $valor que vence em $vencimento."
            );
            echo "Lembrete manual enviado para $nome ($email) - Conta a Pagar: $descricao<br>";
        }
    } elseif ($tipo === 'receber') {
        $stmt = $conn->prepare("
            SELECT cr.*, u.nome AS cliente_nome, u.email AS cliente_email
            FROM contas_receber cr
            JOIN usuarios u ON cr.usuario_id = u.id
            WHERE cr.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $conta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($conta) {
            $nome = $conta['cliente_nome'];
            $email = $conta['cliente_email'];
            $descricao = isset($conta['descricao']) ? $conta['descricao'] : "Conta #" . $conta['id'];
            $valor = number_format($conta['valor'], 2, ',', '.');
            $vencimento = date('d/m/Y', strtotime($conta['data_vencimento']));

            enviarEmail($email, $nome, "🔔 Lembrete Manual: $descricao", 
                "Olá $nome,<br>Este é um lembrete manual da sua conta <b>$descricao</b> no valor de <b>R$ $valor</b> que vence em <b>$vencimento</b>.", 
                "Olá $nome, Este é um lembrete manual da sua conta '$descricao' no valor de R$ $valor que vence em $vencimento."
            );
            echo "Lembrete manual enviado para $nome ($email) - Conta a Receber: $descricao<br>";
        }
    }
}
?>
