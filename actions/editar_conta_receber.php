<?php
require_once '../includes/session_init.php';
include('../database.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $responsavel = trim($_POST['responsavel']);
    $numero = trim($_POST['numero']);
    // Mantém a conversão do valor para o formato do banco de dados
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor']));
    $data_vencimento = $_POST['data_vencimento'];

    if ($id > 0 && !empty($responsavel) && is_numeric($valor)) {
        $stmt = $conn->prepare("UPDATE contas_receber SET responsavel = ?, numero = ?, valor = ?, data_vencimento = ? WHERE id = ?");
        $stmt->bind_param("ssdsi", $responsavel, $numero, $valor, $data_vencimento, $id);

        if ($stmt->execute()) {
            // Define a mensagem de sucesso na sessão
            $_SESSION['success_message'] = "Conta editada com sucesso!";
            // Redireciona para a página de listagem
            header("Location: ../pages/contas_receber.php");
            exit();
        } else {
            // Em caso de erro, redireciona de volta para a página de edição com um erro
            // (Esta parte pode ser melhorada para também usar sessões de erro)
            header("Location: ../pages/editar_conta_receber.php?id={$id}&erro=Erro ao atualizar a conta.");
            exit();
        }
        $stmt->close();
    } else {
        // Redireciona de volta com erro de dados inválidos
        header("Location: ../pages/editar_conta_receber.php?id={$id}&erro=Dados inválidos.");
        exit();
    }
    $conn->close();
} else {
    // Se não for um POST, redireciona para a página principal
    header("Location: ../pages/contas_receber.php");
    exit;
}
?>