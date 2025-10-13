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
$responsavel_id = (int)$_POST['responsavel_id'];
$data_vencimento = $_POST['data_vencimento'];
$numero = htmlspecialchars(trim($_POST['numero']));
$valor = floatval(str_replace(',', '.', $_POST['valor']));

// Pega o ID do usuário logado
$usuarioId = $_SESSION['usuario']['id'];

// Busca o nome do responsável
$responsavel = '';
if ($responsavel_id > 0) {
    $stmt_pessoa = $conn->prepare("SELECT nome FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
    $stmt_pessoa->bind_param("ii", $responsavel_id, $usuarioId);
    $stmt_pessoa->execute();
    $result_pessoa = $stmt_pessoa->get_result();
    if ($row_pessoa = $result_pessoa->fetch_assoc()) {
        $responsavel = $row_pessoa['nome'];
    }
    $stmt_pessoa->close();
}

// Validação
if (empty($responsavel) || empty($data_vencimento) || empty($numero) || empty($valor)) {
    // Adicionar uma mensagem de erro, se desejar
    $_SESSION['error_message'] = 'Todos os campos são obrigatórios e o responsável deve ser válido.';
    header('Location: ../pages/contas_receber.php');
    exit;
}

// Prepara e executa a inserção
$sql = "INSERT INTO contas_receber (responsavel, data_vencimento, numero, valor, usuario_id) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Adicionar uma mensagem de erro, se desejar
    // $_SESSION['error_message'] = "Erro na preparação da query: " . $conn->error;
    header('Location: ../pages/contas_receber.php');
    exit;
}

$stmt->bind_param("sssdi", $responsavel, $data_vencimento, $numero, $valor, $usuarioId);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Conta a receber adicionada com sucesso!";
} else {
    // Adicionar uma mensagem de erro, se desejar
    // $_SESSION['error_message'] = "Erro ao adicionar conta: " . $stmt->error;
}

$stmt->close();
$conn->close();

header('Location: ../pages/contas_receber.php');
exit;
?>