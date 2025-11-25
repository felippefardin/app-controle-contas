<?php
// cron/processar_cancelamentos.php
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../actions/enviar_email.php';

$conn = getMasterConnection();
$hoje = date('Y-m-d');

// Buscar usuários vencidos com cancelamento agendado
$sql = "SELECT * FROM usuarios WHERE data_validade < ? AND tipo_cancelamento IS NOT NULL AND status != 'suspenso'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hoje);
$stmt->execute();
$result = $stmt->get_result();

while ($user = $result->fetch_assoc()) {
    
    if ($user['tipo_cancelamento'] == 'desativar') {
        // Opção 1: Apenas Suspender
        $upd = $conn->prepare("UPDATE usuarios SET status = 'suspenso', tipo_cancelamento = NULL WHERE id = ?");
        $upd->bind_param("i", $user['id']);
        $upd->execute();
        $upd->close();

        $assunto = "Conta Suspensa";
        $msg = "Olá " . $user['nome'] . ",<br>Sua conta foi suspensa pois o período vigente acabou e você solicitou o não cancelamento automático. Para reativar, acesse o login.";
        
        // Chamada corrigida pela existência da função
        enviarEmail($user['email'], $user['nome'], $assunto, $msg);

    } elseif ($user['tipo_cancelamento'] == 'excluir') {
        // Opção 2: Exclusão Total
        // Certifique-se de ter lógica para limpar dados dependentes (FKs) se necessário antes de deletar
        $del = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $del->bind_param("i", $user['id']);
        
        if ($del->execute()) {
            $assunto = "Conta Excluída";
            $msg = "Olá " . $user['nome'] . ",<br>Conforme solicitado, sua conta e seus dados foram excluídos permanentemente.";
            
            // Chamada corrigida pela existência da função
            enviarEmail($user['email'], $user['nome'], $assunto, $msg);
        }
        $del->close();
    }
}

$stmt->close();
$conn->close();

echo "Processamento concluído.";
?>