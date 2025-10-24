<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_GET['id'])) {
    echo "ID da venda não informado.";
    exit;
}

$id = intval($_GET['id']);

// Deleta os itens da venda primeiro
$conn->query("DELETE FROM venda_items WHERE id_venda = $id");

// Deleta a venda
if ($conn->query("DELETE FROM vendas WHERE id = $id")) {
    echo "Venda cancelada com sucesso!";
} else {
    echo "Erro ao cancelar venda.";
}
?>
