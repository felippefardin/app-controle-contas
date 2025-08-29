<?php
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

include('../database.php');

$conn = getConnPrincipal();

$tipo = $_GET['tipo'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

if (!in_array($tipo, ['pdf', 'excel', 'csv'])) {
    die("Tipo de exportação inválido.");
}

$nomeArquivo = "contas_receber_baixadas";

// Monta a SQL
$sql = "SELECT responsavel, numero, valor, data_baixa, forma_pagamento FROM contas_receber WHERE status = 'baixada'";
$params = [];
$types = "";

// Filtro por data de baixa
if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND data_baixa BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
}

$sql .= " ORDER BY data_baixa ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Nenhum dado para exportar.");
}

$dados = [];
while ($row = $result->fetch_assoc()) {
    // Formatar data para dd/mm/yyyy
    if (!empty($row['data_baixa'])) {
        $date = DateTime::createFromFormat('Y-m-d', $row['data_baixa']);
        if ($date) {
            $row['data_baixa'] = $date->format('d/m/Y');
        }
    }
    $dados[] = $row;
}

// --- Exportar CSV ---
if ($tipo === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"{$nomeArquivo}.csv\"");
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Responsável', 'Número', 'Valor', 'Data de Baixa', 'Forma de Pagamento']);
    foreach ($dados as $linha) {
        fputcsv($f, $linha);
    }
    fclose($f);
    exit;
}

// --- Exportar Excel ---
if ($tipo === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xls");
    echo "<table border='1'>";
    echo "<tr><th>Responsável</th><th>Número</th><th>Valor</th><th>Data de Baixa</th><th>Forma de Pagamento</th></tr>";
    foreach ($dados as $linha) {
        echo "<tr>";
        foreach ($linha as $valor) {
            echo "<td>" . htmlspecialchars($valor) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// --- Exportar PDF ---
if ($tipo === 'pdf') {
    $html = "<h2>Contas a Receber Baixadas</h2><table border='1' cellpadding='5'>
                <tr><th>Responsável</th><th>Número</th><th>Valor</th><th>Data de Baixa</th><th>Forma de Pagamento</th></tr>";
    foreach ($dados as $linha) {
        $html .= '<tr>';
        foreach ($linha as $valor) {
            $html .= '<td>' . htmlspecialchars($valor) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("{$nomeArquivo}.pdf", ["Attachment" => true]);
    exit;
}

echo "Tipo de exportação inválido.";
?>
