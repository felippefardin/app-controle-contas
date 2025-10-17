<?php if (isset($_SESSION['proprietario_id_original'])): ?>
    <div style="background-color: #ffc107; color: #000; text-align: center; padding: 10px;">
        Você está visualizando como <strong><?= htmlspecialchars($_SESSION['usuario_principal']['nome']); ?></strong>.
        <a href="../actions/retornar_admin.php" style="margin-left: 20px; color: #000; font-weight: bold;">Voltar para o Acesso Proprietário</a>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>App Controle de Contas</title>

  <style>
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      transition: font-size 0.3s ease;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .font-controls {
      display: flex;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
      margin: 20px 0;
    }

    .font-controls button,
    .font-controls a {
      border: none;
      padding: 10px 16px;
      font-size: 16px;
      font-weight: bold;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
      text-decoration: none;
      display: inline-block;
      color: white;
      text-align: center;
    }

    .btn-font { background-color: #007BFF; }
    .btn-font:hover { background-color: #0056b3; transform: translateY(-2px); }

    .btn-home { background-color: #28a745; }
    .btn-home:hover { transform: translateY(-2px); }

    .btn-exit { background-color: #dc3545; }
    .btn-exit:hover { transform: translateY(-2px); }

    @media (max-width: 768px) {
      .font-controls {
        flex-direction: column;
        align-items: center;
      }
      .font-controls button,
      .font-controls a {
        width: 90%;
        font-size: 15px;
        padding: 10px;
      }
    }

    @media (max-width: 480px) {
      body { padding: 15px; }
      .font-controls button,
      .font-controls a {
        width: 95%;
        font-size: 14px;
        padding: 8px;
      }
    }
  </style>
</head>

<body>
  <div class="font-controls">
    <button type="button" class="btn-font" onclick="adjustFontSize(-1)">A-</button>
    <button type="button" class="btn-font" onclick="adjustFontSize(1)">A+</button>
    <button type="button" class="btn-font" onclick="resetFontSize()">Resetar Fonte</button>
    <a href="../pages/home.php" class="btn-home">Voltar Home</a>
    <a href="../pages/logout.php" class="btn-exit">Sair</a> 
  </div>

  <main>
