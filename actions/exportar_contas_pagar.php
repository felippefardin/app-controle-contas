<?php
require_once '../includes/session_init.php';
require '../vendor/autoload.php';
include '../database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;

$formato = $_GET['formato'] ?? 'csv';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$status = $_GET['status'] ?? 'pendente'; // Deve receber 'pendente' ou 'baixada'

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

$where = ["cp.status = '" . $conn->real_escape_string($status) . "'"];

if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";
    $where[] = "(cp.usuario_id = {$mainUserId} OR cp.usuario_id IN ({$subUsersQuery}))";
}

if (!empty($data_inicio) && !empty($data_fim)) {
    // CORRIGIDO: Usa 'baixada' e a coluna 'data_baixa'
    if ($status === 'baixada') {
        $where[] = "cp.data_baixa BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
    } else {
        $where[] = "cp.data_vencimento BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
    }
}

// CORRIGIDO: Ordena por 'data_baixa' quando o status for 'baixada'
$orderBy = ($status === 'baixada') ? 'cp.data_baixa' : 'cp.data_vencimento';
$sql = "SELECT cp.* FROM contas_pagar cp
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy ASC";

$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Contas a Pagar');

$sheet->setCellValue('A1', 'Fornecedor');
$sheet->setCellValue('B1', 'Número');
$sheet->setCellValue('C1', 'Valor');
$sheet->setCellValue('D1', 'Data de Vencimento');
// CORRIGIDO: Usa 'baixada' para adicionar as colunas extras
if ($status === 'baixada') {
    $sheet->setCellValue('E1', 'Data de Pagamento');
    $sheet->setCellValue('F1', 'Baixado por');
}

$rowNumber = 2;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['fornecedor']);
        $sheet->setCellValue('B' . $rowNumber, $row['numero']);
        $sheet->setCellValue('C' . $rowNumber, $row['valor']);
        $sheet->setCellValue('D' . $rowNumber, date('d/m/Y', strtotime($row['data_vencimento'])));
        // CORRIGIDO: Usa 'baixada' e a coluna 'data_baixa'
        if ($status === 'baixada') {
            $sheet->setCellValue('E' . $rowNumber, $row['data_baixa'] ? date('d/m/Y', strtotime($row['data_baixa'])) : '');
            $sheet->setCellValue('F' . $rowNumber, $row['baixado_por']);
        }
        $rowNumber++;
    }
}

switch ($formato) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="contas_a_pagar.csv"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $writer->save('php://output');
        break;
    case 'excel':
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="contas_a_pagar.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        break;
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="contas_a_pagar.pdf"');
        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', Dompdf::class);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        $writer->save('php://output');
        break;
}

$conn->close();
exit;