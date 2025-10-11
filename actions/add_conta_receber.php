<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Inclui a conexão com o banco
include('../database.php');

// Sanitização e coleta dos dados
$responsavel = htmlspecialchars(trim($_POST['responsavel']));
$data_vencimento = $_POST['data_vencimento'];
$numero = htmlspecialchars(trim($_POST['numero']));
$valor = floatval(str_replace(',', '.', $_POST['valor']));

// Pega o ID do usuário logado
$usuarioId = $_SESSION['usuario']['id'];

// Validação simples
if (empty($responsavel) || empty($data_vencimento) || empty($numero) || empty($valor)) {
    // Opcional: Adicionar uma mensagem de erro
    // $_SESSION['error_message'] = 'Todos os campos são obrigatórios.';
    header('Location: ../pages/contas_receber.php');
    exit;
}

// Prepara e executa a inserção
$sql = "INSERT INTO contas_receber (responsavel, data_vencimento, numero, valor, usuario_id) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Opcional: Adicionar uma mensagem de erro
    // $_SESSION['error_message'] = "Erro na preparação da query: " . $conn->error;
    header('Location: ../pages/contas_receber.php');
    exit;
}

$stmt->bind_param("sssdi", $responsavel, $data_vencimento, $numero, $valor, $usuarioId);

if ($stmt->execute()) {
    // Define a mensagem de sucesso na sessão (padronizado)
    $_SESSION['success_message'] = "Conta a receber adicionada com sucesso!";
} else {
    // Opcional: Adicionar uma mensagem de erro
    // $_SESSION['error_message'] = "Erro ao adicionar conta: " . $stmt->error;
}

$stmt->close();
$conn->close();

header('Location: ../pages/contas_receber.php');
exit;
?>