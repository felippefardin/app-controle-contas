<?php
require_once '../../includes/session_init.php';
require_once '../../database.php';

// 🔒 Verifica se é super admin logado
if (!isset($_SESSION['super_admin'])) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';
$classeMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual = trim($_POST['senha_atual']);
    $novaSenha = trim($_POST['nova_senha']);
    $confirmar = trim($_POST['confirmar_senha']);

    $master = getMasterConnection();
    $email = $_SESSION['super_admin']['email'];

    // Busca o admin no banco
    $stmt = $master->prepare("SELECT senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin || !password_verify($senhaAtual, $admin['senha'])) {
        $mensagem = 'Senha atual incorreta.';
        $classeMsg = 'erro';
    } elseif (strlen($novaSenha) < 6) {
        $mensagem = 'A nova senha deve ter pelo menos 6 caracteres.';
        $classeMsg = 'erro';
    } elseif ($novaSenha !== $confirmar) {
        $mensagem = 'A confirmação não corresponde à nova senha.';
        $classeMsg = 'erro';
    } else {
        // Atualiza senha no banco
        $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $update = $master->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $update->bind_param("ss", $novoHash, $email);
        $update->execute();
        $update->close();

        $mensagem = 'Senha atualizada com sucesso!';
        $classeMsg = 'sucesso';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha - Super Admin</title>
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background-color: #1f1f1f;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px #00bfff;
            width: 400px;
            display: flex;
            flex-direction: column;
        }
        h2 {
            text-align: center;
            color: #00bfff;
            margin-bottom: 20px;
        }
        label {
            margin-top: 10px;
            font-weight: bold;
        }
        input {
            padding: 10px;
            margin-top: 5px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            margin-top: 20px;
            padding: 12px;
            background-color: #00bfff;
            border: none;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #008ccc;
        }
        .mensagem {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .erro { background-color: #cc4444; color: white; }
        .sucesso { background-color: #4CAF50; color: white; }
        a {
            color: #00bfff;
            text-decoration: none;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Redefinir Senha</h2>

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $classeMsg ?>"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <label for="senha_atual">Senha Atual</label>
        <input type="password" id="senha_atual" name="senha_atual" required>

        <label for="nova_senha">Nova Senha</label>
        <input type="password" id="nova_senha" name="nova_senha" required>

        <label for="confirmar_senha">Confirmar Nova Senha</label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" required>

        <button type="submit">Atualizar Senha</button>

        <a href="dashboard.php">← Voltar ao Painel</a>
    </form>
</body>
</html>
