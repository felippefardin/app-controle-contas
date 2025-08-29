<?php
session_start();
require_once('../database.php');

$erro = '';
$sucesso = '';

// Verifica se o token foi passado na URL
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token inválido.");
}

// Conecta ao banco principal
$conn = getConnPrincipal();

// Verifica se o token existe e não expirou
$stmt = $conn->prepare("SELECT rs.id, u.id AS usuario_id, u.nome, u.email 
                        FROM recuperacao_senha rs
                        JOIN usuarios u ON rs.usuario_id = u.id
                        WHERE rs.token = ? AND rs.usado = 0 AND rs.expira_em >= NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    die("Token inválido ou expirado.");
}

$stmt->bind_result($rec_id, $usuario_id, $nome, $email);
$stmt->fetch();
$stmt->close();

// Processa o envio do novo formulário de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma_senha'] ?? '';

    if (!$senha || !$confirma) {
        $erro = "Preencha todos os campos.";
    } elseif ($senha !== $confirma) {
        $erro = "As senhas não coincidem.";
    } else {
        // Atualiza a senha no banco de dados
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmtUpdate = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmtUpdate->bind_param("si", $senha_hash, $usuario_id);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Marca o token como usado
        $stmtToken = $conn->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = ?");
        $stmtToken->bind_param("i", $rec_id);
        $stmtToken->execute();
        $stmtToken->close();

        $sucesso = "Senha redefinida com sucesso! Você já pode <a href='login.php'>entrar</a>.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Redefinir senha</title>
<style>
    body { background-color:#121212; color:#eee; font-family:Arial, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
    form { background:#222; padding:25px 30px; border-radius:8px; width:320px; display:flex; flex-direction:column; }
    h2 { text-align:center; color:#00bfff; margin-bottom:20px; }
    input { margin-top:10px; padding:10px; border:none; border-radius:4px; font-size:1rem; }
    input:focus { outline:2px solid #00bfff; background:#333; color:#fff; }
    button { margin-top:20px; padding:12px; border:none; border-radius:5px; background:#007bff; color:#fff; font-weight:bold; cursor:pointer; }
    button:hover { background:#0056b3; }
    .mensagem { text-align:center; padding:10px; border-radius:5px; margin-bottom:15px; }
    .erro { background:#cc4444; }
    .sucesso { background:#27ae60; }
</style>
</head>
<body>
<form method="POST">
    <h2>Redefinir senha</h2>

    <?php if ($erro): ?>
        <div class="mensagem erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mensagem sucesso"><?= $sucesso ?></div>
    <?php else: ?>
        <label for="senha">Nova senha</label>
        <input type="password" id="senha" name="senha" required autofocus>

        <label for="confirma_senha">Confirme a nova senha</label>
        <input type="password" id="confirma_senha" name="confirma_senha" required>

        <button type="submit">Redefinir senha</button>
    <?php endif; ?>
</form>
</body>
</html>
