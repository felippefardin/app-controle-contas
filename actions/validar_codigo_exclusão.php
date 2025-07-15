<?php
session_start();
include('../database.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] != 'admin') {
    echo "Acesso negado";
    exit;
}

$id_conta = $_POST['id_conta'] ?? null;
$codigo = $_POST['codigo'] ?? null;

if (!$id_conta || !$codigo) {
    echo "Dados incompletos";
    exit;
}

// Verificar se o código é válido, não usado, criado nos últimos 10 minutos e corresponde ao e-mail do admin
$stmt = $conn->prepare("SELECT id FROM codigos_confirmacao WHERE codigo = ? AND email_admin = ? AND usado = 0 AND criado_em > (NOW() - INTERVAL 10 MINUTE)");
$stmt->bind_param("ss", $codigo, $_SESSION['usuario']['email']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo "Código inválido ou expirado.";
    exit;
}

$stmt->bind_result($id_codigo);
$stmt->fetch();

// Marcar código como usado
$stmt_update = $conn->prepare("UPDATE codigos_confirmacao SET usado = 1 WHERE id = ?");
$stmt_update->bind_param("i", $id_codigo);
$stmt_update->execute();

// Excluir a conta a receber
$stmt_delete = $conn->prepare("DELETE FROM contas_receber WHERE id = ?");
$stmt_delete->bind_param("i", $id_conta);
$stmt_delete->execute();

// Redirecionar para a lista de contas a receber
header('Location: ../pages/contas_receber.php');
exit;
