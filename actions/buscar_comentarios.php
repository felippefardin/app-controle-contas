<?php
require_once '../includes/config/config.php';
require_once '../includes/session_init.php';
require_once '../database.php';

$conn = getTenantConnection();
$id = (int)$_GET['id'];

$sql = "SELECT c.*, u.nome FROM lembrete_comentarios c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.lembrete_id = ? ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo '<div class="comentario-item">';
        echo '<div class="comentario-header">' . htmlspecialchars($row['nome']) . ' <span class="comentario-data">' . date('d/m H:i', strtotime($row['created_at'])) . '</span></div>';
        echo '<p class="comentario-texto">' . nl2br(htmlspecialchars($row['comentario'])) . '</p>';
        echo '</div>';
    }
} else {
    echo '<p class="text-center text-muted mt-3">Seja o primeiro a comentar!</p>';
}
?>