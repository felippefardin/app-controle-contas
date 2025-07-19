<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe e limpa os dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // Validações básicas
    if (!$nome || !$email || !$cpf || !$telefone || !$senha || !$senha_confirmar) {
        die("Por favor, preencha todos os campos.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("E-mail inválido.");
    }

    if ($senha !== $senha_confirmar) {
        die("As senhas não conferem.");
    }

    // Verifica se o e-mail já existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Este e-mail já está cadastrado.");
    }
    $stmt->close();

    // Cria hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Insere no banco
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome, $email, $cpf, $telefone, $senha_hash);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: usuarios.php"); 
        exit;
    } else {
        die("Erro ao salvar usuário: " . $conn->error);
    }
} else {
   
    header("Location: usuarios.php");
    exit;
}
?>
