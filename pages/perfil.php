<?php
session_start();
include('../database.php');
include('../includes/header.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

// Buscar dados atuais
$stmt = $conn->prepare("SELECT nome, cpf, telefone, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nome, $cpf, $telefone, $email);
$stmt->fetch();
$stmt->close();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_novo = $_POST['nome'];
    $cpf_novo = $_POST['cpf'];
    $telefone_novo = $_POST['telefone'];
    $email_novo = $_POST['email'];
    $senha_nova = $_POST['senha'];
    $senha_confirmar = $_POST['senha_confirmar'];

    if (empty($nome_novo) || empty($cpf_novo) || empty($telefone_novo) || empty($email_novo)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (!empty($senha_nova) && $senha_nova !== $senha_confirmar) {
        $erro = "Senhas não conferem.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email_novo, $id_usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $erro = "Este e-mail já está em uso por outro usuário.";
        } else {
            if (!empty($senha_nova)) {
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE usuarios SET nome=?, cpf=?, telefone=?, email=?, senha=? WHERE id=?");
                $stmt_update->bind_param("sssssi", $nome_novo, $cpf_novo, $telefone_novo, $email_novo, $senha_hash, $id_usuario);
            } else {
                $stmt_update = $conn->prepare("UPDATE usuarios SET nome=?, cpf=?, telefone=?, email=? WHERE id=?");
                $stmt_update->bind_param("ssssi", $nome_novo, $cpf_novo, $telefone_novo, $email_novo, $id_usuario);
            }

            if ($stmt_update->execute()) {
                $mensagem = "Dados atualizados com sucesso!";
                $_SESSION['usuario']['nome'] = $nome_novo;
                $_SESSION['usuario']['email'] = $email_novo;
            } else {
                $erro = "Erro ao atualizar os dados.";
            }
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Editar Perfil</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
    }
    form {
      background-color: #222;
      padding: 20px;
      border-radius: 8px;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 8px;
      border-radius: 4px;
      border: none;
      margin-bottom: 15px;
      box-sizing: border-box;
      font-size: 16px;
      background-color: #333;
      color: #eee;
    }
    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .password-wrapper input {
      flex: 1;
      padding-right: 40px;
      margin-bottom: 0;
    }
    .toggle-password {
      position: absolute;
      right: 10px;
      background: transparent;
      border: none;
      cursor: pointer;
      color: #00bfff;
      font-size: 18px;
      user-select: none;
    }
    .toggle-password:focus {
      outline: none;
    }
    button[type="submit"] {
      background-color: #0af;
      color: white;
      padding: 10px;
      font-weight: bold;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      width: 100%;
      font-size: 16px;
    }
    .mensagem {
      background-color: #28a745;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      text-align: center;
    }
    .erro {
      background-color: #cc4444;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      text-align: center;
    }
    a {
      color: #0af;
      text-decoration: none;
      margin-top: 20px;
      display: inline-block;
    }
    .salvar-btn {
  margin-top: 20px; /* ou ajuste conforme necessário */
}

.salvar-btn button {
  background-color: #28a745; /* verde */
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
}

.salvar-btn button:hover {
  background-color: #218838;
}

  </style>
</head>
<body>

<div class="container">
  <h2>Editar Perfil</h2>

  <?php if ($mensagem): ?>
    <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <label for="nome">Nome Completo:</label>
    <input id="nome" type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

    <label for="cpf">CPF:</label>
    <input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>

    <label for="telefone">Telefone:</label>
    <input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required>

    <label for="email">Email:</label>
    <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label for="senha">Nova Senha (deixe em branco para manter a atual):</label>
    <div class="password-wrapper">
      <input type="password" id="senha" name="senha">
      <button type="button" class="toggle-password" data-target="senha" aria-label="Mostrar/Ocultar senha">👁️</button>
    </div>

    <label for="senha_confirmar">Confirmar Nova Senha:</label>
    <div class="password-wrapper">
      <input type="password" id="senha_confirmar" name="senha_confirmar">
      <button type="button" class="toggle-password" data-target="senha_confirmar" aria-label="Mostrar/Ocultar senha">👁️</button>
    </div>

    <button type="submit">Salvar Alterações</button>
  </form>

  <p><a href="home.php">Voltar para Home</a></p>
</div>

<script>
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
      } else {
        input.type = 'password';
        button.textContent = '👁️';
      }
    });
  });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
