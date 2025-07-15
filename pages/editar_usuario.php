<?php
include('../includes/header.php');
include('../database.php');
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Usuário não informado.";
    exit;
}

// Pega dados do usuário para preencher o formulário
$stmt = $conn->prepare("SELECT nome, cpf, telefone, email, perfil FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nome, $cpf, $telefone, $email, $perfil);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $perfil = $_POST['perfil'];

    $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, cpf = ?, telefone = ?, email = ?, perfil = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $nome, $cpf, $telefone, $email, $perfil, $id);
    $stmt->execute();

    header('Location: usuarios.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Usuário</title>
  <style>
    body {
      background-color: #121212;
      color: #e0e0e0;
      font-family: Arial, sans-serif;
      padding: 20px;
      margin: 0;
    }

    h2 {
      text-align: center;
      color: #00bfff;
      margin-bottom: 30px;
    }

    form {
      max-width: 500px;
      margin: auto;
      background-color: #1e1e1e;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 191, 255, 0.2);
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
      color: #ccc;
    }

    input[type="text"],
    input[type="email"],
    select {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      background-color: #2c2c2c;
      color: #fff;
      border: 1px solid #444;
      border-radius: 6px;
      font-size: 16px;
    }

    input:focus,
    select:focus {
      border-color: #00bfff;
      outline: none;
    }

    button {
      width: 100%;
      background-color: #00bfff;
      color: white;
      border: none;
      padding: 12px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
    }

    button:hover {
      background-color: #0095cc;
    }

    a {
      display: block;
      margin-top: 15px;
      text-align: center;
      color: #00bfff;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
    }

    @media (max-width: 600px) {
      body {
        padding: 10px;
      }

      form {
        padding: 20px;
      }

      input, select, button {
        font-size: 15px;
      }
    }
  </style>
</head>
<body>

<h2>Editar Usuário</h2>

<form method="POST">
  <label>Nome Completo:</label>
  <input type="text" name="nome" value="<?=htmlspecialchars($nome)?>" required>

  <label>CPF:</label>
  <input type="text" name="cpf" value="<?=htmlspecialchars($cpf)?>" required>

  <label>Telefone:</label>
  <input type="text" name="telefone" value="<?=htmlspecialchars($telefone)?>" required>

  <label>Email:</label>
  <input type="email" name="email" value="<?=htmlspecialchars($email)?>" required>

  <label>Perfil:</label>
  <select name="perfil" required>
    <option value="padrao" <?= $perfil == 'padrao' ? 'selected' : '' ?>>Padrão</option>
    <option value="admin" <?= $perfil == 'admin' ? 'selected' : '' ?>>Administrador</option>
  </select>

  <button type="submit">Salvar</button>
  <a href="usuarios.php">Cancelar</a>
</form>

</body>
</html>

<?php include('../includes/footer.php'); ?>
