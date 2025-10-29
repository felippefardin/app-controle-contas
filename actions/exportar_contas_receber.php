<?php
require_once '../includes/session_init.php';
require '../vendor/autoload.php';
require_once '../database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\IOFactory;

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha na conexão com o banco de dados do cliente.");
}

// 2. PEGA OS DADOS DA SESSÃO E OS FILTROS DE FORMA SEGURA
$usuarioId = $_SESSION['usuario_logado']['id'];
$perfil = $_SESSION['usuario_logado']['perfil'] ?? 'padrao';

$formato = $_GET['formato'] ?? 'excel';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$status = $_GET['status'] ?? 'pendente';

// 3. CONSTRÓI A QUERY
$params = [];
$types = '';
$dateField = ($status === 'baixada') ? 'cr.data_baixa' : 'cr.data_vencimento';
$orderBy = $dateField;

$sql = "SELECT cr.*, u.nome AS baixado_por_nome, c.nome as nome_categoria
        FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por_usuario_id = u.id
        LEFT JOIN categorias c ON cr.id_categoria = c.id
        WHERE ";

if ($perfil === 'admin') {
    $sql .= "(cr.usuario_id = ? OR cr.usuario_id IN (SELECT id FROM usuarios WHERE id_criador = ?))";
    $params = [$usuarioId, $usuarioId];
    $types = 'ii';
} else {
    $sql .= "cr.usuario_id = ?";
    $params = [$usuarioId];
    $types = 'i';
}

$sql .= " AND cr.status = ?";
$params[] = $status;
$types .= 's';

if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND {$dateField} BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= 'ss';
}

$sql .= " ORDER BY $orderBy ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 4. GERAÇÃO DA PLANILHA
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Contas a Receber - ' . ucfirst($status));

// Cabeçalhos
$sheet->setCellValue('A1', 'Cliente/Responsável');
$sheet->setCellValue('B1', 'Número');
$sheet->setCellValue('C1', 'Valor');
$sheet->setCellValue('D1', 'Vencimento');
$sheet->setCellValue('E1', 'Categoria');

if ($status === 'baixada') {
    $sheet->setCellValue('F1', 'Data de Pagamento');
    $sheet->setCellValue('G1', 'Recebido por');
}

// Preenche os dados
$rowNumber = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['responsavel']);
        $sheet->setCellValue('B' . $rowNumber, $row['numero']);
        $sheet->setCellValue('C' . $rowNumber, $row['valor']);
        $sheet->setCellValue('D' . $rowNumber, date('d/m/Y', strtotime($row['data_vencimento'])));
        $sheet->setCellValue('E' . $rowNumber, $row['nome_categoria']);
        if ($status === 'baixada') {
            $sheet->setCellValue('F' . $rowNumber, $row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '');
            $sheet->setCellValue('G' . $rowNumber, $row['baixado_por_nome']);
        }
        $rowNumber++;
    }
}

// 🎨 ESTILIZAÇÃO AVANÇADA
$lastColumn = ($status === 'baixada') ? 'G' : 'E';

// Cabeçalho com fundo gradiente (verde-azulado) e texto branco
$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => '00BFA5'] // verde-água
    ]
]);

// Linhas alternadas com leve cor de fundo
for ($i = 2; $i < $rowNumber; $i++) {
    if ($i % 2 == 0) {
        $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F5E9');
    }
}

// Bordas e alinhamento geral
$sheet->getStyle('A1:' . $lastColumn . ($rowNumber - 1))->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BDBDBD']]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// Coluna de valores formatada
$sheet->getStyle('C2:C' . ($rowNumber - 1))
    ->getNumberFormat()
    ->setFormatCode('R$ #,##0.00');

// Altura e largura automáticas
foreach (range('A', $lastColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->getRowDimension('1')->setRowHeight(28);

// Rodapé com resumo (opcional visual)
$footerRow = $rowNumber + 1;
$sheet->mergeCells("A{$footerRow}:" . $lastColumn . "{$footerRow}");
$sheet->setCellValue("A{$footerRow}", 'Relatório gerado automaticamente pelo sistema de controle financeiro.');
$sheet->getStyle("A{$footerRow}")->applyFromArray([
    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '757575']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// ✅ Ajustes de página
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
if ($status === 'baixada') {
    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
} else {
    $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
}

// 6. EXPORTAÇÃO
switch ($formato) {
    case 'excel':
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="contas_a_receber.xlsx"');
        $writer = new Xlsx($spreadsheet);
        break;
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="contas_a_receber.pdf"');
        IOFactory::registerWriter('Pdf', Dompdf::class);
        $writer = IOFactory::createWriter($spreadsheet, 'Pdf');
        break;
    default:
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="contas_a_receber.csv"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        break;
}

$writer->save('php://output');
$stmt->close();
exit;
?>
