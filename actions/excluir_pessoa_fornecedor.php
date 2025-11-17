<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

$conn = getTenantConnection();
if (!$conn) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

$usuarioId = $_SESSION['usuario_id'];
$id = intval($_GET['id']);

if ($id <= 0) {
    $_SESSION['mensagem_erro'] = "ID inválido.";
    header('Location: ../pages/cadastro_pessoa_fornecedor.php');
    exit;
}

$stmt = $conn->prepare("DELETE FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id, $usuarioId);

if ($stmt->execute()) {
    $_SESSION['mensagem_sucesso'] = "Cadastro excluído com sucesso!";
} else {
    $_SESSION['mensagem_erro'] = "Erro ao excluir: " . $conn->error;
}

header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
exit;
?>
