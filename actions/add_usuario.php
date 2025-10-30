<?php
require_once '../includes/session_init.php';
include('../database.php');

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // 3. Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        header('Location: ../pages/add_usuario.php?erro=campos_vazios');
        exit;
    }

    if ($senha !== $senha_confirmar) {
        header('Location: ../pages/add_usuario.php?erro=senha');
        exit;
    }

    // 4. Verifica se e-mail ou CPF já existem
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? OR cpf = ?");
    $stmt_check->bind_param("ss", $email, $cpf);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Para simplificar, podemos usar um erro genérico de duplicidade
        header('Location: ../pages/add_usuario.php?erro=duplicado_email_cpf');
        exit;
    }
    $stmt_check->close();

    // 5. Insere o novo usuário no banco de dados
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    // Assumindo um perfil 'padrão' por default. Ajuste se necessário.
    $perfil = 'padrao';

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha, perfil) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nome, $email, $cpf, $telefone, $senha_hash, $perfil);

    if ($stmt->execute()) {
        header('Location: ../pages/usuarios.php?sucesso=1');
    } else {
        header('Location: ../pages/add_usuario.php?erro=inesperado');
    }

    $stmt->close();
    $conn->close();
    exit;
} else {
    // Redireciona se não for POST
    header('Location: ../pages/add_usuario.php');
    exit;
}
?>