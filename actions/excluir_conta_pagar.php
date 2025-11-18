<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. OBTÉM DADOS
$id_conta = isset($_GET['id']) ? intval($_GET['id']) : 0;
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : ''; // Pega o parâmetro de redirecionamento
$id_usuario = $_SESSION['usuario_id'];

if ($id_conta > 0) {
    $conn = getTenantConnection();
    
    if ($conn) {
        // Prepara a exclusão garantindo que a conta pertence ao usuário logado
        $stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id_conta, $id_usuario);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Conta excluída com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao excluir a conta: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
    }
} else {
    $_SESSION['error_message'] = "Conta inválida.";
}

// 3. REDIRECIONAMENTO INTELIGENTE
if ($redirect === 'baixadas') {
    header('Location: ../pages/contas_pagar_baixadas.php');
} else {
    header('Location: ../pages/contas_pagar.php');
}
exit;
?>