<?php
require_once '../includes/session_init.php';
require_once '../database.php'; 
require_once '../actions/enviar_email.php'; 

// 1️⃣ Verifica login (lê a sessão corretamente como booleano)
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// --- CORREÇÃO AQUI ---
// A sessão armazena os dados diretamente na raiz, não dentro de 'usuario_logado'
if (empty($_SESSION['email'])) {
    die("Erro: E-mail do usuário não encontrado na sessão (Chave: email).");
}

// 2️⃣ Pega os dados do USUÁRIO logado diretamente da sessão
$email_tenant = $_SESSION['email'];
$nome_tenant  = $_SESSION['nome'] ?? 'Usuário'; // Fallback caso o nome não esteja definido
// --- FIM DA CORREÇÃO ---

// 3️⃣ ✅ CONECTA AO BANCO DE DADOS PRINCIPAL
$conn = getMasterConnection();
if ($conn === null) {
    die("Falha ao conectar ao banco de dados principal.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_usuario_proprietario = null;

try {
    // 4️⃣ Encontra o ID do usuário PROPRIETÁRIO no banco de dados PRINCIPAL
    $stmtOwner = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmtOwner->bind_param("s", $email_tenant);
    $stmtOwner->execute();
    $stmtOwner->bind_result($id_usuario_proprietario);
    $stmtOwner->fetch(); 
    $stmtOwner->close();

    // 5️⃣ Validação
    if ($id_usuario_proprietario === null) {
        die("Erro: Usuário proprietário não encontrado no registro principal.");
    }

    // 6️⃣ Gera token de exclusão
    $token = bin2hex(random_bytes(32));
    $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 7️⃣ Insere na tabela solicitacoes_exclusao
    $stmt = $conn->prepare("INSERT INTO solicitacoes_exclusao (id_usuario, token, expira_em) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_usuario_proprietario, $token, $expira_em);

    if ($stmt->execute()) { 
        // 8️⃣ Envia e-mail com o link
        if (enviarLinkExclusao($email_tenant, $nome_tenant, $token)) {
            header("Location: ../pages/perfil.php?mensagem=Email de confirmação enviado com sucesso!");
        } else {
            header("Location: ../pages/perfil.php?erro=Falha ao enviar o e-mail de confirmação.");
        }
    } else {
        throw new Exception("Falha ao executar a inserção do token.");
    }
    
    $stmt->close();
    $conn->close();

} catch (mysqli_sql_exception $e) {
    error_log("Erro MySQL em enviar_link_exclusao.php: " . $e->getMessage());
    header("Location: ../pages/perfil.php?erro=Erro de banco de dados. Tente novamente mais tarde.");
} catch (Exception $e) {
    error_log("Erro em enviar_link_exclusao.php: " . $e->getMessage());
    header("Location: ../pages/perfil.php?erro=Erro interno ao processar a solicitação.");
}
exit;
?>