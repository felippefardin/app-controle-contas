<?php

require '../vendor/autoload.php';
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include('../database.php');

// Conexão com o banco principal
$conn = getConnPrincipal();

$tipo        = $_GET['tipo'] ?? '';
$status      = $_GET['status'] ?? 'pendente';
$responsavel = $_GET['responsavel'] ?? '';
$numero      = $_GET['numero'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim'] ?? '';

if (!in_array($tipo, ['pdf', 'excel', 'csv'])) {
    die("Tipo de exportação inválido.");
}

// Montar consulta básica
$sql = "SELECT responsavel, numero, valor, data_vencimento, status FROM contas_receber WHERE 1=1";
$params = [];
$types = "";

// Filtrar status
if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

// Filtrar responsável
if (!empty($responsavel)) {
    $sql .= " AND responsavel LIKE ?";
    $params[] = "%$responsavel%";
    $types .= "s";
}

// Filtrar número
if (!empty($numero)) {
    $sql .= " AND numero LIKE ?";
    $params[] = "%$numero%";
    $types .= "s";
}

// Filtrar intervalo de data de vencimento
if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND data_vencimento BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
}

// Ordenar
$sql .= " ORDER BY data_vencimento ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_all(MYSQLI_ASSOC);

// Formatar data
foreach ($dados as &$linha) {
    if (!empty($linha['data_vencimento'])) {
        $date = DateTime::createFromFormat('Y-m-d', $linha['data_vencimento']);
        if ($date) {
            $linha['data_vencimento'] = $date->format('d/m/Y');
        }
    }
}
unset($linha);

// Nome do arquivo
$nomeArquivo = "contas_receber_" . date("YmdHis");

// --- Exportar Excel ---
if ($tipo === 'excel') {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.xlsx");
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['Responsável','Número','Valor','Data de Vencimento','Status'],NULL,'A1');
    $linha = 2;
    foreach ($dados as $d) {
        $sheet->fromArray(array_values($d),NULL,"A{$linha}");
        $linha++;
    }
    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

// --- Exportar CSV ---
if ($tipo === 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename={$nomeArquivo}.csv");
    $f = fopen('php://output','w');
    fputcsv($f,['Responsável','Número','Valor','Data de Vencimento','Status']);
    foreach ($dados as $d) {
        fputcsv($f,$d);
    }
    fclose($f);
    exit;
}

// --- Exportar PDF ---
if ($tipo === 'pdf') {
    $html = "<h2>Contas a Receber</h2><table border='1' cellpadding='5'>
             <tr><th>Responsável</th><th>Número</th><th>Valor</th><th>Data de Vencimento</th><th>Status</th></tr>";
    foreach ($dados as $d) {
        $html .= "<tr>";
        foreach ($d as $valor) {
            $html .= "<td>".htmlspecialchars($valor)."</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</table>";
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $dompdf->stream("{$nomeArquivo}.pdf", ["Attachment"=>true]);
    exit;
}

echo "Tipo de exportação não suportado.";
