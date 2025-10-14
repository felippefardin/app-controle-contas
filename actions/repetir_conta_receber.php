<?php
require_once '../includes/session_init.php';
include('../database.php'); // Inclui sua conexão com o banco

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    exit('Acesso negado.');
}

// Verifica se os dados necessários foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_conta'], $_POST['quantidade'])) {
    $_SESSION['success_message'] = "Erro: Requisição inválida.";
    header('Location: ../pages/contas_receber.php'); // Redireciona de volta
    exit;
}

$contaId = (int)$_POST['id_conta'];
$quantidade = (int)$_POST['quantidade'];
$manterNomeOpt = (int)$_POST['manter_nome'] ?? 1;
$usuarioId = $_SESSION['usuario']['id'];

// Validação de segurança e limites
if ($contaId <= 0 || $quantidade <= 0 || $quantidade > 60) {
    $_SESSION['success_message'] = "Erro: Dados inválidos.";
    header('Location: ../pages/contas_receber.php');
    exit;
}

// 1. Busca a conta original para usar como modelo
// Garante que o usuário só possa repetir contas que ele mesmo criou
$stmt = $conn->prepare("SELECT * FROM contas_receber WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $contaId, $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['success_message'] = "Erro: Conta original não encontrada ou permissão negada.";
    header('Location: ../pages/contas_receber.php');
    exit;
}

$contaOriginal = $result->fetch_assoc();
$stmt->close();

// 2. Prepara o INSERT para as novas contas
$sql_insert = "INSERT INTO contas_receber (responsavel, numero, valor, data_vencimento, status, usuario_id) VALUES (?, ?, ?, ?, 'pendente', ?)";
$stmt_insert = $conn->prepare($sql_insert);

// Inicia a manipulação de datas com DateTime para evitar erros com virada de mês/ano
try {
    $dataBase = new DateTime($contaOriginal['data_vencimento']);
} catch (Exception $e) {
    $_SESSION['success_message'] = "Erro: A data de vencimento da conta original é inválida.";
    header('Location: ../pages/contas_receber.php');
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
    
    // Define o novo nome do responsável, se a opção foi selecionada
    $novoResponsavel = $contaOriginal['responsavel'];
    if ($manterNomeOpt === 1) {
        $parcelaTotal = $quantidade + 1;
        $parcelaAtual = $i + 1;
        // Remove uma contagem de parcela anterior (se houver) para evitar nomes como "Cliente (2/12) (3/13)"
        $novoResponsavel = preg_replace('/ \((\d+)\/(\d+)\)$/', '', $novoResponsavel);
        $novoResponsavel .= " ({$parcelaAtual}/{$parcelaTotal})";
    }

    // Associa os parâmetros e executa a inserção
    $stmt_insert->bind_param(
        "ssdsi",
        $novoResponsavel,
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

// 4. Redireciona com uma mensagem de sucesso ou erro
if ($erros === 0) {
    $_SESSION['success_message'] = "{$quantidade} conta(s) a receber foram repetidas com sucesso!";
} else {
    $_SESSION['success_message'] = "Operação concluída com {$erros} erro(s).";
}

// ATENÇÃO: O caminho de redirecionamento foi ajustado para ../pages/contas_receber.php
header('Location: ../pages/contas_receber.php');
exit;
?>