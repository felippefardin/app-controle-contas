<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>App Controle de Contas</title>
  <style>
    /* Base do corpo */
body {
  background-color: #121212;
  color: #eee;
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 20px;
  transition: font-size 0.3s ease;
}

/* Container dos controles de fonte e botões */
.font-controls {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  margin: 20px 0;
}

/* Estilo dos botões */
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

/* Cores dos botões */
.btn-font { background-color: #007BFF; }
.btn-font:hover { background-color: #0056b3; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.35); }

.btn-home { background-color: #28a745; }
.btn-home:hover { background-color: #1e7e34; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.35); }

.btn-exit { background-color: #dc3545; }
.btn-exit:hover { background-color: #b52a37; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.35); }

/* Foco nos botões */
.font-controls button:focus,
.font-controls a:focus {
  outline: 2px solid #00bfff;
  outline-offset: 2px;
}

/* RESPONSIVIDADE MOBILE */
@media (max-width: 768px) {
  .font-controls {
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .font-controls button,
  .font-controls a {
    width: 90%;      /* Ocupa quase toda a largura da tela */
    font-size: 15px; /* Ajuste de fonte para caber melhor */
    padding: 10px;
  }
}

@media (max-width: 480px) {
  body {
    padding: 15px;
  }

  .font-controls {
    gap: 10px;
  }

  .font-controls button,
  .font-controls a {
    width: 95%;     /* Largura máxima para telas muito pequenas */
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
