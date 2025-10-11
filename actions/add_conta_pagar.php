<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Inclui a conexão com o banco
include('../database.php');

// Recebe e valida os dados do formulário
$fornecedor = trim($_POST['fornecedor'] ?? '');
$data_vencimento = trim($_POST['data_vencimento'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$valor = trim($_POST['valor'] ?? '');

// Pega o ID do usuário logado
$usuarioId = $_SESSION['usuario']['id'];

// Verifica se todos os campos obrigatórios foram preenchidos
if (empty($fornecedor) || empty($data_vencimento) || empty($numero) || empty($valor)) {
    // Você pode adicionar uma mensagem de erro aqui se desejar
    // $_SESSION['error_message'] = "Por favor, preencha todos os campos.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

// Ajusta valor para formato numérico, substituindo vírgula por ponto
$valor = str_replace(',', '.', $valor);

// Prepara e executa a query com a ID do usuário
$stmt = $conn->prepare("INSERT INTO contas_pagar (fornecedor, data_vencimento, numero, valor, usuario_id) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Erro ao preparar a query: " . $conn->error);
}

$stmt->bind_param("sssdi", $fornecedor, $data_vencimento, $numero, $valor, $usuarioId);
$executado = $stmt->execute();

if ($executado) {
    // Define a mensagem de sucesso na sessão
    $_SESSION['success_message'] = "Conta adicionada com sucesso!";
} else {
    // Opcional: Adicionar uma mensagem de erro em caso de falha
    // $_SESSION['error_message'] = "Erro ao adicionar conta: " . $stmt->error;
}

// Fecha statement e conexão
$stmt->close();
$conn->close();

// Redireciona de volta para a página de contas a pagar
header('Location: ../pages/contas_pagar.php');
exit;
?>