<?php
session_start();
include('../database.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$formas = ['boleto', 'deposito', 'credito', 'debito', 'dinheiro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forma = $_POST['forma'];
    $juros = floatval(str_replace(',', '.', $_POST['juros'] ?? 0));
    $hoje = date('Y-m-d');
    $usuario = $_SESSION['usuario']['id'];

    $sql = "UPDATE contas_receber SET status='baixada', forma_pagamento=?, juros=?, data_baixa=?, baixado_por=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    
    // A string de tipos agora inclui 'd' para o valor decimal dos juros
    $stmt->bind_param("ssidi", $forma, $juros, $hoje, $usuario, $id);
    $stmt->execute();

    header('Location: ../pages/contas_receber_baixadas.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Forma de Pagamento - Contas a Receber</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      background-color: #121212;
      color: #eee;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 30px 15px;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }
    h2 {
      color: #00bfff;
      margin-bottom: 30px;
      font-weight: 700;
      text-align: center;
    }
    form {
      background-color: #1f1f1f;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.6);
      width: 100%;
      max-width: 400px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    select, input[type="text"] {
      padding: 12px 40px 12px 14px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #444;
      background-color: #333;
      color: #eee;
      appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg fill="%23eee" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 16px 16px;
      cursor: pointer;
      transition: border-color 0.3s ease;
    }
    select:focus, input[type="text"]:focus {
      outline: none;
      border-color: #00bfff;
      box-shadow: 0 0 6px #00bfff;
    }
    button {
      background-color: #27ae60;
      color: white;
      border: none;
      font-weight: bold;
      padding: 12px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #1e874b;
    }
  </style>
</head>
<body>

  <h2>Escolha a forma de pagamento</h2>
  <form method="POST" novalidate>
    <input type="text" name="juros" placeholder="Juros (ex: 15.50)" pattern="^\d+([.,]\d{1,2})?$" title="Use ponto ou vírgula para separar os centavos" value="0,00" />
    <select name="forma" required aria-label="Selecione a forma de pagamento">
      <option value="" disabled selected>Selecione</option>
      <option value="boleto">Boleto</option>
      <option value="deposito">Depósito</option>
      <option value="credito">Cartão de Crédito</option>
      <option value="debito">Cartão de Débito</option>
      <option value="dinheiro">Dinheiro</option>
    </select>
    <button type="submit">
      <i class="fa-solid fa-check"></i> Confirmar
    </button>
  </form>

</body>
</html>