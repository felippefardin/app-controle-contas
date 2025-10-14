<?php
require_once '../includes/session_init.php';
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

// Parâmetros recebidos do modal
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';
$formato = $_POST['formato'] ?? 'pdf';
$status = $_POST['status'] ?? 'pendente'; // 'pendente' ou 'baixada'

$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

// Base da consulta
$sql = "SELECT c.*, u.nome AS usuario_baixou
        FROM contas_pagar c
        LEFT JOIN usuarios u ON c.baixado_por = u.id";

$where = [];
$params = [];
$types = "";
$orderBy = "";

// Filtro de visibilidade por usuário
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id = {$mainUserId} OR id_criador = {$mainUserId}";
    $where[] = "c.usuario_id IN ($subUsersQuery)";
}

// Lógica explícita para cada status
if ($status === 'baixada') {
    $where[] = "c.status = ?";
    $params[] = 'baixada';
    $types .= "s";

    if (!empty($data_inicio) && !empty($data_fim)) {
        $where[] = "c.data_baixa BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
        $types .= "ss";
    }
    $orderBy = "c.data_baixa ASC";
} else { // Para 'pendente'
    $where[] = "c.status = ?";
    $params[] = 'pendente';
    $types .= "s";

    if (!empty($data_inicio) && !empty($data_fim)) {
        $where[] = "c.data_vencimento BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
        $types .= "ss";
    }
    $orderBy = "c.data_vencimento ASC";
}

// Montagem final da consulta
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY " . $orderBy;

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a consulta: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}
$stmt->close();

if (empty($dados)) {
    echo "<script>alert('Nenhum dado encontrado para o período e status selecionado.'); window.close();</script>";
    exit;
}

// --- O restante do código para gerar PDF, Excel e CSV continua o mesmo ---

$fileName = "relatorio_contas_pagar" . $status . "_" . date('Y-m-d');
$periodo = 'Período de ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
$statusTitulo = ucfirst($status) . 's';

$headers = ($status === 'baixada') 
    ? ['Fornecedor', 'Número', 'Valor', 'Vencimento', 'Data Baixa', 'Usuário Baixou']
    : ['Fornecedor', 'Número', 'Valor', 'Vencimento'];

if ($formato === 'pdf') {
    $html = "<h1>Relatório de Contas a Pagar - {$statusTitulo}</h1>";
    $html .= "<p>{$periodo}</p>";
    $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%" style="font-size: 10px;">';
    $html .= '<thead><tr><th>' . implode('</th><th>', $headers) . '</th></tr></thead>';
    $html .= '<tbody>';
    foreach ($dados as $dado) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($dado['fornecedor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($dado['numero']) . '</td>';
        $html .= '<td>R$ ' . number_format($dado['valor'], 2, ',', '.') . '</td>';
        $html .= '<td>' . ($dado['data_vencimento'] ? date('d/m/Y', strtotime($dado['data_vencimento'])) : '-') . '</td>';
        if ($status === 'baixada') {
            $html .= '<td>' . ($dado['data_baixa'] ? date('d/m/Y', strtotime($dado['data_baixa'])) : '-') . '</td>';
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
            $dado['fornecedor'],
            $dado['numero'],
            $dado['valor'],
            ($dado['data_vencimento'] ? date('d/m/Y', strtotime($dado['data_vencimento'])) : '-'),
        ];
        if ($status === 'baixada') {
            $rowData[] = ($dado['data_baixa'] ? date('d/m/Y', strtotime($dado['data_baixa'])) : '-');
            $rowData[] = $dado['usuario_baixou'] ?? '-';
        }
        $sheet->fromArray($rowData, null, 'A' . $rowNum);
        $rowNum++;
    }

    if ($formato === 'xlsx') {
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'.xlsx"');
    } elseif ($formato === 'csv') {
        $writer = new Csv($spreadsheet);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'. $fileName .'.csv"');
    }
    $writer->save('php://output');
}
$conn->close();
exit;
?>