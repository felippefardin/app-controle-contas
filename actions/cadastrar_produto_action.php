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

// CAPTURA DOS CAMPOS
$nome               = trim($_POST['nome'] ?? '');
$descricao          = trim($_POST['descricao'] ?? '');
$quantidade         = intval($_POST['quantidade_estoque'] ?? 0);
$quantidade_minima  = intval($_POST['quantidade_minima'] ?? 0);
$preco_compra       = floatval($_POST['preco_compra'] ?? 0);
$preco_venda        = floatval($_POST['preco_venda'] ?? 0);
$ncm                = trim($_POST['ncm'] ?? '');
$cfop               = trim($_POST['cfop'] ?? '');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$nome || !$id_usuario) {
    $_SESSION['erro'] = "Preencha todos os campos obrigatórios.";
    header("Location: ../pages/controle_estoque.php");
    exit;
}

try {
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
    $_SESSION['erro'] = "Erro ao cadastrar o produto: " . $e->getMessage();
    header("Location: ../pages/controle_estoque.php");
    exit;
}
