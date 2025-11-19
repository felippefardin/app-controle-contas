<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN
// Verifica se a sessão está ativa e se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        header('Location: ../pages/banco_cadastro.php?erro=db_connection');
        exit;
    }

    // CORREÇÃO AQUI:
    // O ID do usuário agora fica em $_SESSION['usuario_id'], não dentro de ['usuario_logado']
    $id_usuario = $_SESSION['usuario_id'];

    // Validação extra para garantir que o ID não seja nulo
    if (empty($id_usuario)) {
        // Se por algum motivo a sessão perdeu o ID, redireciona para login
        header('Location: ../pages/login.php?error=session_error');
        exit;
    }
    
    // Pega os dados do formulário
    $nome_banco = $_POST['nome_banco'] ?? '';
    $agencia = $_POST['agencia'] ?? '';
    $conta = $_POST['conta'] ?? '';
    $tipo_conta = $_POST['tipo_conta'] ?? '';
    $chave_pix = $_POST['chave_pix'] ?? '';

    // 3. INSERE OS DADOS NO BANCO
    // Prepara a query
    $stmt = $conn->prepare("INSERT INTO contas_bancarias (id_usuario, nome_banco, agencia, conta, tipo_conta, chave_pix) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("isssss", $id_usuario, $nome_banco, $agencia, $conta, $tipo_conta, $chave_pix);
        
        if ($stmt->execute()) {
            header('Location: ../pages/banco_cadastro.php?sucesso=1');
        } else {
            // Log do erro para debug se necessário: error_log($stmt->error);
            header('Location: ../pages/banco_cadastro.php?erro=1');
        }
        $stmt->close();
    } else {
        header('Location: ../pages/banco_cadastro.php?erro=prepare');
    }
    
    $conn->close();
    exit;
} else {
    header('Location: ../pages/banco_cadastro.php');
    exit;
}
?>