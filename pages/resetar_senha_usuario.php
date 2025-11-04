<?php
// 1. Carrega o database.php PRIMEIRO
// Ele já carrega o vendor/autoload.php e o .env
require_once '../database.php'; 

// 2. Não precisamos de session_init.php aqui, o usuário não está logado

$erro = '';
$sucesso = '';

$token = $_GET['token'] ?? '';
$tenant_id = $_GET['tenant_id'] ?? 0;

if (!$token || !$tenant_id) {
    die("Link inválido ou incompleto.");
}

// --- LÓGICA DE CONEXÃO ESPECIALIZADA ---
// Agora esta função existe no seu database.php
$conn = getTenantConnectionById($tenant_id); 

if ($conn === null) {
    die("Não foi possível encontrar a conta associada. Verifique se o link está correto.");
}
// --- FIM DA LÓGICA DE CONEXÃO ---


// Verifica se o token existe na tabela USUARIOS
// (Isto depende do Passo 1 - ALTER TABLE)
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE token_reset = ? AND token_expira_em >= NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    die("Token inválido ou expirado.");
}

$stmt->bind_result($usuario_id);
$stmt->fetch();
$stmt->close();

// Processa o envio da nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma_senha'] ?? '';

    if (!$senha || !$confirma) {
        $erro = "Preencha todos os campos.";
    } elseif ($senha !== $confirma) {
        $erro = "As senhas não coincidem.";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Atualiza a senha na tabela 'usuarios' e limpa o token
        $stmtUpdate = $conn->prepare("UPDATE usuarios SET senha = ?, token_reset = NULL, token_expira_em = NULL WHERE id = ?");
        $stmtUpdate->bind_param("si", $senha_hash, $usuario_id);
        
        if ($stmtUpdate->execute()) {
            $sucesso = "Senha redefinida com sucesso! Você já pode <a href='login.php'>fazer login</a>.";
        } else {
            $erro = "Erro ao atualizar a senha.";
        }
        $stmtUpdate->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Redefinir senha de Usuário</title>
<style>
    /* ... (Seus estilos CSS) ... */
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    form { background: #1e1e1e; padding: 2.5rem 2rem; border-radius: 12px; width: 90%; max-width: 400px; }
    h2 { text-align: center; color: #00bfff; margin-bottom: 2rem; }
    label { display: block; margin-bottom: 0.5rem; color: #ccc; }
    .input-wrapper { position: relative; width: 100%; margin-bottom: 1.25rem; }
    input[type="password"] { width: 100%; padding: 12px; border: 1px solid #444; border-radius: 6px; font-size: 1rem; background-color: #2a2a2a; color: #eee; }
    button { margin-top: 1rem; padding: 12px; border: none; border-radius: 6px; background: #007bff; color: #fff; font-weight: bold; font-size: 1rem; cursor: pointer; }
    .mensagem { text-align: center; padding: 12px; border-radius: 6px; margin-bottom: 1.5rem; }
    .erro { background: #5a1a1a; color: #ffc4c4; border: 1px solid #a03030; }
    .sucesso { background: #1a5a3a; color: #c4ffc4; border: 1px solid #30a050; }
    .sucesso a { color: #fff; font-weight: bold; }
</style>
</head>
<body>
<form method="POST">
    <h2>Redefinir senha de Usuário</h2>

    <?php if ($erro): ?>
        <div class="mensagem erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mensagem sucesso"><?= $sucesso ?></div>
    <?php else: ?>
        <label for="senha">Nova senha</label>
        <div class="input-wrapper">
            <input type="password" id="senha" name="senha" required autofocus>
        </div>

        <label for="confirma_senha">Confirme a nova senha</label>
        <div class="input-wrapper">
            <input type="password" id="confirma_senha" name="confirma_senha" required>
        </div>

        <button type="submit">Redefinir senha</button>
    <?php endif; ?>
</form>
</body>
</html>