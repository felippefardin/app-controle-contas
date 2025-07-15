<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

// Pega dados atuais para preencher o formulário
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

    // Validar campos básicos
    if (empty($nome_novo) || empty($cpf_novo) || empty($telefone_novo) || empty($email_novo)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else if (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } else if (!empty($senha_nova) && $senha_nova !== $senha_confirmar) {
        $erro = "Senhas não conferem.";
    } else {
        // Verificar se o e-mail já existe para outro usuário
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email_novo, $id_usuario);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $erro = "Este e-mail já está em uso por outro usuário.";
        } else {
            // Atualizar dados
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
                // Atualizar sessão
                $_SESSION['usuario']['nome'] = $nome_novo;
                $_SESSION['usuario']['email'] = $email_novo;
            } else {
                $erro = "Erro ao atualizar os dados.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Editar Perfil - App Controle de Contas</title>
  <style>
    /* Reset box-sizing para facilitar o layout */
    *, *::before, *::after {
      box-sizing: border-box;
    }

    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    h2 {
      text-align: center;
      color: #0af;
      margin-bottom: 20px;
      width: 100%;
      max-width: 400px;
    }

    form {
      background-color: #222;
      padding: 20px 25px;
      border-radius: 8px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 0 10px rgba(0, 170, 255, 0.5);
    }

    label {
      display: block;
      margin-top: 15px;
      margin-bottom: 6px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    input {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      border: none;
      font-size: 1rem;
      background-color: #333;
      color: #eee;
      transition: background-color 0.3s ease;
    }

    input:focus {
      background-color: #444;
      outline: 2px solid #0af;
      color: #fff;
    }

    button {
      margin-top: 25px;
      width: 100%;
      padding: 12px;
      background-color: #0af;
      border: none;
      border-radius: 5px;
      color: #eee;
      font-weight: bold;
      font-size: 1.1rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover, button:focus {
      background-color: #0088ff;
      outline: none;
    }

    .mensagem, .erro {
      max-width: 400px;
      width: 100%;
      margin: 0 auto 15px;
      padding: 12px;
      border-radius: 6px;
      font-weight: 600;
      text-align: center;
    }

    .mensagem {
      background-color: #28a745;
      color: #e6ffe6;
      box-shadow: 0 0 10px #28a745cc;
    }

    .erro {
      background-color: #cc4444;
      color: #ffe6e6;
      box-shadow: 0 0 10px #cc4444cc;
    }

    a {
      display: inline-block;
      margin-top: 20px;
      color: #0af;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    a:hover, a:focus {
      color: #0088ff;
      text-decoration: underline;
      outline: none;
    }

    /* Responsividade */
    @media (max-width: 440px) {
      body {
        padding: 15px 10px;
        align-items: center;
      }
      form, h2, .mensagem, .erro {
        max-width: 100%;
      }
      button {
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>

  <h2>Editar Perfil</h2>

  <?php if ($mensagem): ?>
    <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <label for="nome">Nome Completo:</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

    <label for="cpf">CPF:</label>
    <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>

    <label for="telefone">Telefone:</label>
    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label for="senha">Nova Senha (deixe em branco para manter a atual):</label>
    <input type="password" id="senha" name="senha" autocomplete="new-password">

    <label for="senha_confirmar">Confirmar Nova Senha:</label>
    <input type="password" id="senha_confirmar" name="senha_confirmar" autocomplete="new-password">

    <button type="submit">Salvar Alterações</button>
  </form>

  <a href="home.php">Voltar para Home</a>

</body>
</html>
