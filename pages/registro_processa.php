<?php
require_once('../database.php');

$nome = $_POST['nome'];
$cpf = $_POST['cpf'];
$telefone = $_POST['telefone'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
$tipo = 'padrao'; // sempre como padrão

// Verifica se já existe o e-mail
$check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo "Email já está cadastrado!";
  exit;
}

// Insere novo usuário
$stmt = $conn->prepare("INSERT INTO usuarios (nome, cpf, telefone, email, senha, tipo) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $nome, $cpf, $telefone, $email, $senha, $tipo);

if ($stmt->execute()) {
  echo "Cadastro realizado com sucesso! <a href='login.php'>Fazer login</a>";
} else {
  echo "Erro ao cadastrar: " . $conn->error;
}
?>
