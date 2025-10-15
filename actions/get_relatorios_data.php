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
        $sql_receber = "SELECT SUM(valor) AS total FROM contas_receber WHERE data_recebimento = ?";
        $stmt_receber = $conn->prepare($sql_receber);
        $stmt_receber->bind_param("s", $date);
        $stmt_receber->execute();
        $result_receber = $stmt_receber->get_result()->fetch_assoc();
        $total_receber = $result_receber['total'] ?? 0;
        $stmt_receber->close();

        // Entradas de caixa diário
        $sql_caixa = "SELECT SUM(valor) AS total FROM caixa_diario WHERE data = ?";
        $stmt_caixa = $conn->prepare($sql_caixa);
        $stmt_caixa->bind_param("s", $date);
        $stmt_caixa->execute();
        $result_caixa = $stmt_caixa->get_result()->fetch_assoc();
        $total_caixa = $result_caixa['total'] ?? 0;
        $stmt_caixa->close();

        // Soma as entradas de contas a receber e do caixa diário
        $entradas[] = $total_receber + $total_caixa;

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