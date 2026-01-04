<?php
require_once '../includes/session_init.php';
require_once '../database.php'; 

// Correção do caminho do include
require_once __DIR__ . '/enviar_email.php'; 

// 1. Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. Valida sessão
if (empty($_SESSION['email'])) {
    // Redireciona com erro se a sessão estiver corrompida
    header("Location: ../pages/perfil.php?erro=Sessão inválida. Faça login novamente.");
    exit;
}

$email_tenant = $_SESSION['email'];
$nome_tenant  = $_SESSION['nome'] ?? 'Usuário';

// 3. Conexão
$conn = getMasterConnection();
if ($conn === null) {
    header("Location: ../pages/perfil.php?erro=Erro de conexão com o banco de dados.");
    exit;
}

try {
    // 4. Busca ID Proprietário
    $stmtOwner = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmtOwner->bind_param("s", $email_tenant);
    $stmtOwner->execute();
    $stmtOwner->bind_result($id_usuario_proprietario);
    
    if (!$stmtOwner->fetch()) {
        $stmtOwner->close();
        throw new Exception("Usuário não encontrado no banco de dados.");
    }
    $stmtOwner->close();

    // 5. Gera Token
    $token = bin2hex(random_bytes(32));
    $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Remove solicitações antigas deste usuário para não acumular lixo
    $stmtDel = $conn->prepare("DELETE FROM solicitacoes_exclusao WHERE id_usuario = ?");
    $stmtDel->bind_param("i", $id_usuario_proprietario);
    $stmtDel->execute();
    $stmtDel->close();

    // 6. Insere nova solicitação
    $stmt = $conn->prepare("INSERT INTO solicitacoes_exclusao (id_usuario, token, expira_em) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_usuario_proprietario, $token, $expira_em);

    if ($stmt->execute()) { 
        // 7. Tenta enviar o e-mail
        if (enviarLinkExclusao($email_tenant, $nome_tenant, $token)) {
            header("Location: ../pages/perfil.php?mensagem=Email de confirmação enviado! Verifique sua caixa de entrada e spam.");
        } else {
            // Se falhar o envio, avisa o usuário
            header("Location: ../pages/perfil.php?erro=Falha técnica ao enviar e-mail. Verifique o log de erros.");
        }
    } else {
        throw new Exception("Falha ao salvar token no banco.");
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Erro Crítico (Link Exclusão): " . $e->getMessage());
    header("Location: ../pages/perfil.php?erro=Ocorreu um erro interno. Tente novamente.");
}
exit;
?>