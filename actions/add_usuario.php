<?php
session_start();
include('../database.php');

// Apenas o usuário principal logado pode adicionar novos usuários
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: ../pages/login.php');
    exit;
}

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$senha = $_POST['senha'] ?? '';
$senha_confirmar = $_POST['senha_confirmar'] ?? '';

// ID do usuário principal (o criador)
$id_criador = $_SESSION['usuario_principal']['id'];

if ($senha !== $senha_confirmar) {
    header("Location: ../pages/usuarios.php?erro=senha");
    exit;
}

// Verifica duplicidade de email
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND (id = ? OR id_criador = ?)");
$stmt->bind_param("sii", $email, $id_criador, $id_criador);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?erro=duplicado_email");
    exit;
}
$stmt->close();

// Verifica duplicidade de CPF
$cpf_clean = preg_replace('/[.-]/', '', $cpf);
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE REPLACE(REPLACE(cpf,'.',''),'-','') = ? AND (id = ? OR id_criador = ?)");
$stmt->bind_param("sii", $cpf_clean, $id_criador, $id_criador);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?erro=duplicado_cpf");
    exit;
}
$stmt->close();

// Insere o novo usuário com o 'id_criador' correto
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha, id_criador) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $nome, $email, $cpf, $telefone, $senha_hash, $id_criador);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?sucesso=1");
    exit;
} else {
    $stmt->close();
    header("Location: ../pages/usuarios.php?erro=inesperado");
    exit;
}