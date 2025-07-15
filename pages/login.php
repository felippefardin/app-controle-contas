<?php
session_start();
include('../database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $nome, $email_db, $senha_hash, $perfil);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            $_SESSION['usuario'] = [
                'id' => $id,
                'nome' => $nome,
                'email' => $email_db,
                'perfil' => $perfil
            ];
            $_SESSION['mensagem'] = "Usuário logado com sucesso!";
            header('Location: home.php');
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Usuário não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Login - App Controle de Contas</title>
  <style>
    /* básico para centralizar e escuro */
    * {
      box-sizing: border-box;
    }

    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      display: flex;
      height: 100vh;
      justify-content: center;
      align-items: center;
      margin: 0;
      padding: 10px;
    }

    form {
      background: #222;
      padding: 25px 30px;
      border-radius: 8px;
      width: 320px;
      box-shadow: 0 0 15px rgba(0, 123, 255, 0.7);
      display: flex;
      flex-direction: column;
    }

    form h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #00bfff;
    }

    label {
      margin-top: 10px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    input {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
    }

    input:focus {
      outline: 2px solid #00bfff;
      background-color: #333;
      color: #fff;
    }

    button {
      margin-top: 20px;
      padding: 12px;
      background-color: #007bff;
      border: none;
      border-radius: 5px;
      color: white;
      font-weight: bold;
      font-size: 1.1rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover,
    button:focus {
      background-color: #0056b3;
      outline: none;
    }

    .erro {
      background-color: #cc4444;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      font-weight: 600;
      text-align: center;
    }

    /* Responsividade */
    @media (max-width: 400px) {
      form {
        width: 100%;
        padding: 20px;
      }
      button {
        font-size: 1rem;
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <form method="POST" novalidate>
    <h2>Login</h2>

    <?php if (!empty($erro)) : ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required autofocus />

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required />

    <button type="submit">Entrar</button>

    <p style="margin-top:10px; text-align:center;">
      <a href="registro.php" style="color:#0af; text-decoration:none;">Não tem conta? Cadastre-se</a>
    </p>
  </form>
</body>
</html>
