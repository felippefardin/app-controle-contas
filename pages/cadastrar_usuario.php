<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'admin') {
    echo "<p style='color:red;'>Acesso negado</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $perfil = $_POST['perfil'];

    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "<p class='erro'>Este e-mail já está cadastrado.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, cpf, telefone, email, senha, perfil) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nome, $cpf, $telefone, $email, $senha, $perfil);
        $stmt->execute();

        header('Location: usuarios.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Cadastrar Novo Usuário</title>
  <style>
    body {
      background-color: #121212;
      color: #f0f0f0;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 40px;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    h2 {
      color: #00bfff;
      margin-bottom: 20px;
    }

    form {
      background-color: #1e1e1e;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.5);
      width: 100%;
      max-width: 500px;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border: none;
      border-radius: 5px;
      background-color: #2c2c2c;
      color: #f0f0f0;
    }

    input:focus, select:focus {
      outline: none;
      border: 1px solid #00bfff;
    }

    button {
      background-color: #00bfff;
      color: #fff;
      border: none;
      padding: 12px;
      margin-top: 20px;
      border-radius: 5px;
      width: 100%;
      font-size: 16px;
      cursor: pointer;
    }

    button:hover {
      background-color: #009acf;
    }

    a {
      display: inline-block;
      margin-top: 15px;
      color: #ff6666;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover {
      text-decoration: underline;
    }

    .erro {
      background-color: #b22222;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      color: #fff;
      font-weight: bold;
    }

    @media (max-width: 600px) {
      form {
        padding: 20px;
      }

      h2 {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>

<h2>Cadastrar Novo Usuário</h2>

<form method="POST">
  <label>Nome Completo:</label>
  <input type="text" name="nome" required>

  <label>CPF:</label>
  <input type="text" name="cpf" required>

  <label>Telefone:</label>
  <input type="text" name="telefone" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <label>Senha:</label>
  <input type="password" name="senha" required>

  <label>Perfil:</label>
  <select name="perfil" required>
    <option value="padrao">Padrão</option>
    <option value="admin">Administrador</option>
  </select>

  <button type="submit">Cadastrar</button>
  <a href="usuarios.php">Cancelar</a>
</form>

</body>
</html>

<?php include('../includes/footer.php'); ?>
