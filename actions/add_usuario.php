<?php
session_start();
include('../database.php'); // A conexão $conn já é criada aqui.

// Apenas o usuário principal logado pode adicionar novos usuários
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: ../pages/login.php');
    exit;
}

// OS CAMPOS FORAM REMOVIDOS DAQUI, POIS O ARQUIVO actions/add_usuario.php NÃO PRECISA DELES.

// Verifica se os dados foram enviados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? '')); // É uma boa prática salvar e-mails em minúsculo
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // ID do usuário principal (o criador)
    $id_criador = $_SESSION['usuario_principal']['id'];

    if (empty($nome) || empty($email) || empty($senha)) {
        header("Location: ../pages/usuarios.php?erro=campos_vazios");
        exit;
    }
    
    if ($senha !== $senha_confirmar) {
        header("Location: ../pages/usuarios.php?erro=senha");
        exit;
    }

    // ---- VERIFICAÇÃO DE DUPLICIDADE CORRIGIDA ----
    // Verifica duplicidade de email na tabela inteira
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        header("Location: ../pages/usuarios.php?erro=duplicado_email");
        exit;
    }
    $stmt->close();

    // Verifica duplicidade de CPF na tabela inteira (se CPF for obrigatório)
    if (!empty($cpf)) {
        $cpf_clean = preg_replace('/[.-]/', '', $cpf);
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE REPLACE(REPLACE(cpf,'.',''),'-','') = ?");
        $stmt->bind_param("s", $cpf_clean);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            header("Location: ../pages/usuarios.php?erro=duplicado_cpf");
            exit;
        }
        $stmt->close();
    }

    // ---- INSERÇÃO NO BANCO ----
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
        // Para depuração, você pode querer ver o erro: error_log($stmt->error);
        header("Location: ../pages/usuarios.php?erro=inesperado");
        exit;
    }
} else {
    // Redireciona se o formulário não for enviado via POST
    header("Location: ../pages/add_usuario.php");
    exit;
}

?>