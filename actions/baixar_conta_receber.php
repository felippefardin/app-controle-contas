<?php
require_once '../includes/session_init.php';
include('../database.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

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

    // --- CORREÇÃO E AJUSTE NO SQL ---
    $sql = "UPDATE contas_receber SET status='baixada', forma_pagamento=?, juros=?, data_baixa=?, baixado_por_usuario_id=?, comprovante=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    
    // O bind_param foi ajustado para incluir o comprovante.
    $stmt->bind_param("sdsisi", $forma, $juros, $dataBaixaFormatada, $usuario, $comprovantePath, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta baixada com sucesso!";
    } else {
        // $_SESSION['error_message'] = "Erro ao baixar a conta.";
    }
    
    $stmt->close();
    $conn->close();

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
    * { box-sizing: border-box; }
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
    h2 { color: #00bfff; margin-bottom: 30px; font-weight: 700; text-align: center; }
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
    select, input[type="text"], input[type="file"] {
        padding: 12px 14px;
        font-size: 16px;
        border-radius: 6px;
        border: 1px solid #444;
        background-color: #333;
        color: #eee;
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
    button:hover { background-color: #1e874b; }
  </style>
</head>
<body>

  <h2>Escolha a forma de recebimento</h2>
  <form method="POST" novalidate enctype="multipart/form-data">
    <input type="text" name="data_baixa" placeholder="Data da Baixa (ex: dd/mm/aaaa)" value="<?= date('d/m/Y') ?>" required />

    <input type="text" name="juros" placeholder="Juros (ex: 15,50)" pattern="^\d+([,.]\d{1,2})?$" title="Use vírgula para separar os centavos" value="0,00" />
    
    <select name="forma" required aria-label="Selecione a forma de pagamento">
      <option value="" disabled selected>Selecione a forma</option>
      <option value="boleto">Boleto</option>
      <option value="deposito">Depósito</option>
      <option value="credito">Cartão de Crédito</option>
      <option value="debito">Cartão de Débito</option>
      <option value="dinheiro">Dinheiro</option>
    </select>

    <label for="comprovante" style="text-align: left; font-weight: bold; margin-bottom: -10px;">Anexar Comprovante:</label>
    <input type="file" name="comprovante" accept="image/*,.pdf">

    <button type="submit">
      <i class="fa-solid fa-check"></i> Confirmar
    </button>
  </form>

</body>
</html>