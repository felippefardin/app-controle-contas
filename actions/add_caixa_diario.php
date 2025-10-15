<?php
require_once '../includes/session_init.php'; // Adicione para ter acesso à sessão
require_once '../database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    // Redireciona para o login se não estiver logado
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];
    $valor = $_POST['valor'];
    $usuarioId = $_SESSION['usuario']['id']; // Pega o ID do usuário da sessão

    // A lógica ON DUPLICATE KEY UPDATE precisa ser ajustada para a chave composta (data, usuario_id)
    // Primeiro, vamos garantir que a tabela tenha uma chave única para data e usuario_id
    // Se você ainda não fez, execute: ALTER TABLE caixa_diario ADD UNIQUE KEY (data, usuario_id);

    $sql = "INSERT INTO caixa_diario (data, valor, usuario_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = valor + VALUES(valor)";
    $stmt = $conn->prepare($sql);
    // Adiciona o bind_param para o usuario_id
    $stmt->bind_param("sdi", $data, $valor, $usuarioId);

    if ($stmt->execute()) {
        header("Location: ../pages/lancamento_caixa.php?success=1");
    } else {
        header("Location: ../pages/lancamento_caixa.php?error=1");
    }
    $stmt->close();
}
?>