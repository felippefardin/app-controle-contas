<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Conexão com o banco principal
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Coleta e sanitiza os dados, usando null coalescing para evitar undefined index
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$senha = $_POST['senha'] ?? '';
$senha_confirmar = $_POST['senha_confirmar'] ?? '';

// ID do usuário principal logado
$ownerId = $_SESSION['usuario']['id'];

if ($senha !== $senha_confirmar) {
    header("Location: ../pages/usuarios.php?erro=senha");
    exit;
}

// Verifica duplicidade de email
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND owner_id = ?");
$stmt->bind_param("si", $email, $ownerId);
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
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE REPLACE(REPLACE(cpf,'.',''),'-','') = ? AND owner_id = ?");
$stmt->bind_param("si", $cpf_clean, $ownerId);
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
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha, owner_id) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("sssssi", $nome, $email, $cpf, $telefone, $senha_hash, $ownerId);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: ../pages/usuarios.php?sucesso=1");
    exit;
} else {
    $stmt->close();
    echo "Erro ao salvar usuário: " . $conn->error;
}