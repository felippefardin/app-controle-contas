<?php
require_once '../includes/session_init.php';
require_once '../database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: ../pages/login.php");
    exit;
}

$conn = getTenantConnection();

if (!$conn) {
    die("Erro: Conexão do tenant não encontrada.");
}

// Função auxiliar para formatar moeda (BR -> US)
function formatarMoeda($valor) {
    if (empty($valor)) return 0;
    // Remove pontos de milhar (ex: 1.000 -> 1000)
    $valor = str_replace('.', '', $valor);
    // Substitui vírgula decimal por ponto (ex: 10,50 -> 10.50)
    $valor = str_replace(',', '.', $valor);
    return floatval($valor);
}

// CAPTURA DOS CAMPOS
$nome               = trim($_POST['nome'] ?? '');
$descricao          = trim($_POST['descricao'] ?? '');
$quantidade         = intval($_POST['quantidade_estoque'] ?? 0);
$quantidade_minima  = intval($_POST['quantidade_minima'] ?? 0);

// Aplica a formatação correta nos preços
$preco_compra       = formatarMoeda($_POST['preco_compra'] ?? '0');
$preco_venda        = formatarMoeda($_POST['preco_venda'] ?? '0');

$ncm                = trim($_POST['ncm'] ?? '');
$cfop               = trim($_POST['cfop'] ?? '');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$nome || !$id_usuario) {
    $_SESSION['erro'] = "Preencha todos os campos obrigatórios (Nome).";
    header("Location: ../pages/controle_estoque.php");
    exit;
}

try {
    // A coluna no banco é 'quantidade_estoque' conforme o schema
    $query = "INSERT INTO produtos 
        (nome, descricao, quantidade_estoque, quantidade_minima, preco_compra, preco_venda, ncm, cfop, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssiiddssi",
        $nome,
        $descricao,
        $quantidade,
        $quantidade_minima,
        $preco_compra,
        $preco_venda,
        $ncm,
        $cfop,
        $id_usuario
    );

    $stmt->execute();
    $stmt->close();

    $_SESSION['sucesso'] = "Produto cadastrado com sucesso!";
    header("Location: ../pages/controle_estoque.php");
    exit;

} catch (mysqli_sql_exception $e) {
    // Captura o erro exato para exibir na tela
    $_SESSION['erro'] = "Erro ao cadastrar o produto: " . $e->getMessage();
    header("Location: ../pages/controle_estoque.php");
    exit;
}
?>