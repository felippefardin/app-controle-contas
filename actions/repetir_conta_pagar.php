<?php
require_once '../includes/session_init.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    // Se não estiver logado, não pode fazer nada
    exit('Acesso negado.');
}

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$database = "app_controle_contas";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se os dados necessários foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_conta'], $_POST['quantidade'])) {
    $_SESSION['success_message'] = "Erro: Requisição inválida."; // Usando success para feedback visual
    header('Location: ../views/contas_pagar.php');
    exit;
}

$contaId = (int)$_POST['id_conta'];
$quantidade = (int)$_POST['quantidade'];
$manterNomeOpt = (int)$_POST['manter_nome'] ?? 1;
$usuarioId = $_SESSION['usuario']['id'];

if ($contaId <= 0 || $quantidade <= 0 || $quantidade > 60) { // Limite de 60 repetições
    $_SESSION['success_message'] = "Erro: Dados inválidos.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

// 1. Busca a conta original para usar como modelo
$stmt = $conn->prepare("SELECT * FROM contas_pagar WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $contaId, $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['success_message'] = "Erro: Conta original não encontrada ou permissão negada.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

$contaOriginal = $result->fetch_assoc();
$stmt->close();

// 2. Prepara o INSERT para as novas contas
$sql_insert = "INSERT INTO contas_pagar (fornecedor, numero, valor, data_vencimento, status, usuario_id) VALUES (?, ?, ?, ?, 'pendente', ?)";
$stmt_insert = $conn->prepare($sql_insert);

// Inicia a manipulação de datas com DateTime para evitar erros
try {
    $dataBase = new DateTime($contaOriginal['data_vencimento']);
} catch (Exception $e) {
    $_SESSION['success_message'] = "Erro: A data de vencimento da conta original é inválida.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

$erros = 0;

// 3. Loop para criar as novas contas
for ($i = 1; $i <= $quantidade; $i++) {
    // Clona o objeto de data para a nova iteração
    $novoVencimento = clone $dataBase;
    
    // Adiciona $i meses à data de vencimento original
    $novoVencimento->add(new DateInterval("P{$i}M"));
    $dataParaDb = $novoVencimento->format('Y-m-d');
    
    // Define o novo nome do fornecedor, se a opção foi selecionada
    $novoFornecedor = $contaOriginal['fornecedor'];
    if ($manterNomeOpt === 1) {
        $parcelaTotal = $quantidade + 1;
        $parcelaAtual = $i + 1;
        // Remove uma contagem de parcela anterior (se houver) para evitar nomes duplicados
        $novoFornecedor = preg_replace('/ \((\d+)\/(\d+)\)$/', '', $novoFornecedor);
        $novoFornecedor .= " ({$parcelaAtual}/{$parcelaTotal})";
    }

    // Associa os parâmetros e executa a inserção
    $stmt_insert->bind_param(
        "ssdsi",
        $novoFornecedor,
        $contaOriginal['numero'],
        $contaOriginal['valor'],
        $dataParaDb,
        $contaOriginal['usuario_id'] // Mantém o ID do usuário original
    );
    
    if (!$stmt_insert->execute()) {
        $erros++;
    }
}

$stmt_insert->close();
$conn->close();

// 4. Redireciona com uma mensagem de sucesso
if ($erros === 0) {
    $_SESSION['success_message'] = "{$quantidade} conta(s) repetida(s) com sucesso!";
} else {
    $_SESSION['success_message'] = "Operação concluída com {$erros} erro(s).";
}

header('Location: ../pages/contas_pagar.php');
exit;
?>