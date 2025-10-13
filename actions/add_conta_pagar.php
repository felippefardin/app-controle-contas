<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

include('../database.php');

// --- VALIDAÇÃO E SANITIZAÇÃO ---
$fornecedor = trim(filter_input(INPUT_POST, 'fornecedor', FILTER_SANITIZE_SPECIAL_CHARS));
$numero = trim(filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_SPECIAL_CHARS));
$data_vencimento = trim($_POST['data_vencimento'] ?? '');
$valor = trim($_POST['valor'] ?? '');
$enviar_email = isset($_POST['enviar_email']) ? 'S' : 'N';

$usuarioId = $_SESSION['usuario']['id'];

// Validação
$erros = [];
if (empty($fornecedor)) {
    $erros[] = "O campo Fornecedor é obrigatório.";
}
if (empty($numero)) {
    $erros[] = "O campo Número é obrigatório.";
}
if (empty($data_vencimento) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_vencimento)) {
    $erros[] = "Data de vencimento inválida.";
}
if (empty($valor)) {
    $erros[] = "O campo Valor é obrigatório.";
}

// Se houver erros, redireciona de volta com uma mensagem
if (!empty($erros)) {
    $_SESSION['error_message'] = implode('<br>', $erros);
    header('Location: ../pages/contas_pagar.php');
    exit;
}
// --- FIM DA VALIDAÇÃO ---



// Ajusta valor para formato numérico
$valor = str_replace('.', '', $valor); // Remove separador de milhar
$valor = str_replace(',', '.', $valor); // Troca vírgula por ponto
$valor_float = filter_var($valor, FILTER_VALIDATE_FLOAT);

if ($valor_float === false) {
    $_SESSION['error_message'] = "Valor inválido.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}


$stmt = $conn->prepare("INSERT INTO contas_pagar (fornecedor, data_vencimento, numero, valor, usuario_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssdi", $fornecedor, $data_vencimento, $numero, $valor_float, $usuarioId);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Conta adicionada com sucesso!";
} else {
    $_SESSION['error_message'] = "Erro ao adicionar conta.";
}

$stmt->close();
$conn->close();

header('Location: ../pages/contas_pagar.php');
exit;
?>