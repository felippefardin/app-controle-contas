<?php
require_once '../includes/session_init.php';
include('../database.php');

if (!isset($_GET['id'])) {
    header('Location: ../pages/usuarios.php');
    exit;
}

$id = intval($_GET['id']);

// Recebe os dados do formulário
$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$cpf = trim($_POST['cpf']);
$telefone = trim($_POST['telefone']);
$senha = $_POST['senha'];
$senha_confirmar = $_POST['senha_confirmar'];

// Valida senha
if (!empty($senha) && $senha !== $senha_confirmar) {
    header("Location: ../pages/editar_usuario.php?id=$id&erro=senha");
    exit;
}

// Padroniza CPF (remove pontos e traços)
$cpf_clean = preg_replace('/[^\d]/', '', $cpf);

// Verifica duplicidade de e-mail em outros usuários
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email=? AND id<>?");
$stmt->bind_param("si", $email, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: ../pages/editar_usuario.php?id=$id&erro=duplicado_email");
    exit;
}
$stmt->close();

// Verifica duplicidade de CPF em outros usuários
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),'/','')=? AND id<>?");
$stmt->bind_param("si", $cpf_clean, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: ../pages/editar_usuario.php?id=$id&erro=duplicado_cpf");
    exit;
}
$stmt->close();

// Atualiza usuário
if (!empty($senha)) {
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, cpf=?, telefone=?, senha=? WHERE id=?");
    $stmt->bind_param("sssssi", $nome, $email, $cpf, $telefone, $senha_hash, $id);
} else {
    $stmt = $conn->prepare("UPDATE usuarios SET nome=?, email=?, cpf=?, telefone=? WHERE id=?");
    $stmt->bind_param("ssssi", $nome, $email, $cpf, $telefone, $id);
}

$stmt->execute();
$stmt->close();

header("Location: ../pages/usuarios.php?sucesso=1");
exit;
?>
