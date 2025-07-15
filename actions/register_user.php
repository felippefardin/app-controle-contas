<?php
include('../database.php');
$nome = $_POST['nome'];
$cpf = $_POST['cpf'];
$telefone = $_POST['telefone'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
$perfil = $_POST['perfil'];
$sql = "INSERT INTO usuarios (nome, cpf, telefone, email, senha, perfil) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nome, $cpf, $telefone, $email, $senha, $perfil);
if ($stmt->execute()) {
  echo "<script>alert('Usuário cadastrado com sucesso');window.location.href='../pages/login.php';</script>";
} else {
  echo "Erro: " . $stmt->error;
}
?>