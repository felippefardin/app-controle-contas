<?php
require_once '../includes/session_init.php';
include '../database.php';

// Aumentar o tempo máximo de execução do script
set_time_limit(300); // 5 minutos

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Requisição inválida.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

$contaId = filter_input(INPUT_POST, 'conta_id', FILTER_VALIDATE_INT);
$repetirVezes = filter_input(INPUT_POST, 'repetir_vezes', FILTER_VALIDATE_INT);
$repetirIntervalo = filter_input(INPUT_POST, 'repetir_intervalo', FILTER_VALIDATE_INT);

// Validação dos dados
if (!$contaId || !$repetirVezes || !$repetirIntervalo || $repetirVezes <= 0 || $repetirIntervalo <= 0) {
    $_SESSION['error_message'] = "Dados inválidos para repetir a conta.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

// 1. Buscar a conta original
$stmt = $conn->prepare("SELECT * FROM contas_pagar WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $contaId, $_SESSION['usuario']['id']);
$stmt->execute();
$result = $stmt->get_result();
$contaOriginal = $result->fetch_assoc();
$stmt->close();

if (!$contaOriginal) {
    $_SESSION['error_message'] = "Conta a pagar original não encontrada.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

// 2. Preparar o statement para inserção
$sql = "INSERT INTO contas_pagar (fornecedor, numero, valor, data_vencimento, status, id_categoria, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql);

if ($stmt_insert === false) {
    die("Erro ao preparar a query de inserção: " . $conn->error);
}

$dataVencimento = new DateTime($contaOriginal['data_vencimento']);
$statusPendente = 'pendente';
$conn->begin_transaction();
$sucesso = true;

// 3. Loop para criar as repetições
for ($i = 1; $i <= $repetirVezes; $i++) {
    $dataVencimento->add(new DateInterval("P{$repetirIntervalo}D"));
    $novaDataVencimento = $dataVencimento->format('Y-m-d');

    $stmt_insert->bind_param(
        "ssdssii",
        $contaOriginal['fornecedor'],
        $contaOriginal['numero'],
        $contaOriginal['valor'],
        $novaDataVencimento,
        $statusPendente,
        $contaOriginal['id_categoria'],
        $_SESSION['usuario']['id']
    );

    if (!$stmt_insert->execute()) {
        $sucesso = false;
        $_SESSION['error_message'] = "Erro ao repetir a conta: " . $stmt_insert->error;
        break; 
    }
}

// 4. Finalizar a transação
if ($sucesso) {
    $conn->commit();
    $_SESSION['success_message'] = "Conta a pagar repetida com sucesso {$repetirVezes} vez(es).";
} else {
    $conn->rollback();
    // A mensagem de erro já foi definida no loop
}

$stmt_insert->close();
$conn->close();

header('Location: ../pages/contas_pagar.php');
exit;