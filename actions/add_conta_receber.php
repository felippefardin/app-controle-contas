<?php
session_start();
include('../database.php');
if (!isset($_SESSION['usuario'])) { header('Location: ../pages/login.php'); exit; }

$responsavel = $_POST['responsavel'];
$data_vencimento = $_POST['data_vencimento'];
$numero = $_POST['numero'];
$valor = $_POST['valor'];

$sql = "INSERT INTO contas_receber (responsavel, data_vencimento, numero, valor) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssd", $responsavel, $data_vencimento, $numero, $valor);
$stmt->execute();

header('Location: ../pages/contas_receber.php');
