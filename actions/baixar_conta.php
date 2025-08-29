<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// 🔹 Conexão com o banco (mesma de contas_pagar.php)
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "app_controle_contas";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0); // garante que seja um número
$formas = ['boleto', 'deposito', 'credito', 'debito', 'dinheiro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forma   = $_POST['forma'];
    $hoje    = date('Y-m-d');
    $usuario = $_SESSION['usuario']['id'];

    $sql = "UPDATE contas_pagar 
            SET status='baixada', forma_pagamento=?, data_baixa=?, baixado_por=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro ao preparar query: " . $conn->error);
    }

    $stmt->bind_param("ssii", $forma, $hoje, $usuario, $id);
    if (!$stmt->execute()) {
        die("Erro ao atualizar conta: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

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
  
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    * { box-sizing: border-box; }
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
    h2 { text-align: center; color: #00bfff; margin-bottom: 25px; }
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
      cursor: pointer;
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
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    button:hover { background-color: #0099cc; }
  </style>
</head>
<body>

  <form method="POST" autocomplete="off">
    <h2><i class="fa fa-credit-card"></i> Escolha a forma de pagamento</h2>
    <select name="forma" required aria-label="Selecione a forma de pagamento">
      <option value="">Selecione</option>
      <?php foreach ($formas as $f): ?>
        <option value="<?= htmlspecialchars($f) ?>"><?= ucfirst($f) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit"><i class="fa fa-check"></i> Confirmar</button>
  </form>

</body>
</html>
