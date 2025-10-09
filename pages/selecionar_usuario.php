<?php
session_start();

// Se o usuário principal não estiver logado, redireciona para o login
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: login.php');
    exit;
}

include('../database.php');

$usuario_principal = $_SESSION['usuario_principal'];

// Busca o usuário principal e seus usuários secundários
$sql = "SELECT id, nome FROM usuarios WHERE id = ? OR id_criador = ? ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $usuario_principal['id'], $usuario_principal['id']);
$stmt->execute();
$result_usuarios = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Selecionar Usuário - App Controle de Contas</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        .selection-container {
            padding: 40px;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 191, 255, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        h2 {
            color: #00bfff;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #bbb;
        }
        select, input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #444;
            background-color: #333;
            color: #eee;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #00bfff;
            color: #121212;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0095cc;
        }
        .mensagem-erro {
            background-color: #dc3545;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="selection-container">
        <h2>Selecionar Usuário</h2>
        
        <?php if (isset($_SESSION['erro_selecao'])): ?>
            <div class="mensagem-erro"><?= htmlspecialchars($_SESSION['erro_selecao']); ?></div>
            <?php unset($_SESSION['erro_selecao']); ?>
        <?php endif; ?>

        <form action="../actions/trocar_usuario.php" method="POST">
            <div class="form-group">
                <label for="usuario_id">Acessar como:</label>
                <select name="usuario_id" id="usuario_id" required>
                    <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                        <option value="<?= $usuario['id'] ?>">
                            <?= htmlspecialchars($usuario['nome']) ?>
                            <?= ($usuario['id'] === $usuario_principal['id']) ? '(Principal)' : '' ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="senha">Senha do usuário selecionado:</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit">Acessar Sistema</button>
        </form>
    </div>
</body>
</html>