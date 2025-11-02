<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../actions/enviar_email.php'; 

// 1️⃣ Verifica login (usa 'usuario_logado', que é o TENANT)
//    Esta era a causa do redirecionamento para login.php
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

$usuario_tenant = $_SESSION['usuario_logado'];

// Se a sessão não tiver id de TENANT válido, interrompe
if (empty($usuario_tenant['id'])) {
    die("Erro: ID do tenant não encontrado na sessão.");
}

// Dados do TENANT (para enviar o e-mail)
$tenant_id    = $usuario_tenant['id'];
$email_tenant = $usuario_tenant['email'];
$nome_tenant  = $usuario_tenant['nome'];

// 2️⃣ Busca o ID do USUÁRIO proprietário (owner) com base no TENANT ID
//    Precisamos disso para satisfazer a foreign key da tabela 'solicitacoes_exclusao'.
$id_usuario_proprietario = null;
$stmtOwner = $conn->prepare("SELECT id FROM usuarios WHERE tenant_id = ? AND nivel_acesso = 'proprietario' LIMIT 1");
$stmtOwner->bind_param("i", $tenant_id);
$stmtOwner->execute();
$stmtOwner->bind_result($id_usuario_proprietario);

if (!$stmtOwner->fetch()) {
    // Se não encontrar um proprietário, não pode continuar
    $stmtOwner->close();
    // ⚠️ Se você vir este erro, verifique sua tabela 'usuarios'
    die("Erro: Nenhum usuário proprietário (owner) foi encontrado para este tenant.");
}
$stmtOwner->close();

// 3️⃣ Gera token de exclusão
$token = bin2hex(random_bytes(32));
$expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 4️⃣ Insere na tabela solicitacoes_exclusao (usando o ID do USUÁRIO proprietário)
$stmt = $conn->prepare("INSERT INTO solicitacoes_exclusao (id_usuario, token, expira_em) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id_usuario_proprietario, $token, $expira_em);

if ($stmt->execute()) {
    // 5️⃣ Envia e-mail com o link (usando e-mail e nome do TENANT)
    if (enviarLinkExclusao($email_tenant, $nome_tenant, $token)) {
        header("Location: ../pages/perfil.php?mensagem=Email de confirmação enviado com sucesso!");
    } else {
        header("Location: ../pages/perfil.php?erro=Falha ao enviar o e-mail de confirmação.");
    }
} else {
    error_log("Erro MySQL: " . $stmt->error);
    header("Location: ../pages/perfil.php?erro=Erro ao processar a solicitação. Verifique o log.");
}

$stmt->close();
$conn->close();
exit;
?>