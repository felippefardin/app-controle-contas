<?php
session_start();
include('../includes/header.php'); 
include('../database.php');

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

    if (empty($nome_novo) || empty($cpf_novo) || empty($telefone_novo) || empty($email_novo)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else if (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } else if (!empty($senha_nova) && $senha_nova !== $senha_confirmar) {
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
  <title>Editar Perfil - App Controle de Contas</title>
  <link rel="stylesheet" href="../css/style.css" />
  
  <!-- FontAwesome CDN para ícones -->
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
    .input-with-icon {
      position: relative;
    }
    .input-with-icon button {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #0af;
      cursor: pointer;
      font-size: 18px;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 28px;
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
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    .erro {
      background-color: #cc4444;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    a {
      color: #0af;
      text-decoration: none;
      margin-top: 20px;
      display: inline-block;
    }
    .font-controls {
      margin-bottom: 20px;
      text-align: center;
    }
    .font-controls button {
      padding: 6px 12px;
      margin: 0 5px;
      cursor: pointer;
      font-weight: bold;
      border-radius: 4px;
      border: none;
      background-color: #0af;
      color: white;
      transition: background-color 0.3s ease;
    }
    .font-controls button:hover {
      background-color: #0088cc;
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


  <form method="POST" autocomplete="off" class="form-editar-perfil">
    <label for="nome">Nome Completo:</label>
    <input id="nome" type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

    <label for="cpf">CPF:</label>
    <input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>

    <label for="telefone">Telefone:</label>
    <input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required>

    <label for="email">Email:</label>
    <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label for="senha">Nova Senha (deixe em branco para manter a atual):</label>
    <div class="input-with-icon">
      <input id="senha" type="password" name="senha" >
      <button type="button" tabindex="-1" onclick="togglePasswordVisibility('senha')">
        <i class="fa-solid fa-eye" id="icon-senha"></i>
      </button>
    </div>

    <label for="senha_confirmar">Confirmar Nova Senha:</label>
    <div class="input-with-icon">
      <input id="senha_confirmar" type="password" name="senha_confirmar" >
      <button type="button" tabindex="-1" onclick="togglePasswordVisibility('senha_confirmar')">
        <i class="fa-solid fa-eye" id="icon-senha_confirmar"></i>
      </button>
    </div>

    <button type="submit">Salvar Alterações</button>
  </form>

  <p><a href="home.php">Voltar para Home</a></p>

</div>

<?php include('../includes/footer.php'); ?>
