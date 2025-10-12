<?php
require_once '../database.php';
header('Content-Type: application/json');

$report = $_GET['report'];

if ($report == 'fluxo_caixa') {
    // Lógica para o Fluxo de Caixa
    $labels = [];
    $entradas = [];
    $saidas = [];

    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d/m', strtotime($date));

        // Entradas (contas_receber)
        $sql = "SELECT SUM(valor) AS total FROM contas_receber WHERE data_recebimento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $entradas[] = $result['total'] ?? 0;
        $stmt->close();

        // Saídas (contas_pagar)
        $sql = "SELECT SUM(valor) AS total FROM contas_pagar WHERE data_pagamento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $saidas[] = $result['total'] ?? 0;
        $stmt->close();
    }

    echo json_encode(['labels' => $labels, 'entradas' => $entradas, 'saidas' => $saidas]);

} elseif ($report == 'pagar_categoria') {
    // Contas a pagar por categoria
    $sql = "SELECT categoria, SUM(valor) AS total FROM contas_pagar GROUP BY categoria";
    $result = $conn->query($sql);
    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['categoria'];
        $values[] = $row['total'];
    }
    echo json_encode(['labels' => $labels, 'values' => $values]);

} elseif ($report == 'despesas_fornecedor') {
    // Despesas por fornecedor
    $sql = "SELECT p.nome, SUM(cp.valor) AS total 
            FROM contas_pagar cp 
            JOIN pessoas p ON cp.id_pessoa_fornecedor = p.id 
            GROUP BY p.nome 
            ORDER BY total DESC 
            LIMIT 10";
    $result = $conn->query($sql);
    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['nome'];
        $values[] = $row['total'];
    }
    echo json_encode(['labels' => $labels, 'values' => $values]);
}
?>
