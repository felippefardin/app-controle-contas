<?php
// Usar __DIR__ garante que o caminho esteja correto
require_once __DIR__ . '/../includes/session_init.php';

// Verifique se a sessão foi realmente iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$erro = $_SESSION['erro_login'] ?? '';
unset($_SESSION['erro_login']);

// ADICIONE ESTAS DUAS LINHAS
$sucesso = $_SESSION['registro_sucesso'] ?? '';
unset($_SESSION['registro_sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Login - App Controle de Contas</title>
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
      width: 500px;
      box-shadow: 0 0 15px rgba(0, 123, 255, 0.7);
      display: flex;
      flex-direction: column;
      position: relative;
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
    button:hover {
      background-color: #0056b3;
    }
    .erro {
      background-color: #cc4444;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      font-weight: 600;
      text-align: center;
    }
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
    .action-icons {
      position: absolute;
      top: 10px;
      right: 10px;
      display: flex;
      gap: 15px;
    }
    .action-icons a {
      color: #0af;
      text-decoration: none;
      font-size: 1.3rem;
      position: relative;
      transition: transform 0.3s ease;
    }
    .action-icons a:hover {
      transform: scale(1.2);
    }

    /* Balão do Feedback */
    .action-icons a.feedback::after {
      content: "Nos ajude a melhorar o sistema com sua opinião";
      position: absolute;
      bottom: 130%;
      right: 50%;
      transform: translateX(50%);
      background-color: #00bfff;
      color: #fff;
      font-size: 0.8rem;
      padding: 8px 10px;
      border-radius: 6px;
      white-space: nowrap;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    .action-icons a.feedback:hover::after {
      opacity: 1;
    }

    /* Texto acima do Suporte */
    .action-icons a.suporte::after {
      content: "Fale Conosco";
      position: absolute;
      bottom: 130%;
      left: 50%;
      transform: translateX(-50%);
      color: #00bfff;
      font-weight: bold;
      font-size: 0.9rem;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .action-icons a.suporte:hover::after {
      opacity: 1;
    }

    @media (max-width: 500px) {
      form {
        width: 100%;
        padding: 20px;
      }
      .action-icons {
        gap: 10px;
        top: 5px;
        right: 5px;
      }
      .action-icons a {
        font-size: 1.2rem;
      }
      button {
        font-size: 1rem;
        padding: 10px;
      }
    }
  </style>
</head>
<body>
  <form action="../actions/login.php" method="POST" novalidate>
    <div class="action-icons">
      <a href="feedback.php" class="feedback" title="Feedback"><i class="fas fa-comment-dots"></i></a>
      <a href="suporte.php" class="suporte" title="Suporte"><i class="fas fa-headset"></i></a>
    </div>
    <h2>Login</h2>

    
<?php if (!empty($sucesso)) : ?>
  <div class="sucesso" style="background-color: #4CAF50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-weight: 600; text-align: center;">
    <?= htmlspecialchars($sucesso) ?>
  </div>
<?php endif; ?>
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
      <a href="esqueci_senha_login.php" style="color:#0af; text-decoration:none;">Esqueci minha senha</a>
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
