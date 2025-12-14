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

// 1. Salva na Sessão PHP
$_SESSION['tema_preferencia'] = $tema;

// 2. Salva no Cookie via PHP (Backup caso o JS falhe ou para sincronia)
// Validade de 30 dias (86400 * 30)
setcookie('tema_preferencia', $tema, time() + (86400 * 30), "/"); 

// 3. Salva no Banco de Dados
$conn = getTenantConnection(); 
if (!$conn) $conn = getMasterConnection();

if ($conn) {
    $stmt = $conn->prepare("UPDATE usuarios SET tema_preferencia = ? WHERE id = ?");
    $stmt->bind_param("si", $tema, $_SESSION['usuario_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Erro BD']);
    }
} else {
    echo json_encode(['status' => 'success', 'msg' => 'Salvo apenas sessão/cookie']);
}
?>