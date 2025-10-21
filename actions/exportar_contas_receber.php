<?php
require_once '../includes/session_init.php';
require '../vendor/autoload.php';
include '../database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\IOFactory;

$formato = $_GET['formato'] ?? 'csv';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$status = $_GET['status'] ?? 'pendente';

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

$where = ["cr.status = '" . $conn->real_escape_string($status) . "'"];

if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";
    $where[] = "(cr.usuario_id = {$mainUserId} OR cr.usuario_id IN ({$subUsersQuery}))";
}

if (!empty($data_inicio) && !empty($data_fim)) {
    if ($status === 'baixada') {
        $where[] = "cr.data_baixa BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
    } else {
        $where[] = "cr.data_vencimento BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
    }
}

$orderBy = ($status === 'baixada') ? 'cr.data_baixa' : 'cr.data_vencimento';

$sql = "SELECT cr.*, u.nome AS baixado_por_nome
        FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por_usuario_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy ASC";

$result = $conn->query($sql);

// === PLANILHA ===
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Contas a Receber - ' . ucfirst($status));

// Cabeçalhos
$sheet->setCellValue('A1', 'Responsável');
$sheet->setCellValue('B1', 'Número');
$sheet->setCellValue('C1', 'Valor');
$sheet->setCellValue('D1', 'Data de Vencimento');

if ($status === 'baixada') {
    $sheet->setCellValue('E1', 'Data de Pagamento');
    $sheet->setCellValue('F1', 'Baixado por');
}

// Estilo do cabeçalho
$headerStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFD9E1F2'] // azul suave
    ],
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FF000000'],
        'size' => 12
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

if ($status === 'baixada') {
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
} else {
    $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
}

// Linhas de conteúdo
$rowNumber = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['responsavel']);
        $sheet->setCellValue('B' . $rowNumber, $row['numero']);
        $sheet->setCellValue('C' . $rowNumber, number_format($row['valor'], 2, ',', '.'));
        $sheet->setCellValue('D' . $rowNumber, date('d/m/Y', strtotime($row['data_vencimento'])));
        if ($status === 'baixada') {
            $sheet->setCellValue('E' . $rowNumber, $row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '');
            $sheet->setCellValue('F' . $rowNumber, $row['baixado_por_nome']);
        }
        $rowNumber++;
    }
}

// Ajustes visuais
$lastColumn = ($status === 'baixada') ? 'F' : 'D';

// Largura das colunas + alinhamento centralizado
foreach (range('A', $lastColumn) as $columnID) {
    $sheet->getColumnDimension($columnID)->setWidth(25);
    $sheet->getStyle($columnID)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
}

// Altura das linhas
foreach (range(1, $rowNumber) as $row) {
    $sheet->getRowDimension($row)->setRowHeight(22);
}

// Configuração da página (PDF)
$sheet->getPageSetup()
    ->setPaperSize(PageSetup::PAPERSIZE_A4)
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

$sheet->getPageMargins()->setTop(0.5)->setBottom(1.0)->setLeft(0.5)->setRight(0.5);

// Rodapé com data e número de página
$footerDate = date('d/m/Y H:i');
$sheet->getHeaderFooter()
    ->setOddFooter("&C Gerado em: $footerDate  |  Página &P de &N");

// === EXPORTAÇÃO ===
switch ($formato) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="contas_a_receber.csv"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $writer->save('php://output');
        break;

    case 'excel':
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="contas_a_receber.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        break;

    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="contas_a_receber.pdf"');
        IOFactory::registerWriter('Pdf', Dompdf::class);
        $writer = IOFactory::createWriter($spreadsheet, 'Pdf');
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        break;
}

$conn->close();
exit;
?>
