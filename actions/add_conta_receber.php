<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        $_SESSION['error_message'] = 'Falha na conexão com o banco de dados.';
        header('Location: ../pages/contas_receber.php');
        exit;
    }

    // Pega os dados da sessão e do formulário
    $usuario_id = $_SESSION['usuario_logado']['id'];
    $responsavel_nome = trim($_POST['responsavel_nome']);
    $id_pessoa_fornecedor = !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null;
    $numero = trim($_POST['numero']);
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor']));
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;

    if (empty($responsavel_nome) || empty($numero) || !is_numeric($valor) || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = 'Todos os campos obrigatórios devem ser preenchidos.';
        header('Location: ../pages/contas_receber.php');
        exit;
    }

    // 2. INSERE OS DADOS NO BANCO DE DADOS DO USUÁRIO CORRETO
    $stmt = $conn->prepare(
        "INSERT INTO contas_receber (responsavel, id_pessoa_fornecedor, numero, valor, data_vencimento, usuario_id, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sisdsii", $responsavel_nome, $id_pessoa_fornecedor, $numero, $valor, $data_vencimento, $usuario_id, $id_categoria);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Conta a receber adicionada com sucesso!';
    } else {
        $_SESSION['error_message'] = 'Erro ao adicionar conta: ' . $stmt->error;
    }

    $stmt->close();
    header('Location: ../pages/contas_receber.php');
    exit;
}
?>