<?php
session_start();
include('../database.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id = $_GET['id'];
$formas = ['boleto', 'deposito', 'credito', 'debito', 'dinheiro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forma = $_POST['forma'];
    $hoje = date('Y-m-d');
    $usuario = $_SESSION['usuario']['id'];

    $sql = "UPDATE contas_pagar SET status='baixada', forma_pagamento=?, data_baixa=?, baixado_por=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $forma, $hoje, $usuario, $id);
    $stmt->execute();

    header('Location: ../pages/contas_pagar.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Baixar Conta - Forma de Pagamento</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <!-- FontAwesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    /* Reset e base */
    * {
      box-sizing: border-box;
    }
    body {
      background-color: #121212;
      color: #eee;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    h2 {
      text-align: center;
      color: #00bfff;
      margin-bottom: 25px;
    }

    form {
      background-color: #1f1f1f;
      padding: 25px 30px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(0,191,255,0.4);
      width: 320px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    select {
      padding: 12px 15px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #333;
      background-color: #2a2a2a;
      color: #eee;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%23eee' stroke-width='2'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 12px 8px;
      cursor: pointer;
      transition: border-color 0.3s ease;
    }
    select:focus {
      outline: none;
      border-color: #00bfff;
      box-shadow: 0 0 8px #00bfff;
    }

    button {
      background-color: #00bfff;
      border: none;
      color: white;
      font-weight: 600;
      font-size: 16px;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    button:hover {
      background-color: #0099cc;
    }

  </style>
</head>
<body>

  <form method="POST" autocomplete="off">
    <h2><i class="fa fa-credit-card"></i> Escolha a forma de pagamento</h2>
    <select name="forma" required aria-label="Selecione a forma de pagamento">
      <option value="">Selecione</option>
      <option value="boleto">Boleto</option>
      <option value="deposito">Depósito</option>
      <option value="credito">Cartão de Crédito</option>
      <option value="debito">Cartão de Débito</option>
      <option value="dinheiro">Dinheiro</option>
    </select>
    <button type="submit"><i class="fa fa-check"></i> Confirmar</button>
  </form>

</body>
</html>
