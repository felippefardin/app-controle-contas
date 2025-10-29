<?php
require_once '../includes/session_init.php';
require '../vendor/autoload.php';
require_once '../database.php'; // Corrigido para require_once

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
$dateField = ($status === 'baixada') ? 'cp.data_baixa' : 'cp.data_vencimento';
$orderBy = $dateField;

$sql = "SELECT cp.*, u.nome as baixado_por_nome, c.nome as nome_categoria 
        FROM contas_pagar cp
        LEFT JOIN usuarios u ON cp.baixado_por = u.id
        LEFT JOIN categorias c ON cp.id_categoria = c.id
        WHERE ";

if ($perfil === 'admin') {
    $sql .= "(cp.usuario_id = ? OR cp.usuario_id IN (SELECT id FROM usuarios WHERE id_criador = ?))";
    $params = [$usuarioId, $usuarioId];
    $types = 'ii';
} else {
    $sql .= "cp.usuario_id = ?";
    $params = [$usuarioId];
    $types = 'i';
}

$sql .= " AND cp.status = ?";
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
$sheet->setTitle('Contas a Pagar - ' . ucfirst($status));

// Cabeçalhos
$sheet->setCellValue('A1', 'Fornecedor');
$sheet->setCellValue('B1', 'Número');
$sheet->setCellValue('C1', 'Valor');
$sheet->setCellValue('D1', 'Vencimento');
$sheet->setCellValue('E1', 'Categoria');
if ($status === 'baixada') {
    $sheet->setCellValue('F1', 'Data de Pagamento');
    $sheet->setCellValue('G1', 'Baixado por');
}

// Preenche os dados
$rowNumber = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['fornecedor']);
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

//  🎨 ESTILIZAÇÃO VISUAL PROFISSIONAL
$lastColumn = ($status === 'baixada') ? 'G' : 'E';

// Cabeçalho com cor e centralização
$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '007ACC']]
]);

// Bordas e alinhamento geral
$sheet->getStyle('A1:' . $lastColumn . ($rowNumber - 1))->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

// Coluna de valores formatada em moeda
$sheet->getStyle('C2:C' . ($rowNumber - 1))
    ->getNumberFormat()
    ->setFormatCode('R$ #,##0.00');

// Autoajuste e altura do cabeçalho
foreach (range('A', $lastColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->getRowDimension('1')->setRowHeight(25);

// ✅ Ajuste A4 e orientação
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
        header('Content-Disposition: attachment;filename="contas_a_pagar.xlsx"');
        $writer = new Xlsx($spreadsheet);
        break;
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="contas_a_pagar.pdf"');
        IOFactory::registerWriter('Pdf', Dompdf::class);
        $writer = IOFactory::createWriter($spreadsheet, 'Pdf');
        break;
    default:
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="contas_a_pagar.csv"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        break;
}

$writer->save('php://output');
$stmt->close();
exit;
?>
