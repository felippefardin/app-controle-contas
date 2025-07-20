<?php

require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include('../database.php');

$tipo = $_GET['tipo'] ?? '';
$status = $_GET['status'] ?? 'pendente';
$data = $_GET['data'] ?? '';

if (!in_array($tipo, ['pdf', 'excel', 'csv'])) {
    die("Tipo de exportação inválido.");
}

// Montar consulta
$sql = "SELECT fornecedor, numero, valor, data_vencimento FROM contas_pagar WHERE status = ?";
$params = [$status];
$types = "s";

if (!empty($data)) {
    $sql .= " AND data_vencimento = ?";
    $params[] = $data;
    $types .= "s";
}

$sql .= " ORDER BY data_vencimento ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_all(MYSQLI_ASSOC);

// Nome do arquivo
$nomeArquivo = "contas_{$status}" . (!empty($data) ? "_{$data}" : "") . "_" . date("YmdHis");

// Exportar para Excel
if ($tipo === 'excel') {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xlsx");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeçalhos
    $sheet->fromArray(['Fornecedor', 'Número', 'Valor', 'Data de Vencimento'], NULL, 'A1');

    // Dados
    $linha = 2;
    foreach ($dados as $linhaDados) {
        $sheet->fromArray(array_values($linhaDados), NULL, "A{$linha}");
        $linha++;
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

// Exportar para CSV
if ($tipo === 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.csv");

    $f = fopen('php://output', 'w');
    fputcsv($f, ['Fornecedor', 'Número', 'Valor', 'Data de Vencimento']);

    foreach ($dados as $linha) {
        fputcsv($f, $linha);
    }
    fclose($f);
    exit;
}

// Exportar para PDF
if ($tipo === 'pdf') {
    $html = "<h2>Contas " . ucfirst($status) . "</h2><table border='1' cellpadding='5'><tr><th>Fornecedor</th><th>Número</th><th>Valor</th><th>Data de Vencimento</th></tr>";
    foreach ($dados as $linha) {
        $html .= "<tr>";
        foreach ($linha as $valor) {
            $html .= "<td>" . htmlspecialchars($valor) . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</table>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("{$nomeArquivo}.pdf", ["Attachment" => true]);
    exit;
}

echo "Tipo de exportação não suportado.";
