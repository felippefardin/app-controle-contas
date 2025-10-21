<?php
require_once '../../includes/session_init.php';

// Protege a página para que apenas o proprietário possa acessá-la
if (!isset($_SESSION['proprietario'])) {
    header('Location: ../login.php');
    exit;
}

include('../../database.php');

// Busca todos os usuários principais (que não são sub-usuários)
$sql = "SELECT id, nome, email FROM usuarios WHERE id_criador IS NULL ORDER BY nome ASC";
$result_usuarios = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso Proprietário - Selecionar Conta</title>
    <style>
        /* Você pode usar um estilo semelhante ao da sua página selecionar_usuario.php */
        body { font-family: sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        .user-list { list-style: none; padding: 0; }
        .user-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd; }
        .user-list a { text-decoration: none; background-color: #007bff; color: white; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Selecionar Conta de Usuário</h2>
        <p>Bem-vindo, <?= htmlspecialchars($_SESSION['proprietario']['nome']); ?>. Selecione uma conta para acessar.</p>
        <ul class="user-list">
            <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                <li>
                    <span><?= htmlspecialchars($usuario['nome']) . ' (' . htmlspecialchars($usuario['email']) . ')' ?></span>
                    <a href="../../actions/incorporar_usuario.php?id=<?= $usuario['id'] ?>">Acessar</a>
                </li>
            <?php endwhile; ?>
        </ul>
        <a href="../logout.php">Sair</a>
    </div>
</body>
</html>