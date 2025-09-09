<?php
// pages/exportar_contas_pagar.php

require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include('../database.php'); // database.php deve definir $conn

// Confere se a conexão existe
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "app_controle_contas");
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }
}

// Parâmetros
$tipo        = $_GET['tipo']        ?? '';
$status      = $_GET['status']      ?? 'pendente'; 
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim']    ?? '';

// Valida tipo
if (!in_array($tipo, ['pdf', 'excel', 'csv'])) {
    http_response_code(400);
    exit('Tipo de exportação inválido.');
}

// Monta SQL
$sql    = "SELECT fornecedor, numero, valor, data_vencimento FROM contas_pagar WHERE status = ?";
$params = [$status];
$types  = "s";

if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND data_vencimento BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
} elseif (!empty($data_inicio)) {
    $sql .= " AND data_vencimento >= ?";
    $params[] = $data_inicio;
    $types .= "s";
} elseif (!empty($data_fim)) {
    $sql .= " AND data_vencimento <= ?";
    $params[] = $data_fim;
    $types .= "s";
}

$sql .= " ORDER BY data_vencimento ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("Erro ao preparar consulta: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$dados  = $result->fetch_all(MYSQLI_ASSOC);

// Formata dados para CSV/PDF
$dados_formatados = [];
foreach ($dados as $linha) {
    $linha_fmt = $linha;
    if (!empty($linha['data_vencimento'])) {
        $dt = DateTime::createFromFormat('Y-m-d', $linha['data_vencimento']);
        if ($dt) $linha_fmt['data_vencimento'] = $dt->format('d/m/Y');
    }
    if (isset($linha_fmt['valor'])) {
        $linha_fmt['valor'] = number_format((float)$linha_fmt['valor'], 2, ',', '.');
    }
    $dados_formatados[] = $linha_fmt;
}
unset($linha, $linha_fmt);

// Nome do arquivo
$faixa = (!empty($data_inicio) || !empty($data_fim)) ? "{$data_inicio}_a_{$data_fim}_" : "";
$nomeArquivo = "contas_pagar_{$status}_{$faixa}" . date("YmdHis");

// ---- EXCEL ----
if ($tipo === 'excel') {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xlsx");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeçalhos
    $sheet->fromArray(['Fornecedor', 'Número', 'Valor', 'Vencimento'], NULL, 'A1');

    // Dados
    $row = 2;
    foreach ($dados as $d) {
        $valor = is_numeric($d['valor']) ? (float)$d['valor'] : $d['valor'];
        $venc  = $d['data_vencimento'];
        $sheet->fromArray([$d['fornecedor'], $d['numero'], $valor, $venc], NULL, "A{$row}");
        $row++;
    }
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

// ---- CSV ----
if ($tipo === 'csv') {
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.csv");
    echo "\xEF\xBB\xBF"; // BOM UTF-8

    $f = fopen('php://output', 'w');
    fputcsv($f, ['Fornecedor', 'Número', 'Valor', 'Vencimento']);

    foreach ($dados_formatados as $linha) {
        fputcsv($f, [$linha['fornecedor'], $linha['numero'], $linha['valor'], $linha['data_vencimento']]);
    }
    fclose($f);
    exit;
}

// ---- PDF ----
if ($tipo === 'pdf') {
    $html  = "<style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
                h2 { text-align:center; margin-bottom:12px; }
                table { width:100%; border-collapse: collapse; }
                th, td { border:1px solid #ccc; padding:6px; }
                th { background:#f0f0f0; text-align:left; }
              </style>";
    $html .= "<h2>Contas a Pagar - {$status}</h2>";
    if (!empty($data_inicio) || !empty($data_fim)) {
        $html .= "<p><strong>Período:</strong> " . htmlspecialchars($data_inicio ?: '...') .
                 " a " . htmlspecialchars($data_fim ?: '...') . "</p>";
    }
    $html .= "<table><thead><tr>
                <th>Fornecedor</th>
                <th>Número</th>
                <th>Valor</th>
                <th>Vencimento</th>
              </tr></thead><tbody>";

    foreach ($dados_formatados as $linha) {
        $html .= "<tr>
                    <td>" . htmlspecialchars($linha['fornecedor']) . "</td>
                    <td>" . htmlspecialchars($linha['numero']) . "</td>
                    <td>" . htmlspecialchars($linha['valor']) . "</td>
                    <td>" . htmlspecialchars($linha['data_vencimento']) . "</td>
                  </tr>";
    }
    $html .= "</tbody></table>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("{$nomeArquivo}.pdf", ["Attachment" => true]);
    exit;
}

echo "Tipo de exportação não suportado.";
?>
