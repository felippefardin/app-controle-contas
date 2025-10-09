<?php
session_start();
include('../database.php');

$email = $_POST['email'];
$senha = $_POST['senha'];

// --- MODIFICAÇÃO: Adicionado `status = 'ativo'` na consulta ---
$sql = "SELECT * FROM usuarios WHERE email = ? AND id_criador IS NULL AND status = 'ativo'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_principal'] = $user;
        session_write_close();
        header('Location: ../pages/selecionar_usuario.php');
        exit;
    }
}

// --- MENSAGEM DE ERRO ATUALIZADA ---
$_SESSION['erro_login'] = "Credenciais inválidas, usuário não autorizado ou bloqueado.";
header('Location: ../pages/login.php');
exit;
?>