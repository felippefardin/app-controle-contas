<?php
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include('../database.php');

$tipo = $_GET['tipo'] ?? '';
$status = $_GET['status'] ?? 'pendente'; // padrão: pendente

header('Content-Type: text/html; charset=utf-8');

// Consulta separando por status
$sql = "SELECT fornecedor, numero, valor, data_vencimento FROM contas_pagar WHERE status = ? ORDER BY data_vencimento ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $status);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Nenhum dado para exportar.");
}

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

$nomeArquivo = "contas_{$status}";

if ($tipo === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"{$nomeArquivo}.csv\"");

    $f = fopen('php://output', 'w');
    fputcsv($f, ['Fornecedor', 'Número', 'Valor', 'Data de Vencimento']);
    foreach ($dados as $linha) {
        fputcsv($f, $linha);
    }
    fclose($f);
    exit;
}

if ($tipo === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xls");

    echo "<table border='1'>";
    echo "<tr><th>Fornecedor</th><th>Número</th><th>Valor</th><th>Data de Vencimento</th></tr>";
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

if ($tipo === 'pdf') {
    $html = "<h2>Contas " . ucfirst($status) . "</h2><table border='1' cellpadding='5'><tr><th>Fornecedor</th><th>Número</th><th>Valor</th><th>Data de Vencimento</th></tr>";
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
