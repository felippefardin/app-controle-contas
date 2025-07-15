<?php
session_start();
include('../database.php');
if (!isset($_SESSION['usuario'])) { header('Location: ../pages/login.php'); exit; }

$fornecedor = $_POST['fornecedor'];
$data_vencimento = $_POST['data_vencimento'];
$numero = $_POST['numero'];
$valor = $_POST['valor'];

$sql = "INSERT INTO contas_pagar (fornecedor, data_vencimento, numero, valor) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssd", $fornecedor, $data_vencimento, $numero, $valor);
$stmt->execute();

header('Location: ../pages/contas_pagar.php');

