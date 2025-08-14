<?php
// pages/exportar_contas_pagar.php

require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include('../database.php');

// Parâmetros
$tipo        = $_GET['tipo']        ?? '';
$status      = $_GET['status']      ?? 'pendente'; // exclusivo para contas a pagar pendentes
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim']    ?? '';

if (!in_array($tipo, ['pdf', 'excel', 'csv'])) {
    http_response_code(400);
    exit('Tipo de exportação inválido.');
}

// Monta consulta (somente pendentes da contas_pagar, filtrando por data_vencimento)
$sql    = "SELECT fornecedor, numero, valor, data_vencimento FROM contas_pagar WHERE status = ?";
$params = [$status];
$types  = "s";

if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND data_vencimento BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types   .= "ss";
} elseif (!empty($data_inicio)) {
    $sql .= " AND data_vencimento >= ?";
    $params[] = $data_inicio;
    $types   .= "s";
} elseif (!empty($data_fim)) {
    $sql .= " AND data_vencimento <= ?";
    $params[] = $data_fim;
    $types   .= "s";
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

// Formata data (dd/mm/aaaa) para saída humana (PDF/CSV). No Excel manteremos o bruto.
$dados_formatados = [];
foreach ($dados as $linha) {
    $linha_fmt = $linha;
    if (!empty($linha['data_vencimento'])) {
        $dt = DateTime::createFromFormat('Y-m-d', $linha['data_vencimento']);
        if ($dt) $linha_fmt['data_vencimento'] = $dt->format('d/m/Y');
    }
    // Valor com 2 casas para PDF/CSV
    if (isset($linha_fmt['valor'])) {
        $linha_fmt['valor'] = number_format((float)$linha_fmt['valor'], 2, ',', '.');
    }
    $dados_formatados[] = $linha_fmt;
}
unset($linha, $linha_fmt);

// Nome do arquivo
$faixa = (!empty($data_inicio) || !empty($data_fim)) ? "{$data_inicio}_a_{$data_fim}_" : "";
$nomeArquivo = "contas_pagar_pendentes_{$faixa}" . date("YmdHis");

// ---- EXCEL (.xlsx) ----
if ($tipo === 'excel') {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xlsx");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeçalhos
    $headers = ['Fornecedor', 'Número', 'Valor', 'Vencimento'];
    $sheet->fromArray($headers, null, 'A1');

    // Dados: para Excel, envie valores brutos e converta data para formato Excel se desejar
    $row = 2;
    foreach ($dados as $d) {
        // Valor como número
        $valor = is_numeric($d['valor']) ? (float)$d['valor'] : $d['valor'];

        // Data em texto (ou poderia converter para DateTime Excel)
        $venc = $d['data_vencimento'];

        $sheet->fromArray(
            [
                $d['fornecedor'],
                $d['numero'],
                $valor,
                $venc
            ],
            null,
            "A{$row}"
        );
        $row++;
    }

    // Formatação básica de cabeçalho
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

// ---- CSV (.csv) ----
if ($tipo === 'csv') {
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.csv");
    // BOM para Excel no Windows ler acentuação
    echo "\xEF\xBB\xBF";

    $f = fopen('php://output', 'w');
    fputcsv($f, ['Fornecedor', 'Número', 'Valor', 'Vencimento']);

    foreach ($dados_formatados as $linha) {
        fputcsv($f, [
            $linha['fornecedor'],
            $linha['numero'],
            $linha['valor'],
            $linha['data_vencimento']
        ]);
    }
    fclose($f);
    exit;
}

// ---- PDF (.pdf) ----
if ($tipo === 'pdf') {
    // Tabela simples com estilos embutidos
    $html  = "<style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
                h2 { text-align:center; margin-bottom:12px; }
                table { width:100%; border-collapse: collapse; }
                th, td { border:1px solid #ccc; padding:6px; }
                th { background:#f0f0f0; text-align:left; }
              </style>";
    $html .= "<h2>Contas a Pagar - Pendentes</h2>";
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
