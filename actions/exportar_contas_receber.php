<?php
session_start();
include('../database.php');
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

if (!isset($_SESSION['usuario'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Acesso negado.';
    exit;
}

// Pega os dados do formulário
$formato = $_POST['formato'] ?? 'pdf';
$status = $_POST['status'] ?? 'pendente';
$data_inicio = $_POST['data_inicio'];
$data_fim = $_POST['data_fim'];

// Prepara a consulta SQL com os filtros corretos
$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];
$id_criador = $_SESSION['usuario']['id_criador'] ?? 0;

$where = [];

// Filtro de Status
if ($status === 'pendente' || $status === 'baixada') {
    $where[] = "cr.status = '".$conn->real_escape_string($status)."'";
}

// Filtro de Data
$dateColumn = ($status === 'baixada') ? 'cr.data_baixa' : 'cr.data_vencimento';
$where[] = "$dateColumn BETWEEN '".$conn->real_escape_string($data_inicio)."' AND '".$conn->real_escape_string($data_fim)."'";

// Filtro de Usuário
if ($perfil !== 'admin') {
    $mainUserId = ($id_criador > 0) ? $id_criador : $usuarioId;
    $subUsersQuery = "SELECT id FROM usuarios WHERE id_criador = {$mainUserId}";
    $where[] = "(cr.usuario_id = {$mainUserId} OR cr.usuario_id IN ({$subUsersQuery}))";
}

// --- CORREÇÃO PRINCIPAL AQUI ---
// Adicionado LEFT JOIN com a tabela 'usuarios' para buscar o nome de quem baixou a conta.
$sql = "SELECT cr.*, u.nome AS baixado_por_nome 
        FROM contas_receber cr
        LEFT JOIN usuarios u ON cr.baixado_por_usuario_id = u.id
        WHERE ".implode(" AND ", $where)." 
        ORDER BY cr.data_vencimento ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Erro na consulta: " . $conn->error);
}

// Define os cabeçalhos dinamicamente
$headers = ['Responsável', 'Número', 'Vencimento', 'Valor', 'Status'];
if ($status !== 'pendente') {
    $headers[] = 'Baixado por'; // Adiciona a coluna se o status não for apenas pendente
}


// Geração do arquivo com base no formato
switch ($formato) {
    case 'pdf':
        $html = "<h1>Relatório de Contas a Receber</h1>";
        $html .= "<table border='1' cellpadding='5' style='width:100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background-color:#ccc;'>";
        foreach ($headers as $header) {
            $html .= "<th>$header</th>";
        }
        $html .= "          </tr>
                    </thead>
                    <tbody>";
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>
                        <td>".htmlspecialchars($row['responsavel'])."</td>
                        <td>".htmlspecialchars($row['numero'])."</td>
                        <td>".date('d/m/Y', strtotime($row['data_vencimento']))."</td>
                        <td>R$ ".number_format($row['valor'], 2, ',', '.')."</td>
                        <td>".ucfirst($row['status'])."</td>";
            if ($status !== 'pendente') {
                $html .= "<td>".htmlspecialchars($row['baixado_por_nome'] ?? 'N/A')."</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("contas_receber.pdf", ["Attachment" => true]);
        break;

    case 'xlsx':
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contas a Receber');
        $sheet->fromArray($headers, NULL, 'A1');

        $rowNum = 2;
        while ($row = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $rowNum, $row['responsavel']);
            $sheet->setCellValue('B' . $rowNum, $row['numero']);
            $sheet->setCellValue('C' . $rowNum, date('d/m/Y', strtotime($row['data_vencimento'])));
            $sheet->setCellValue('D' . $rowNum, $row['valor']);
            $sheet->setCellValue('E' . $rowNum, $row['status']);
            if ($status !== 'pendente') {
                 $sheet->setCellValue('F' . $rowNum, $row['baixado_por_nome'] ?? 'N/A');
            }
            $rowNum++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="contas_receber.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        break;

    case 'csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="contas_receber.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        while ($row = $result->fetch_assoc()) {
            $line = [
                $row['responsavel'],
                $row['numero'],
                date('d/m/Y', strtotime($row['data_vencimento'])),
                number_format($row['valor'], 2, ',', '.'),
                $row['status']
            ];
            if ($status !== 'pendente') {
                $line[] = $row['baixado_por_nome'] ?? 'N/A';
            }
            fputcsv($output, $line);
        }
        fclose($output);
        break;
}

$conn->close();
exit;
?>