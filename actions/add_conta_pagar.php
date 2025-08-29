<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Inclui a conexão com o banco
include('../database.php');

// Conecta ao banco principal
$conn = getConnPrincipal();

// Recebe e valida os dados do formulário
$fornecedor = trim($_POST['fornecedor'] ?? '');
$data_vencimento = trim($_POST['data_vencimento'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$valor = trim($_POST['valor'] ?? '');

// Verifica se todos os campos obrigatórios foram preenchidos
if (empty($fornecedor) || empty($data_vencimento) || empty($numero) || empty($valor)) {
    die("Por favor, preencha todos os campos.");
}

// Ajusta valor para formato numérico, substituindo vírgula por ponto
$valor = str_replace(',', '.', $valor);

// Prepara e executa a query
$stmt = $conn->prepare("INSERT INTO contas_pagar (fornecedor, data_vencimento, numero, valor) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("Erro ao preparar a query: " . $conn->error);
}

$stmt->bind_param("sssd", $fornecedor, $data_vencimento, $numero, $valor);
$executado = $stmt->execute();

if (!$executado) {
    die("Erro ao adicionar conta: " . $stmt->error);
}

// Fecha statement e conexão
$stmt->close();
$conn->close();

// Redireciona de volta para a página de contas a pagar
header('Location: ../pages/contas_pagar.php');
exit;
?>
