<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$nome = $_SESSION['usuario']['nome'];
$perfil = $_SESSION['usuario']['perfil'];

// Mostrar mensagem de sucesso se existir
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
} else {
    $mensagem = null;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Home - App Controle de Contas</title>
  <style>
    /* Reset básico */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: #121212;
      color: #eee;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    h1 {
      color: #00bfff;
      margin-bottom: 10px;
      text-align: center;
    }

    h3 {
      margin-bottom: 20px;
      font-weight: 400;
      text-align: center;
      color: #bbb;
    }

    .mensagem-sucesso {
      background-color: #28a745;
      padding: 15px 20px;
      margin-bottom: 20px;
      border-radius: 8px;
      max-width: 400px;
      width: 100%;
      text-align: center;
      box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
      font-weight: 600;
      font-size: 1rem;
    }

    nav {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
      margin-bottom: 30px;
      width: 100%;
      max-width: 600px;
    }

    nav a {
      background-color: #00bfff;
      color: #121212;
      text-decoration: none;
      padding: 12px 22px;
      border-radius: 8px;
      font-weight: 600;
      box-shadow: 0 3px 6px rgba(0,191,255,0.4);
      transition: background-color 0.3s ease, color 0.3s ease;
      flex: 1 1 130px;
      text-align: center;
      user-select: none;
    }

    nav a:hover,
    nav a:focus {
      background-color: #0095cc;
      color: #fff;
      outline: none;
      box-shadow: 0 0 12px #0095cc;
    }

    p {
      font-size: 1.1rem;
      color: #ccc;
      text-align: center;
      max-width: 600px;
      width: 100%;
    }

    /* Responsivo para telas menores */
    @media (max-width: 480px) {
      nav {
        flex-direction: column;
        gap: 12px;
      }

      nav a {
        flex: 1 1 100%;
        padding: 14px 0;
        font-size: 1.1rem;
      }

      body {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
  <h1>App Controle de Contas</h1>
  <h3>Usuário: <?= htmlspecialchars($nome) ?> (<?= htmlspecialchars($perfil) ?>)</h3>

  <?php if ($mensagem): ?>
    <div class="mensagem-sucesso"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <nav>
    <a href="contas_pagar.php">Contas a Pagar</a>
    <a href="contas_receber.php">Contas a Receber</a>
    <a href="usuarios.php">Usuários</a>
    <a href="perfil.php">Perfil</a>
    <a href="logout.php">Sair</a>
  </nav>

  <p>Bem-vindo ao sistema!</p>
</body>
</html>
