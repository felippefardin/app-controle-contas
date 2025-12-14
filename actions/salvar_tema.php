<?php
// ARQUIVO: actions/salvar_tema.php
include '../includes/session_init.php';
include '../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Não logado']);
    exit;
}

$tema = $_POST['tema'] ?? 'dark';
if (!in_array($tema, ['dark', 'light'])) $tema = 'dark';

$conn = getTenantConnection(); // Ou getMasterConnection dependendo de onde está a tabela usuarios
// Fallback se não conseguir conexão tenant, tenta master se usuários forem centralizados
if (!$conn) $conn = getMasterConnection();

$stmt = $conn->prepare("UPDATE usuarios SET tema_preferencia = ? WHERE id = ?");
$stmt->bind_param("si", $tema, $_SESSION['usuario_id']);

if ($stmt->execute()) {
    $_SESSION['tema_preferencia'] = $tema; // Atualiza sessão
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error']);
}
?>