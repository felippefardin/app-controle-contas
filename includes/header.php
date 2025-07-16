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

  .font-controls button {
    background-color: #007BFF;
    color: white;
    border: none;
    padding: 10px 14px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }

  .font-controls button:hover {
    background-color: #0056b3;
  }

  .font-controls button:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
  }
</style>

<div class="font-controls">
  <button type="button" onclick="adjustFontSize(-1)">A-</button>
  <button type="button" onclick="adjustFontSize(1)">A+</button>
  <button type="button" onclick="resetFontSize()">Resetar Fonte</button>
</div>
