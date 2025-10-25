<?php
// actions/add_conta_pagar.php

require_once '../includes/session_init.php';
require_once '../database.php'; // Garante que $conn está disponível

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega os dados do formulário
    $fornecedor_nome = trim($_POST['fornecedor_nome']);
    $id_pessoa_fornecedor = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
    $numero = trim($_POST['numero']);
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = $_POST['id_categoria'];
    $enviar_email = isset($_POST['enviar_email']) ? 'S' : 'N';

    // AJUSTE: Garante que a conta seja sempre salva sob o ID do usuário principal
    $actor_user_id = $_SESSION['usuario']['id'];
    $id_criador = $_SESSION['usuario']['id_criador'] ?? 0;
    $usuario_id_to_save = ($id_criador > 0) ? $id_criador : $actor_user_id;

    // Validação básica
    if (empty($fornecedor_nome) || empty($numero) || !is_numeric($valor) || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = "Todos os campos, incluindo a categoria, são obrigatórios.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    try {
        // AJUSTE: Adiciona id_pessoa_fornecedor e o ID do usuário principal ao INSERT
        $stmt = $conn->prepare(
            "INSERT INTO contas_pagar (fornecedor, id_pessoa_fornecedor, numero, valor, data_vencimento, usuario_id, enviar_email, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // AJUSTE: Atualiza o bind_param para usar o ID do usuário principal
        $stmt->bind_param("sisdsisi", $fornecedor_nome, $id_pessoa_fornecedor, $numero, $valor, $data_vencimento, $usuario_id_to_save, $enviar_email, $id_categoria);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Conta a pagar adicionada com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao adicionar conta: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();

    } catch (mysqli_sql_exception $e) {
        // Captura o erro para uma depuração mais fácil
        $_SESSION['error_message'] = "Erro de banco de dados: " . $e->getMessage();
    }

    header('Location: ../pages/contas_pagar.php');
    exit;
}
?>