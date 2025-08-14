<?php

require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include('../database.php');

$tipo = $_GET['tipo'] ?? '';
$status = $_GET['status'] ?? 'baixada'; // aqui já consideramos baixadas
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

if (!in_array($tipo, ['pdf', 'excel', 'csv'])) {
    die("Tipo de exportação inválido.");
}

// Montar consulta básica
$sql = "SELECT fornecedor, numero, valor, data_baixa, forma_pagamento FROM contas_pagar WHERE status = ?";
$params = [$status];
$types = "s";

// Filtro por intervalo de data de baixa, se informado
if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND data_baixa BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
}

// Ordenar pela data de baixa
$sql .= " ORDER BY data_baixa ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_all(MYSQLI_ASSOC);

// Formata a data para padrão brasileiro dd/mm/aaaa
foreach ($dados as &$linha) {
    if (!empty($linha['data_baixa'])) {
        $date = DateTime::createFromFormat('Y-m-d', $linha['data_baixa']);
        if ($date !== false) {
            $linha['data_baixa'] = $date->format('d/m/Y');
        }
    }
}
unset($linha); // remove referência

// Nome do arquivo
$nomeArquivo = "contas_baixadas_" . (!empty($data_inicio) && !empty($data_fim) ? "{$data_inicio}_a_{$data_fim}_" : "") . date("YmdHis");

// Exportar para Excel
if ($tipo === 'excel') {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xlsx");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeçalhos
    $sheet->fromArray(['Fornecedor', 'Número', 'Valor', 'Data de Baixa', 'Forma de Pagamento'], NULL, 'A1');

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
    fputcsv($f, ['Fornecedor', 'Número', 'Valor', 'Data de Baixa', 'Forma de Pagamento']);

    foreach ($dados as $linha) {
        fputcsv($f, $linha);
    }
    fclose($f);
    exit;
}

// Exportar para PDF
if ($tipo === 'pdf') {
    $html = "<h2>Contas Baixadas</h2><table border='1' cellpadding='5'><tr><th>Fornecedor</th><th>Número</th><th>Valor</th><th>Data de Baixa</th><th>Forma de Pagamento</th></tr>";
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
