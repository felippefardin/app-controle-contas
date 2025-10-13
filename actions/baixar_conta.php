<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

$id = intval($_GET['id'] ?? 0);
$formas = ['boleto', 'deposito', 'credito', 'debito', 'dinheiro'];
$comprovantePath = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forma = $_POST['forma'];
    $juros = floatval(str_replace(',', '.', $_POST['juros'] ?? 0));
    $dataBaixaInput = $_POST['data_baixa'];
    $usuario = $_SESSION['usuario']['id'];

    // Lógica de Upload do Comprovante
    if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] == 0) {
        $target_dir = "../comprovantes/";
        $fileName = uniqid() . '_' . basename($_FILES["comprovante"]["name"]);
        $target_file = $target_dir . $fileName;
        
        // Tenta mover o arquivo para o diretório de uploads
        if (move_uploaded_file($_FILES["comprovante"]["tmp_name"], $target_file)) {
            $comprovantePath = 'comprovantes/' . $fileName;
        }
    }

    $dataBaixa = DateTime::createFromFormat('d/m/Y', $dataBaixaInput);
    if ($dataBaixa) {
        $dataBaixaFormatada = $dataBaixa->format('Y-m-d');
    } else {
        $dataBaixaFormatada = date('Y-m-d');
    }

    $sql = "UPDATE contas_pagar 
            SET status='baixada', forma_pagamento=?, juros=?, data_baixa=?, baixado_por=?, comprovante=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro ao preparar query: " . $conn->error);
    }
    
    $stmt->bind_param("sdsisi", $forma, $juros, $dataBaixaFormatada, $usuario, $comprovantePath, $id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta baixada com sucesso!";
    } else {
        // $_SESSION['error_message'] = "Erro ao baixar conta: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header('Location: ../pages/contas_pagar_baixadas.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Baixar Conta - Forma de Pagamento</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
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
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    select, input[type="text"] {
      padding: 12px 15px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #333;
      background-color: #2a2a2a;
      color: #eee;
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
 
    input[type="file"] {
        padding: 12px 15px;
        font-size: 16px;
        border-radius: 6px;
        border: 1px solid #333;
        background-color: #2a2a2a;
        color: #eee;
        cursor: pointer;
    }
  </style>
</head>
<body>

  <form method="POST" autocomplete="off" enctype="multipart/form-data">
    <h2><i class="fa fa-credit-card"></i> Baixar Conta a Pagar</h2>
    
    <input type="text" name="data_baixa" placeholder="Data da Baixa (ex: dd/mm/aaaa)" value="<?= date('d/m/Y') ?>" required />

    <input type="text" name="juros" placeholder="Juros (ex: 10,50)" pattern="^\d+([,.]\d{1,2})?$" title="Use vírgula para separar os centavos" value="0,00" />
    
    <select name="forma" required aria-label="Selecione a forma de pagamento">
      <option value="">Selecione a Forma de Pagamento</option>
      <?php foreach ($formas as $f): ?>
        <option value="<?= htmlspecialchars($f) ?>"><?= ucfirst($f) ?></option>
      <?php endforeach; ?>
    </select>
    
    <label for="comprovante" style="text-align: left; font-weight: bold; margin-bottom: -10px;">Anexar Comprovante:</label>
    <input type="file" name="comprovante" accept="image/*,.pdf">
    
    <button type="submit"><i class="fa fa-check"></i> Confirmar</button>
  </form>

</body>
</html>



 