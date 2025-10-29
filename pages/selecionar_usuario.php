<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Incluído no início

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO CORRETA
$usuario_logado = $_SESSION['usuario_logado'];
$id_usuario_atual = $usuario_logado['id'];

// ✅ 3. BUSCA TODOS OS USUÁRIOS DO CLIENTE (TENANT) ATUAL
// A consulta é simplificada, pois todos os usuários no banco de dados do tenant pertencem a ele.
$sql = "SELECT id, nome, nivel_acesso FROM usuarios ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result_usuarios = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Selecionar Usuário - App Controle de Contas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #bbb;
        }
        select, input[type="password"] {
            width: 100%;
            padding: 12px;
            padding-right: 40px;
            border-radius: 5px;
            border: 1px solid #444;
            background-color: #333;
            color: #eee;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            top: 37px;
            right: 12px;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .toggle-password:hover {
            color: #00bfff;
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
                        <option value="<?= $usuario['id'] ?>" <?= ($usuario['id'] === $id_usuario_atual) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usuario['nome']) ?>
                            <?= ($usuario['nivel_acesso'] === 'proprietario') ? '(Principal)' : '' ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="senha">Senha do usuário selecionado:</label>
                <input type="password" name="senha" id="senha" required>
                <i class="fas fa-eye toggle-password" id="toggleSenha"></i>
            </div>
            <button type="submit">Acessar Sistema</button>
        </form>
    </div>

    <script>
    const toggleSenha = document.getElementById('toggleSenha');
    const inputSenha = document.getElementById('senha');

    toggleSenha.addEventListener('click', () => {
      const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
      inputSenha.setAttribute('type', tipo);
      toggleSenha.classList.toggle('fa-eye');
      toggleSenha.classList.toggle('fa-eye-slash');
    });
  </script>
</body>
</html>