<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

include('../includes/header.php');
include('../database.php');

echo "<h2>Contas a Receber - Baixadas</h2>";

$sql = "SELECT c.*, u.nome AS usuario_baixou 
        FROM contas_receber_baixadas c 
        LEFT JOIN usuarios u ON c.usuario_id = u.id 
        ORDER BY data_baixa DESC";

$result = $conn->query($sql);

if ($result === false) {
    echo "Erro na consulta: " . $conn->error;
} else {
    echo "<table border='1' width='100%' style='margin-top:20px;'>";
    echo "<tr>
            <th>Responsável</th>
            <th>Vencimento</th>
            <th>Número</th>
            <th>Valor</th>
            <th>Forma de Pagamento</th>
            <th>Data de Baixa</th>
            <th>Usuário</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['responsavel']) . "</td>";
        echo "<td>" . htmlspecialchars($row['data_vencimento']) . "</td>";
        echo "<td>" . htmlspecialchars($row['numero']) . "</td>";
        echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($row['forma_pagamento']) . "</td>";
        echo "<td>" . htmlspecialchars($row['data_baixa']) . "</td>";
        echo "<td>" . htmlspecialchars($row['usuario_baixou']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo '<p><a href="home.php">Voltar</a></p>';

include('../includes/footer.php');
?>
