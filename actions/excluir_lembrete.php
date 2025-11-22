<?php
require_once '../includes/session_init.php';
require_once '../database.php'; 

// Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.php");
    exit;
}

// Pega dados da sessão
$usuario_id = $_SESSION['usuario_id']; 
$tenant_id  = $_SESSION['tenant_id'];

// Conexão com o banco do tenant
$conn = getTenantConnection();
if (!$conn) {
    die("Erro ao conectar ao banco do tenant.");
}

// ID do lembrete
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if ($id) {
    $stmt = $conn->prepare("DELETE FROM lembretes WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success'>Lembrete removido!</div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao excluir lembrete.</div>";
    }

    $stmt->close();
}

header('Location: ../pages/lembrete.php');
exit;
