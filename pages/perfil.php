<?php
session_start();
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
  <link rel="stylesheet" href="../css/style.css" />

  <title>Editar Perfil - App Controle de Contas</title>
  <style>
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    form {
      background-color: #222;
      padding: 20px;
      border-radius: 8px;
      max-width: 400px;
    }
    input, label {
      display: block;
      width: 100%;
      margin-bottom: 10px;
    }
    input {
      padding: 8px;
      border-radius: 4px;
      border: none;
    }
    button {
      padding: 10px;
      background-color: #0af;
      border: none;
      border-radius: 4px;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }
    .mensagem {
      background-color: #28a745;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      max-width: 400px;
    }
    .erro {
      background-color: #cc4444;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      max-width: 400px;
    }
    a {
      color: #0af;
      text-decoration: none;
      margin-top: 10px;
      display: inline-block;
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

  <div class="font-controls">
  <button type="button" onclick="adjustFontSize(-1)">A-</button>
  <button type="button" onclick="adjustFontSize(1)">A+</button>
</div>


  <form method="POST">
    <label>Nome Completo:</label>
    <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

    <label>CPF:</label>
    <input type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>

    <label>Telefone:</label>
    <input type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label>Nova Senha (deixe em branco para manter a atual):</label>
    <input type="password" name="senha">

    <label>Confirmar Nova Senha:</label>
    <input type="password" name="senha_confirmar">

    <button type="submit">Salvar Alterações</button>
  </form>

  <p><a href="home.php">Voltar para Home</a></p>

  <script>
  function adjustFontSize(change) {
    const body = document.body;
    const style = window.getComputedStyle(body, null).getPropertyValue('font-size');
    let fontSize = parseFloat(style);
    fontSize += change;
    if (fontSize < 12) fontSize = 12;
    if (fontSize > 24) fontSize = 24;
    body.style.fontSize = fontSize + 'px';
  }
</script>


</body>
</html>
