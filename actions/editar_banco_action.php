<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        header('Location: ../pages/banco_cadastro.php?erro_edicao=db_connection');
        exit;
    }

    // --- CORREÇÃO AQUI ---
    // Pega o ID do usuário da variável correta de sessão
    $id_usuario = $_SESSION['usuario_id'];
    
    // Pega os dados do formulário
    $id = $_POST['id'] ?? 0;
    $nome_banco = $_POST['nome_banco'] ?? '';
    $agencia = $_POST['agencia'] ?? '';
    $conta = $_POST['conta'] ?? '';
    $tipo_conta = $_POST['tipo_conta'] ?? '';
    $chave_pix = $_POST['chave_pix'] ?? '';

    // 3. ATUALIZA OS DADOS NO BANCO COM SEGURANÇA
    if (!empty($id) && !empty($id_usuario)) {
        $stmt = $conn->prepare("UPDATE contas_bancarias SET nome_banco=?, agencia=?, conta=?, tipo_conta=?, chave_pix=? WHERE id=? AND id_usuario=?");
        
        if ($stmt) {
            $stmt->bind_param("sssssii", $nome_banco, $agencia, $conta, $tipo_conta, $chave_pix, $id, $id_usuario);
            
            if ($stmt->execute()) {
                header('Location: ../pages/banco_cadastro.php?sucesso_edicao=1');
            } else {
                header('Location: ../pages/banco_cadastro.php?erro_edicao=1');
            }
            $stmt->close();
        } else {
            header('Location: ../pages/banco_cadastro.php?erro_edicao=prepare_error');
        }
    } else {
        header('Location: ../pages/banco_cadastro.php?erro_edicao=invalid_id');
    }

    $conn->close();
    exit;
} else {
    header('Location: ../pages/banco_cadastro.php');
    exit;
}
?>