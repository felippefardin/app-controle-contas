<?php
session_start();
require_once '../includes/config/config.php';
require_once 'enviar_email.php'; 
require_once '../includes/utils.php'; // Importa Flash Messages

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || !isset($_POST['opcao_cancelamento'])) {
    header('Location: ../pages/login.php');
    exit;
}

$tenant_id = $_SESSION['tenant_id'] ?? null;
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 0;
$opcao = $_POST['opcao_cancelamento']; 

$conn = getMasterConnection();

// 2. Atualiza a intenção de cancelamento
try {
    $sql = "UPDATE tenants SET tipo_cancelamento = ? WHERE tenant_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $opcao, $tenant_id);
        $stmt->execute();
        $stmt->close();
    }
} catch (Exception $e) { }

// 3. Busca dados para o e-mail
$sqlInfo = "SELECT t.*, u.nome, u.email 
            FROM tenants t 
            JOIN usuarios u ON (u.email COLLATE utf8mb4_unicode_ci = t.admin_email COLLATE utf8mb4_unicode_ci)
            WHERE t.tenant_id = ?";
            
$stmtInfo = $conn->prepare($sqlInfo);

if (!$stmtInfo) {
    $sqlInfo = "SELECT t.*, u.nome, u.email 
                FROM tenants t 
                JOIN usuarios u ON (u.email COLLATE utf8mb4_unicode_ci = t.admin_email COLLATE utf8mb4_unicode_ci)
                WHERE t.id = ?";
    $stmtInfo = $conn->prepare($sqlInfo);
}

if ($stmtInfo) {
    $stmtInfo->bind_param("s", $tenant_id);
    $stmtInfo->execute();
    $resultInfo = $stmtInfo->get_result();
    $dados = $resultInfo->fetch_assoc();
    $stmtInfo->close();
} else {
    $dados = []; 
}

// 4. Lógica de Data
if (isset($dados['data_renovacao']) && !empty($dados['data_renovacao'])) {
    $data_fim = date('d/m/Y', strtotime($dados['data_renovacao']));
} else {
    $data_fim = date('d/m/Y', strtotime('+30 days'));
}

$nome_usuario = $dados['nome'] ?? $_SESSION['nome'] ?? 'Cliente';
$email_usuario = $dados['email'] ?? $_SESSION['email'] ?? '';
$nome_empresa = $dados['nome_empresa'] ?? 'Sua Empresa';

// 5. Envio do E-mail
if (!empty($email_usuario)) {
    if ($opcao == 'excluir') {
        $assunto = "Confirmação: Cancelamento e Exclusão Agendada";
        $mensagem = "Olá " . $nome_usuario . ",<br><br>";
        $mensagem .= "O agendamento para exclusão da conta <strong>" . $nome_empresa . "</strong> foi realizado.<br>";
        $mensagem .= "Seu acesso permanecerá disponível até: <strong>$data_fim</strong>.<br>";
        $mensagem .= "Após esta data, todos os seus dados serão apagados permanentemente.<br>";
        $mensagem .= "<br><small>Se mudar de ideia, entre em contato com o suporte antes do prazo.</small>";
        
        enviarEmail($email_usuario, $nome_usuario, $assunto, $mensagem);
    } else {
        $assunto = "Assinatura: Renovação Automática Cancelada";
        $mensagem = "Olá " . $nome_usuario . ",<br><br>";
        $mensagem .= "A renovação automática do plano para <strong>" . $nome_empresa . "</strong> foi cancelada.<br>";
        $mensagem .= "Sua conta permanecerá ativa até <strong>$data_fim</strong>, após isso será suspensa.<br>";
        $mensagem .= "Seus dados serão mantidos caso deseje reativar futuramente.<br>";
        
        enviarEmail($email_usuario, $nome_usuario, $assunto, $mensagem);
    }
}

$conn->close();

set_flash_message('warning', "Solicitação recebida. Seu acesso continua até o fim do ciclo.");
header('Location: ../pages/minha_assinatura.php');
exit;
?>