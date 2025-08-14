<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$cpf = trim($_POST['cpf']);
$telefone = trim($_POST['telefone']);
$senha = $_POST['senha'];
$senha_confirmar = $_POST['senha_confirmar'];

if ($senha !== $senha_confirmar) {
    header("Location: ../pages/usuarios.php?erro=senha");
    exit;
}

// Verifica duplicidade de email
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?erro=duplicado_email");
    exit;
}
$stmt->close();

// Verifica duplicidade de CPF (remove pontos e traço)
$cpf_clean = preg_replace('/[.-]/', '', $cpf);
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE REPLACE(REPLACE(cpf,'.',''),'-','') = ?");
$stmt->bind_param("s", $cpf_clean);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?erro=duplicado_cpf");
    exit;
}
$stmt->close();

// Insere usuário
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (nome,email,cpf,telefone,senha) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $nome, $email, $cpf, $telefone, $senha_hash);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?sucesso=1");
    exit;
} else {
    $stmt->close();
    echo "Erro ao salvar usuário: " . $conn->error;
}
?>
