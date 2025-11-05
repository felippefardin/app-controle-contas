<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Carrega as funções de conexão
require_once '../actions/enviar_email.php'; 

// 1️⃣ Verifica login (lê a sessão do tenant)
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

$usuario_logado = $_SESSION['usuario_logado'];

// Se a sessão não tiver e-mail, não podemos continuar
if (empty($usuario_logado['email'])) {
    die("Erro: E-mail do usuário não encontrado na sessão.");
}

// 2️⃣ Pega os dados do USUÁRIO logado
$email_tenant = $usuario_logado['email'];
$nome_tenant  = $usuario_logado['nome'];

// 3️⃣ ✅ CONECTA AO BANCO DE DADOS PRINCIPAL
//    Este script deve interagir com as tabelas 'usuarios' e 'solicitacoes_exclusao'
//    do banco 'app_controle_contas', e NÃO do tenant.
$conn = getMasterConnection();
if ($conn === null) {
    die("Falha ao conectar ao banco de dados principal.");
}
// Força o mysqli a reportar erros como exceções
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_usuario_proprietario = null;

try {
    // 4️⃣ Encontra o ID do usuário PROPRIETÁRIO no banco de dados PRINCIPAL
    $stmtOwner = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmtOwner->bind_param("s", $email_tenant);
    $stmtOwner->execute();
    $stmtOwner->bind_result($id_usuario_proprietario);
    $stmtOwner->fetch(); // Busca o resultado
    $stmtOwner->close();

    // 5️⃣ Validação (Verifica se o ID foi encontrado)
    if ($id_usuario_proprietario === null) {
        // Isso acontece se o usuário (ex: felippefardin@gmail.com) não existir na
        // tabela 'usuarios' do banco 'app_controle_contas'.
        // Execute a Parte 1 da correção.
        die("Erro: Usuário proprietário não encontrado no registro principal. Execute a correção manual no banco de dados (Parte 1).");
    }

    // 6️⃣ Gera token de exclusão
    $token = bin2hex(random_bytes(32));
    $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 7️⃣ Insere na tabela solicitacoes_exclusao (usando o $conn PRINCIPAL)
    $stmt = $conn->prepare("INSERT INTO solicitacoes_exclusao (id_usuario, token, expira_em) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_usuario_proprietario, $token, $expira_em);

    if ($stmt->execute()) { // Esta era a linha 48 que falhava
        // 8️⃣ Envia e-mail com o link
        if (enviarLinkExclusao($email_tenant, $nome_tenant, $token)) {
            header("Location: ../pages/perfil.php?mensagem=Email de confirmação enviado com sucesso!");
        } else {
            header("Location: ../pages/perfil.php?erro=Falha ao enviar o e-mail de confirmação.");
        }
    } else {
        // Este else é desnecessário com mysqli_report, mas mantido por segurança
        throw new Exception("Falha ao executar a inserção do token.");
    }
    
    $stmt->close();
    $conn->close();

} catch (mysqli_sql_exception $e) {
    // Captura erros de SQL (como a Foreign Key)
    error_log("Erro MySQL em enviar_link_exclusao.php: " . $e->getMessage());
    header("Location: ../pages/perfil.php?erro=Erro de banco de dados ao processar a solicitação. Verifique o log.");
} catch (Exception $e) {
    // Captura outros erros
    error_log("Erro em enviar_link_exclusao.php: " . $e->getMessage());
    header("Location: ../pages/perfil.php?erro=Erro interno ao processar a solicitação.");
}
exit;
?>