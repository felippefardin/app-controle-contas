<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // O nome do responsável vem do campo de autocompletar
    $responsavel_nome = trim($_POST['responsavel_nome']);
    // AJUSTE: Captura o ID do responsável/cliente do campo hidden
    $id_pessoa_fornecedor = !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null;
    $numero = trim($_POST['numero']);
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor']));
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = $_POST['id_categoria'];
    $usuario_id = $_SESSION['usuario']['id'];

    // Validação básica
    if (empty($responsavel_nome) || empty($numero) || !is_numeric($valor) || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = 'Todos os campos, incluindo a categoria, são obrigatórios.';
        header('Location: ../pages/contas_receber.php');
        exit;
    }

    // AJUSTE: Adiciona id_pessoa_fornecedor ao INSERT
    $stmt = $conn->prepare(
        "INSERT INTO contas_receber (responsavel, id_pessoa_fornecedor, numero, valor, data_vencimento, usuario_id, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    // AJUSTE: Atualiza o bind_param para incluir o novo campo (i para integer)
    $stmt->bind_param("sisdsii", $responsavel_nome, $id_pessoa_fornecedor, $numero, $valor, $data_vencimento, $usuario_id, $id_categoria);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Conta a receber adicionada com sucesso!';
    } else {
        // Para depuração, caso ainda haja erros:
        $_SESSION['error_message'] = 'Erro ao adicionar conta: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header('Location: ../pages/contas_receber.php');
    exit;
}
?>