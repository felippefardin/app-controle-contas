<?php
include('../includes/header.php');
include('../database.php');
require_once '../includes/session_init.php';

if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Usuário não informado.";
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// Pega dados do usuário para preencher o formulário
$stmt = $conn->prepare("SELECT nome, cpf, telefone, email, perfil FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result(); // Adicionado para verificar o número de linhas

if ($stmt->num_rows > 0) {
    $stmt->bind_result($nome, $cpf, $telefone, $email, $perfil);
    $stmt->fetch();
} else {
    // Se nenhum usuário for encontrado, define as variáveis como vazias
    $nome = $cpf = $telefone = $email = $perfil = "";
    echo "Usuário não encontrado.";
    // Você pode redirecionar o usuário de volta para a página de usuários se preferir
    // header('Location: usuarios.php?erro=usuario_nao_encontrado');
    // exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_post = $_POST['nome'];
    $cpf_post = $_POST['cpf'];
    $telefone_post = $_POST['telefone'];
    $email_post = $_POST['email'];
    $perfil_post = $_POST['perfil'];

    $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, cpf = ?, telefone = ?, email = ?, perfil = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $nome_post, $cpf_post, $telefone_post, $email_post, $perfil_post, $id);
    
    if ($stmt->execute()) {
        header('Location: usuarios.php?sucesso=1');
    } else {
        echo "Erro ao atualizar o usuário.";
    }
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
        }
        form {
            background-color: #222;
            padding: 25px;
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #eee;
            margin-bottom: 20px;
            border-bottom: 2px solid #0af;
            padding-bottom: 10px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #444;
            margin-bottom: 15px;
            box-sizing: border-box;
            font-size: 16px;
            background-color: #333;
            color: #eee;
        }
        input:focus,
        select:focus {
            border-color: #0af;
            outline: none;
        }
        button, a.btn {
            padding: 10px 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        button[type="submit"] {
            background-color: #0af;
            color: white;
            width: auto; /* Ajuste para não ocupar a largura toda */
        }
        button[type="submit"]:hover {
            background-color: #008cdd;
        }
        a.btn {
            background-color: #555;
            color: white;
        }
        a.btn:hover {
            background-color: #444;
        }
        .form-actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <form method="POST">
        <h2><i class="fa-solid fa-user-pen"></i> Editar Usuário</h2>

        <label for="nome">Nome Completo:</label>
        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>" required>

        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf ?? '') ?>" required>

        <label for="telefone">Telefone:</label>
        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone ?? '') ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

        <label for="perfil">Perfil:</label>
        <select id="perfil" name="perfil" required>
            <option value="padrao" <?= ($perfil ?? '') == 'padrao' ? 'selected' : '' ?>>Padrão</option>
            <option value="admin" <?= ($perfil ?? '') == 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>

        <div class="form-actions">
            <button type="submit">Salvar Alterações</button>
            <a href="usuarios.php" class="btn">Cancelar</a>
            <a href="esqueci_senha_login.php?id=<?= htmlspecialchars($id ?? '') ?>" class="btn">Esqueci/Redefinir Senha</a>
        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>