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
    $fornecedor = trim($_POST['fornecedor_nome']);
    $numero = trim($_POST['numero']);
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $data_vencimento = $_POST['data_vencimento'];
    $id_categoria = $_POST['id_categoria'];
    $usuario_id = $_SESSION['usuario']['id'];
    $enviar_email = isset($_POST['enviar_email']) ? 'S' : 'N'; // Verifica se o checkbox está marcado

    // Validação básica
    if (empty($fornecedor) || empty($numero) || !is_numeric($valor) || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = "Todos os campos, incluindo a categoria, são obrigatórios.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO contas_pagar (fornecedor, numero, valor, data_vencimento, usuario_id, enviar_email, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // O "s" para enviar_email é porque ele é um CHAR, tratado como string.
        $stmt->bind_param("ssdsisi", $fornecedor, $numero, $valor, $data_vencimento, $usuario_id, $enviar_email, $id_categoria);

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