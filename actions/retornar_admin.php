<?php
require_once '../includes/session_init.php';
include('../database.php');

// Verifica se há uma sessão de proprietário para a qual voltar
if (isset($_SESSION['proprietario_id_original'])) {
    $proprietario_id = $_SESSION['proprietario_id_original'];

    // Busca os dados do proprietário novamente
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $proprietario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($proprietario = $result->fetch_assoc()) {
        // Limpa a sessão atual
        session_unset();
        session_destroy();

        // Inicia uma nova sessão para o proprietário
        session_start();
        $_SESSION['proprietario'] = $proprietario;
        session_write_close();

        // Redireciona de volta para a página de administração
        header('Location: ../pages/admin/selecionar_conta.php');
        exit;
    }
}

// Se algo der errado, redireciona para o login
header('Location: ../pages/login.php');
exit;
?>