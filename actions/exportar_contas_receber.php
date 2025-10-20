<?php
require_once '../includes/session_init.php';
require '../vendor/autoload.php';
include '../database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;

// Linha de teste
$status = 'pago';

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
    if ($status === 'pago') {
        $where[] = "cr.data_pagamento BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
    } else {
        $where[] = "cr.data_vencimento BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_fim) . "'";
    }
}

// Adicione esta linha antes da sua variável $sql
$orderBy = ($status === 'pago') ? 'cr.data_pagamento' : 'cr.data_vencimento';

$sql = "SELECT cr.*, u.nome AS baixado_por_nome
        FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por_usuario_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy ASC"; // Use a variável $orderBy aqui

$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Contas a Receber');

$sheet->setCellValue('A1', 'Responsável');
$sheet->setCellValue('B1', 'Número');
$sheet->setCellValue('C1', 'Valor');
$sheet->setCellValue('D1', 'Data de Vencimento');
if ($status === 'pago') {
    $sheet->setCellValue('E1', 'Data de Pagamento');
    $sheet->setCellValue('F1', 'Baixado por');
}

$rowNumber = 2;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['responsavel']);
        $sheet->setCellValue('B' . $rowNumber, $row['numero']);
        $sheet->setCellValue('C' . $rowNumber, $row['valor']);
        $sheet->setCellValue('D' . $rowNumber, date('d/m/Y', strtotime($row['data_vencimento'])));
        if ($status === 'pago') {
            $sheet->setCellValue('E' . $rowNumber, date('d/m/Y', strtotime($row['data_pagamento'])));
            $sheet->setCellValue('F' . $rowNumber, $row['baixado_por_nome']);
        }
        $rowNumber++;
    }
}

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
        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', Dompdf::class);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        $writer->save('php://output');
        break;
}

$conn->close();
exit;