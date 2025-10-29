<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
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

    // Pega o ID do usuário da sessão correta
    $id_usuario = $_SESSION['usuario_logado']['id'];
    
    // Pega os dados do formulário
    $id = $_POST['id'];
    $nome_banco = $_POST['nome_banco'];
    $agencia = $_POST['agencia'];
    $conta = $_POST['conta'];
    $tipo_conta = $_POST['tipo_conta'];
    $chave_pix = $_POST['chave_pix'];

    // 3. ATUALIZA OS DADOS NO BANCO COM SEGURANÇA
    $stmt = $conn->prepare("UPDATE contas_bancarias SET nome_banco=?, agencia=?, conta=?, tipo_conta=?, chave_pix=? WHERE id=? AND id_usuario=?");
    $stmt->bind_param("sssssii", $nome_banco, $agencia, $conta, $tipo_conta, $chave_pix, $id, $id_usuario);
    
    if ($stmt->execute()) {
        header('Location: ../pages/banco_cadastro.php?sucesso_edicao=1');
    } else {
        header('Location: ../pages/banco_cadastro.php?erro_edicao=1');
    }
    $stmt->close();
    exit;
} else {
    header('Location: ../pages/banco_cadastro.php');
    exit;
}
?>