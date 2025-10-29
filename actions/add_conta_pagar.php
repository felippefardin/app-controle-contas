<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    // Pega os dados do usuário da sessão correta
    $usuario_id = $_SESSION['usuario_logado']['id'];

    // Pega os dados do formulário
    $fornecedor_nome = trim($_POST['fornecedor_nome']);
    $id_pessoa_fornecedor = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
    $numero = trim($_POST['numero']);
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    $enviar_email = isset($_POST['enviar_email']) ? 'S' : 'N';

    if (empty($fornecedor_nome) || empty($numero) || !is_numeric($valor) || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = "Todos os campos obrigatórios devem ser preenchidos.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    // 3. INSERE OS DADOS USANDO A COLUNA `usuario_id`
    $sql = "INSERT INTO contas_pagar (fornecedor, id_pessoa_fornecedor, numero, valor, data_vencimento, usuario_id, enviar_email, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // O bind_param para id_usuario foi corrigido para "i" (integer)
    $stmt->bind_param("sisdsisi", $fornecedor_nome, $id_pessoa_fornecedor, $numero, $valor, $data_vencimento, $usuario_id, $enviar_email, $id_categoria);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta a pagar adicionada com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao adicionar conta: " . $stmt->error;
    }

    $stmt->close();
    header('Location: ../pages/contas_pagar.php');
    exit;
}
?>