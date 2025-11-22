<?php
require_once '../includes/config/config.php';
require_once '../includes/session_init.php';
require_once '../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_logado'])) {
    echo json_encode(['sucesso' => false, 'msg' => 'Não logado']);
    exit;
}

$conn = getTenantConnection();
$usuario_id = $_SESSION['usuario_id'];
$lembrete_id = (int)$_POST['lembrete_id'];
$comentario = trim($_POST['comentario']);

if (!$comentario) {
    echo json_encode(['sucesso' => false]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO lembrete_comentarios (lembrete_id, usuario_id, comentario) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $lembrete_id, $usuario_id, $comentario);
    $stmt->execute();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false]);
}
?>