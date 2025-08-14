<?php
session_start();
include('../database.php');

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        $erro = "Preencha e-mail e senha.";
    } else {
        $stmt = $conn->prepare("SELECT id, nome, email, senha, perfil, tipo, foto FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $nome, $email_db, $senha_hash, $perfil, $tipo, $foto);
            $stmt->fetch();

            if (password_verify($senha, $senha_hash)) {
                $_SESSION['usuario'] = [
                    'id' => $id,
                    'nome' => $nome,
                    'email' => $email_db,
                    'perfil' => $perfil,
                    'tipo' => $tipo,
                    'foto' => $foto,
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
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Login - App Controle de Contas</title>
  <!-- Link Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
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

    /* Campo com ícone */
    .password-container {
      position: relative;
      width: 100%;
    }

    .password-container input {
      padding-right: 35px;
    }

    .password-container .toggle-password {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      cursor: pointer;
      color: black;
      font-size: 1.1rem;
    }

    .password-container .toggle-password:hover {
      color: #222;
    }

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
    <div class="password-container">
      <input type="password" id="senha" name="senha" required />
      <i class="fas fa-eye toggle-password" id="toggleSenha"></i>
    </div>

    <button type="submit">Entrar</button>

    <p style="margin-top:10px; text-align:center;">
      <a href="registro.php" style="color:#0af; text-decoration:none;">Não tem conta? Cadastre-se</a>
    </p>
    <p style="text-align:center; margin-top: 10px;">
      <a href="esqueci_senha.php" style="color:#0af; text-decoration:none;">Esqueci minha senha</a>
    </p>
  </form>

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