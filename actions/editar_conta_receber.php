<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $responsavel = trim($_POST['responsavel']);
    $numero = trim($_POST['numero']);
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']); // Converte para formato de banco de dados
    $data_vencimento = $_POST['data_vencimento'];

    if ($id > 0 && !empty($responsavel) && is_numeric($valor)) {
        $stmt = $conn->prepare("UPDATE contas_receber SET responsavel = ?, numero = ?, valor = ?, data_vencimento = ? WHERE id = ?");
        $stmt->bind_param("ssdsi", $responsavel, $numero, $valor, $data_vencimento, $id);

        if ($stmt->execute()) {
            header("Location: ../pages/contas_receber.php?msg=Conta atualizada com sucesso!");
        } else {
            header("Location: ../pages/editar_conta_receber.php?id={$id}&erro=Erro ao atualizar a conta.");
        }
        $stmt->close();
    } else {
        header("Location: ../pages/editar_conta_receber.php?id={$id}&erro=Dados inválidos.");
    }
    $conn->close();
} else {
    header("Location: ../pages/contas_receber.php");
    exit;
}
?>