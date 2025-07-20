<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>App Controle de Contas</title>
  <link rel="stylesheet" href="../css/styles.css" />
</head>
<body>

<style>
  
  .font-controls {
    position: fixed;
    top: 20px;
    right: 20px;
    display: flex;
    gap: 10px;
    z-index: 1000;
  }

  .font-controls button,
  .font-controls a {
    border: none;
    padding: 10px 14px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    text-decoration: none;
    display: inline-block;
  }

  .font-controls .btn-font {
    background-color: #007BFF;
    color: white;
  }

  .font-controls .btn-font:hover {
    background-color: #0056b3;
  }

  .font-controls .btn-exit {
    background-color: #dc3545;
    color: white;
  }

  .font-controls .btn-exit:hover {
    background-color: #b52a37;
  }

  .font-controls .btn-home {
    background-color: #28a745;
    color: white;
  }

  .font-controls .btn-home:hover {
    background-color: #1e7e34;
  }

  .font-controls button:focus,
  .font-controls a:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
  }
</style>

<div class="font-controls">
  <button type="button" class="btn-font" onclick="adjustFontSize(-1)">A-</button>
  <button type="button" class="btn-font" onclick="adjustFontSize(1)">A+</button>
  <button type="button" class="btn-font" onclick="resetFontSize()">Resetar Fonte</button>
  <a href="../pages/home.php" class="btn-home">Voltar Home</a>
  <a href="../pages/logout.php" class="btn-exit">Sair</a>
</div>

<script>
  let fontSize = 100;

  function adjustFontSize(amount) {
    fontSize += amount * 10;
    document.body.style.fontSize = fontSize + '%';
  }

  function resetFontSize() {
    fontSize = 100;
    document.body.style.fontSize = '100%';
  }
</script>

</body>
</html>
