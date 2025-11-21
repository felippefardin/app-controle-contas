<?php
require_once '../includes/session_init.php';
require_once '../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_logado'])) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$conn = getTenantConnection();
$id_venda = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_venda) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// Busca cabeçalho da venda e cliente
$sqlVenda = "
    SELECT v.*, p.nome as nome_cliente 
    FROM vendas v 
    LEFT JOIN pessoas_fornecedores p ON v.id_cliente = p.id 
    WHERE v.id = ?
";
$stmt = $conn->prepare($sqlVenda);
$stmt->bind_param("i", $id_venda);
$stmt->execute();
$venda = $stmt->get_result()->fetch_assoc();

if (!$venda) {
    echo json_encode(['error' => 'Venda não encontrada']);
    exit;
}

// Busca itens
$sqlItens = "
    SELECT vi.*, p.nome as nome_produto 
    FROM venda_items vi
    JOIN produtos p ON vi.id_produto = p.id
    WHERE vi.id_venda = ?
";
$stmt = $conn->prepare($sqlItens);
$stmt->bind_param("i", $id_venda);
$stmt->execute();
$resItens = $stmt->get_result();

$itens = [];
while ($row = $resItens->fetch_assoc()) {
    $itens[] = [
        'produto' => $row['nome_produto'],
        'quantidade' => $row['quantidade'],
        'preco_unitario' => number_format($row['preco_unitario'], 2, ',', '.'),
        'total' => number_format($row['subtotal'], 2, ',', '.')
    ];
}

// Busca se existe Nota Fiscal emitida para esta venda
$sqlNota = "SELECT chave_acesso FROM notas_fiscais WHERE id_venda = ? AND status = 'autorizada' ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sqlNota);
$stmt->bind_param("i", $id_venda);
$stmt->execute();
$nota = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'cliente' => $venda['nome_cliente'] ?? 'Consumidor Final',
    'data' => date('d/m/Y H:i', strtotime($venda['data_venda'])),
    'total_venda' => number_format($venda['valor_total'], 2, ',', '.'),
    'itens' => $itens,
    'chave_nfe' => $nota['chave_acesso'] ?? null // Retorna a chave se existir
]);
?>