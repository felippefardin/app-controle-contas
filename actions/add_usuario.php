<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Alterado para require_once

// --- Início da Correção ---

// 1. Verifica se o usuário está logado com a sessão correta
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. Obtém a conexão correta com o banco de dados
$conn = getTenantConnection();
if ($conn === null) {
    // Redireciona com um erro específico de banco de dados
    header('Location: ../pages/add_usuario.php?erro=db_error');
    exit;
}

// --- Fim da Correção ---

// 3. Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // 4. Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        header('Location: ../pages/add_usuario.php?erro=campos_vazios');
        exit;
    }

    if ($senha !== $senha_confirmar) {
        header('Location: ../pages/add_usuario.php?erro=senha');
        exit;
    }

    // 5. Verifica se e-mail ou CPF já existem
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? OR cpf = ?");
    $stmt_check->bind_param("ss", $email, $cpf);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        // Adicionado para verificar qual campo está duplicado
        $stmt_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_email->bind_param("s", $email);
        $stmt_email->execute();
        $stmt_email->store_result();
        if ($stmt_email->num_rows > 0) {
            header('Location: ../pages/add_usuario.php?erro=duplicado_email');
            exit;
        }

        $stmt_cpf = $conn->prepare("SELECT id FROM usuarios WHERE cpf = ?");
        $stmt_cpf->bind_param("s", $cpf);
        $stmt_cpf->execute();
        $stmt_cpf->store_result();
        if ($stmt_cpf->num_rows > 0) {
            header('Location: ../pages/add_usuario.php?erro=duplicado_cpf');
            exit;
        }
    }
    $stmt_check->close();


    // 6. Insere o novo usuário no banco de dados
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $perfil = 'padrao'; // Perfil padrão (CORRETO)

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