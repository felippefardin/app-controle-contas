<?php
require_once '../includes/config/config.php';

$id = $_POST['user_id'];
$senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
$plano = $_POST['novo_plano'];
$token = $_POST['token'];

$conn = getMasterConnection();

// Atualiza senha e limpa token
$sql = "UPDATE usuarios SET senha = ?, token_recovery = NULL WHERE id = ? AND token_recovery = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $senha, $id, $token);
$stmt->execute();
$stmt->close();
$conn->close();

// Redireciona para o checkout (ajuste o caminho conforme seu arquivo real de checkout)
header("Location: ../pages/checkout_plano.php?plano=$plano&user_id=$id&reactivate=1");
exit;
?>