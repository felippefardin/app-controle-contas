<?php
require '../database.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? 0;
if (!$id) {
    echo json_encode(["success" => false, "message" => "Venda inválida."]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM venda_items WHERE id_venda = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt2 = $conn->prepare("DELETE FROM vendas WHERE id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();

echo json_encode(["success" => true, "message" => "Venda cancelada com sucesso!"]);
