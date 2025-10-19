<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // O nome do responsável vem do campo de autocompletar
    $responsavel = trim($_POST['responsavel_nome']); 
    $numero = trim($_POST['numero']);
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor']));
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = $_POST['id_categoria'];
    $usuario_id = $_SESSION['usuario']['id'];

    // Validação básica
    if (empty($responsavel) || empty($numero) || !is_numeric($valor) || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = 'Todos os campos, incluindo a categoria, são obrigatórios.';
        header('Location: ../pages/contas_receber.php');
        exit;
    }

    // QUERY CORRIGIDA: Removida a coluna 'id_pessoa' que não existe na sua tabela.
    $stmt = $conn->prepare("INSERT INTO contas_receber (responsavel, numero, valor, data_vencimento, usuario_id, id_categoria) VALUES (?, ?, ?, ?, ?, ?)");
    // BIND_PARAM CORRIGIDO: Ajustado para o número correto de parâmetros.
    $stmt->bind_param("ssdsii", $responsavel, $numero, $valor, $data_vencimento, $usuario_id, $id_categoria);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Conta a receber adicionada com sucesso!';
    } else {
        // Para depuração, caso ainda haja erros:
        // $_SESSION['error_message'] = 'Erro ao adicionar conta: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header('Location: ../pages/contas_receber.php');
    exit;
}
?>