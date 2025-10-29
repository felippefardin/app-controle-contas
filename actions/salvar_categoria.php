<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        header('Location: ../pages/categorias.php?erro=db_connection');
        exit;
    }

    // Pega os dados da sessão e do formulário
    $usuarioId = $_SESSION['usuario_logado']['id'];
    $nome = trim($_POST['nome']);
    $tipo = $_POST['tipo'];
    $id = $_POST['id']; // Pode estar vazio se for uma nova categoria

    if (empty($nome) || empty($tipo)) {
        header('Location: ../pages/categorias.php?erro=empty_fields');
        exit;
    }

    $stmt = false; // Inicializa a variável
    
    // 3. LÓGICA PARA INSERIR OU ATUALIZAR
    if (empty($id)) { 
        // Inserir nova categoria
        $stmt = $conn->prepare("INSERT INTO categorias (id_usuario, nome, tipo) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $usuarioId, $nome, $tipo);
    } else { 
        // Atualizar categoria existente (com verificação de segurança)
        $stmt = $conn->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("ssii", $nome, $tipo, $id, $usuarioId);
    }

    if ($stmt) {
        if ($stmt->execute()) {
             header('Location: ../pages/categorias.php?sucesso=1');
        } else {
             header('Location: ../pages/categorias.php?erro=execute_failed');
        }
        $stmt->close();
    } else {
        header('Location: ../pages/categorias.php?erro=prepare_failed');
    }
    
    exit;
}

// Se não for POST, apenas redireciona de volta
header('Location: ../pages/categorias.php');
exit;
?>