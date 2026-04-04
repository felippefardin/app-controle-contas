<?php
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$masterConn = getMasterConnection(); // Conexão com a tabela 'tenants'
$tenants = $masterConn->query("SELECT * FROM tenants WHERE status_assinatura != 'cancelado'");

while ($tenant = $tenants->fetch_assoc()) {
    // Configura a conexão específica do banco do tenant
    $conn = new mysqli($tenant['db_host'], $tenant['db_user'], $tenant['db_password'], $tenant['db_database']);

    // Busca contas que vencem hoje ou em 3 dias
    $query = "SELECT cr.*, pf.nome as cliente_nome, pf.email as cliente_email 
              FROM contas_receber cr
              JOIN pessoas_fornecedores pf ON cr.id_pessoa_fornecedor = pf.id
              WHERE cr.status = 'pendente' 
              AND (cr.data_vencimento = CURDATE() OR cr.data_vencimento = DATE_ADD(CURDATE(), INTERVAL 3 DAY))";
    
    $result = $conn->query($query);

    while ($conta = $result->fetch_assoc()) {
        enviarEmailCobranca($tenant, $conta);
    }
    $conn->close();
}

function enviarEmailCobranca($tenant, $conta) {
    $mail = new PHPMailer(true);
    try {
        // Configurações de SMTP (Use as suas credenciais globais ou do tenant)
        $mail->isSMTP();
        $mail->Host = 'smtp.seuservidor.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = $tenant['admin_email'];
        $mail->Password = 'sua_senha';
        $mail->Port = 587;

        $mail->setFrom($tenant['admin_email'], $tenant['nome_empresa']);
        $mail->addAddress($conta['cliente_email'], $conta['cliente_nome']);

        $mail->isHTML(true);
        $mail->Subject = "Lembrete de Pagamento - " . $tenant['nome_empresa'];
        $mail->Body = "Olá <b>{$conta['cliente_nome']}</b>,<br><br>
                       Lembramos que a conta <b>{$conta['descricao']}</b> no valor de 
                       <b>R$ " . number_format($conta['valor'], 2, ',', '.') . "</b> 
                       vence em: <b>" . date('d/m/Y', strtotime($conta['data_vencimento'])) . "</b>.<br>
                       Por favor, desconsidere se já efetuou o pagamento.";

        $mail->send();
    } catch (Exception $e) {
        error_log("Erro ao enviar cobrança para ID {$conta['id']}: {$mail->ErrorInfo}");
    }
}