<?php
// header_home.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>App Controle de Contas</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>

<style>
/* Estilo dos botões */
.font-controls button {
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

/* Cores específicas */
.font-controls .btn-font { background-color: #007BFF; color: white; }
.font-controls .btn-font:hover { background-color: #0056b3; }

.font-controls button:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

/* Responsivo: telas menores que 600px */
@media (max-width: 600px) {
    .font-controls {
        
        left: 50%;
        transform: translateX(-50%);
        position: relative;
        display: flex;
        gap: 10px;
        z-index: 1000;
    }

    .font-controls button {
        font-size: 14px;
        padding: 8px 12px;
    }
}

/* Posição padrão no desktop */
.font-controls {
    position: relative;
   
    display: flex;
    gap: 10px;
    z-index: 1000;
}
</style>


<div class="font-controls">
  <button type="button" class="btn-font" onclick="adjustFontSize(-1)">A-</button>
  <button type="button" class="btn-font" onclick="adjustFontSize(1)">A+</button>
  <button type="button" class="btn-font" onclick="resetFontSize()">Resetar Fonte</button>  
  <button type="button" class="btn-font">
    <a href="tutorial.php" style="color: #fff; text-decoration: none;">
      <i class="fa fa-book-open" style="color: #fff;"></i> Tutorial
    </a>
  </button>
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
