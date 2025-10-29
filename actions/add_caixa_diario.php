<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega a conexão correta para o cliente
    $conn = getTenantConnection();
    if ($conn === null) {
        header("Location: ../pages/lancamento_caixa.php?error=db_connection");
        exit;
    }

    $data = $_POST['data'];
    $valor = $_POST['valor'];
    $usuarioId = $_SESSION['usuario_logado']['id'];

    // 3. LÓGICA PARA INSERIR OU ATUALIZAR O CAIXA
    // (Lembre-se da chave única na tabela `caixa_diario` para `data` e `usuario_id`)
    $sql = "INSERT INTO caixa_diario (data, valor, usuario_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = valor + VALUES(valor)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header("Location: ../pages/lancamento_caixa.php?error=prepare_failed");
        exit;
    }

    $stmt->bind_param("sdi", $data, $valor, $usuarioId);

    if ($stmt->execute()) {
        header("Location: ../pages/lancamento_caixa.php?success=1");
    } else {
        header("Location: ../pages/lancamento_caixa.php?error=execute_failed");
    }

    $stmt->close();
    exit;
} else {
    // Redireciona se não for POST
    header('Location: ../pages/lancamento_caixa.php');
    exit;
}
?>