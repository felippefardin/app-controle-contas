<?php
session_start();
require '../vendor/autoload.php';
include('../database.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Dompdf\Dompdf;

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Parâmetros recebidos via POST do modal
$data_inicio = $_POST['data_inicio'];
$data_fim = $_POST['data_fim'];
$formato = $_POST['formato'];
$status = $_POST['status'] ?? 'pendente'; // 'pendente' ou 'baixada'

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Monta a base da consulta
$sql = "SELECT c.*, u.nome AS usuario_baixou
        FROM contas_receber c
        LEFT JOIN usuarios u ON c.baixado_por = u.id";
$where = [];
$params = [];
$types = "";

// Define o campo de data para o filtro de período
$dateField = ($status === 'baixada') ? 'c.data_baixa' : 'c.data_vencimento';

$where[] = "c.status = ?";
$params[] = $status;
$types .= "s";

if (!empty($data_inicio) && !empty($data_fim)) {
    $where[] = "$dateField BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
}

// Filtro de visibilidade por usuário
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id = {$mainUserId} OR id_criador = {$mainUserId}";
    $where[] = "c.usuario_id IN ($subUsersQuery)";
}

$sql .= " WHERE " . implode(" AND ", $where);
$sql .= ($status === 'baixada') ? " ORDER BY c.data_baixa DESC" : " ORDER BY c.data_vencimento ASC";

$stmt = $conn->prepare($sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

$fileName = "contas_a_receber_{$status}_" . date('Y-m-d');
$periodo = 'Período de ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));

// Cabeçalhos para a tabela/arquivo
$headers = ['Responsável', 'Número', 'Valor', 'Vencimento'];
if ($status === 'baixada') {
    $headers = ['Responsável', 'Número', 'Valor', 'Data Baixa', 'Juros', 'Forma Pagamento', 'Usuário Baixou'];
}

// Geração dos arquivos
if ($formato === 'pdf') {
    $html = "<h1>Relatório de Contas a Receber ({$status})</h1>";
    $html .= "<p>{$periodo}</p>";
    $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size: 12px;">';
    $html .= '<thead><tr><th>' . implode('</th><th>', $headers) . '</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($dados as $dado) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($dado['responsavel']) . '</td>';
        $html .= '<td>' . htmlspecialchars($dado['numero']) . '</td>';
        $html .= '<td>R$ ' . number_format($dado['valor'], 2, ',', '.') . '</td>';
        $html .= '<td>' . date('d/m/Y', strtotime($dado[$status === 'baixada' ? 'data_baixa' : 'data_vencimento'])) . '</td>';
        if ($status === 'baixada') {
            $html .= '<td>R$ ' . number_format($dado['juros'] ?? 0, 2, ',', '.') . '</td>';
            $html .= '<td>' . htmlspecialchars($dado['forma_pagamento'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($dado['usuario_baixou'] ?? '-') . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($fileName . ".pdf");

} else {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');

    $rowNum = 2;
    foreach ($dados as $dado) {
        $rowData = [
            $dado['responsavel'],
            $dado['numero'],
            $dado['valor'],
            date('d/m/Y', strtotime($dado[$status === 'baixada' ? 'data_baixa' : 'data_vencimento'])),
        ];
        if ($status === 'baixada') {
            $rowData[] = $dado['juros'] ?? 0;
            $rowData[] = $dado['forma_pagamento'] ?? '-';
            $rowData[] = $dado['usuario_baixou'] ?? '-';
        }
        $sheet->fromArray($rowData, null, 'A' . $rowNum);
        $rowNum++;
    }

    if ($formato === 'xlsx') {
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'.xlsx"');
        $writer->save('php://output');
    } elseif ($formato === 'csv') {
        $writer = new Csv($spreadsheet);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'. $fileName .'.csv"');
        $writer->save('php://output');
    }
}
$conn->close();
?>