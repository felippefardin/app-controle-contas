<?php
session_start();
include('../database.php');
if (!isset($_SESSION['usuario'])) { header('Location: ../pages/login.php'); exit; }

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

<h2>Escolha a forma de pagamento</h2>
<form method="POST">
  <select name="forma" required>
    <option value="">Selecione</option>
    <option value="boleto">Boleto</option>
    <option value="deposito">Depósito</option>
    <option value="credito">Cartão de Crédito</option>
    <option value="debito">Cartão de Débito</option>
    <option value="dinheiro">Dinheiro</option>
  </select>
  <button type="submit">Confirmar</button>
</form>
