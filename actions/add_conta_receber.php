<?php
session_start();
include('../database.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Sanitização e coleta dos dados
$responsavel = htmlspecialchars(trim($_POST['responsavel']));
$data_vencimento = $_POST['data_vencimento'];
$numero = htmlspecialchars(trim($_POST['numero']));
$valor = floatval(str_replace(',', '.', $_POST['valor']));

// Validação simples
if (empty($responsavel) || empty($data_vencimento) || empty($numero) || empty($valor)) {
    $_SESSION['erro'] = 'Todos os campos são obrigatórios.';
    header('Location: ../pages/contas_receber.php');
    exit;
}

// Prepara e executa a inserção
$sql = "INSERT INTO contas_receber (responsavel, data_vencimento, numero, valor) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['erro'] = "Erro na preparação da query: " . $conn->error;
    header('Location: ../pages/contas_receber.php');
    exit;
}

$stmt->bind_param("sssd", $responsavel, $data_vencimento, $numero, $valor);

if ($stmt->execute()) {
    $_SESSION['mensagem'] = "Conta a receber adicionada com sucesso.";
} else {
    $_SESSION['erro'] = "Erro ao adicionar conta: " . $stmt->error;
}

$stmt->close();
$conn->close();

header('Location: ../pages/contas_receber.php');
exit;
