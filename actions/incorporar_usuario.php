<?php
require_once '../includes/session_init.php';
include('../database.php');

// Apenas o proprietário pode executar esta ação
if (!isset($_SESSION['proprietario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if (isset($_GET['id'])) {
    $usuario_id = (int)$_GET['id'];

    // Busca os dados do usuário a ser incorporado
    $sql = "SELECT * FROM usuarios WHERE id = ? AND id_criador IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($usuario_a_incorporar = $result->fetch_assoc()) {
        // Guarda o ID do proprietário para poder voltar depois
        $_SESSION['proprietario_id_original'] = $_SESSION['proprietario']['id'];
        
        // Simula o login do usuário principal
        $_SESSION['usuario_principal'] = $usuario_a_incorporar;

        // Redireciona para a página de seleção de usuário (ou direto para a home se não houver sub-usuários)
        header('Location: ../pages/selecionar_usuario.php');
        exit;
    }
}

// Se algo der errado, volta para a página de administração
header('Location: ../admin/selecionar_conta.php');
exit;
?>