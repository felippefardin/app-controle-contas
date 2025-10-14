<?php
require_once '../includes/session_init.php';
include('../database.php'); // A conexão $conn já é criada aqui.

// Apenas o usuário principal logado pode adicionar novos usuários
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Verifica se os dados foram enviados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
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

    // ---- VERIFICAÇÃO DE DUPLICIDADE ----
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        header("Location: ../pages/usuarios.php?erro=duplicado_email");
        exit;
    }
    $stmt->close();

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
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha, id_criador) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Verificação para garantir que a preparação da query funcionou
    if ($stmt === false) {
        die("ERRO AO PREPARAR A QUERY: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("sssssi", $nome, $email, $cpf, $telefone, $senha_hash, $id_criador);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../pages/usuarios.php?sucesso=1");
        exit;
    } else {
        // ****** MUDANÇA PRINCIPAL AQUI ******
        // Em vez de redirecionar, vamos mostrar o erro exato na tela.
        echo "<h1>Erro Crítico ao Salvar no Banco de Dados</h1>";
        echo "<p>Não foi possível criar o usuário. O banco de dados retornou o seguinte erro:</p>";
        echo "<pre style='background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Erro:</strong> " . htmlspecialchars($stmt->error);
        echo "<br>";
        echo "<strong>ID do Criador que tentou ser salvo:</strong> " . htmlspecialchars($id_criador);
        echo "</pre>";
        $stmt->close();
        exit; // Para o script aqui
    }
} else {
    // Redireciona se o formulário não for enviado via POST
    header("Location: ../pages/add_usuario.php");
    exit;
}

?>