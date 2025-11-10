<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Inclui a biblioteca de geração de planilhas (PHPOffice PhpSpreadsheet)
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Inclui a biblioteca de geração de PDF (Dompdf)
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. VERIFICAÇÃO DE SESSÃO E CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php");
    exit();
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

$usuario_logado = $_SESSION['usuario_logado'];
$usuarioId = $usuario_logado['id'];

// Parâmetros de filtro
$formato = $_GET['formato'] ?? 'csv';
$tipo_pessoa = $_GET['tipo'] ?? 'todos'; // 'cliente', 'fornecedor', 'todos'

// 2. MONTAGEM DA QUERY SQL
$where = ["id_usuario = " . intval($usuarioId)];

if ($tipo_pessoa === 'cliente') {
    $where[] = "tipo = 'cliente'";
    $nome_arquivo = 'clientes';
    $titulo_relatorio = 'Relatório de Clientes';
} elseif ($tipo_pessoa === 'fornecedor') {
    $where[] = "tipo = 'fornecedor'";
    $nome_arquivo = 'fornecedores';
    $titulo_relatorio = 'Relatório de Fornecedores';
} else {
    // Tipo 'todos' ou inválido
    $nome_arquivo = 'pessoas_e_fornecedores';
    $titulo_relatorio = 'Relatório de Clientes e Fornecedores';
}

// CORREÇÃO: Usando a coluna 'contato' no lugar de 'telefone' e removendo 'observacao'
$sql = "SELECT nome, tipo, email, contato, cpf_cnpj, endereco 
        FROM pessoas_fornecedores 
        WHERE " . implode(" AND ", $where) . "
        ORDER BY nome ASC";

$result = $conn->query($sql);

if ($result === false) {
    // Em produção, isso deve ser um erro genérico com log.
    die("Erro na consulta SQL: " . $conn->error);
}

// Cabeçalho da tabela (para todos os formatos)
$cabecalho = [
    'Nome', 
    'Tipo', 
    'Email', 
    'Contato/Telefone', // Usando um nome amigável para 'contato'
    'CPF/CNPJ', 
    'Endereço'
    // Removido 'Observação'
];

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        $row['nome'],
        ucfirst($row['tipo']),
        $row['email'],
        $row['contato'], // Usando a coluna correta
        $row['cpf_cnpj'],
        $row['endereco'],
    ];
}

// 3. EXPORTAÇÃO
switch ($formato) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$nome_arquivo.'_'.date('Ymd').'.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Escreve o cabeçalho
        fputcsv($output, $cabecalho, ';');
        
        // Escreve os dados
        foreach ($dados as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;

    case 'excel':
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($titulo_relatorio);

        // Adiciona o cabeçalho
        $sheet->fromArray($cabecalho, null, 'A1');

        // Adiciona os dados
        $sheet->fromArray($dados, null, 'A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$nome_arquivo.'_'.date('Ymd').'.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    case 'pdf':
        // Configuração do Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);

        // Cria o HTML do relatório
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>
                    body { font-family: Arial, sans-serif; font-size: 10pt; }
                    h1 { text-align: center; color: #00bfff; font-size: 14pt; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; color: #333; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                  </style>';
        $html .= '</head><body>';
        $html .= '<h1>'.$titulo_relatorio.'</h1>';
        
        // Tabela HTML
        $html .= '<table>';
        $html .= '<thead><tr>';
        foreach ($cabecalho as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        foreach ($dados as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$nome_arquivo.'_'.date('Ymd').'.pdf"');
        
        echo $dompdf->output();
        exit;

    default:
        // Se o formato não for reconhecido, retorna uma mensagem de erro ou redireciona
        echo "Formato de exportação não suportado.";
        exit;
}
?>