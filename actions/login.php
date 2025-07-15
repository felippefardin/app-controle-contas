<?php
session_start();
include('../database.php');
$email = $_POST['email'];
$senha = $_POST['senha'];
$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
  if (password_verify($senha, $user['senha'])) {
    $_SESSION['usuario'] = $user;
    echo "<script>alert('Usuário logado com sucesso');window.location.href='../pages/home.php';</script>";
    exit;
  }
}
echo "<script>alert('Credenciais inválidas');window.location.href='../pages/login.php';</script>";
?>