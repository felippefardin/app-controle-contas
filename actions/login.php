<?php
session_start();
include('../database.php');

$email = $_POST['email'];
$senha = $_POST['senha'];

// Apenas usuários principais (que não foram criados por ninguém) podem iniciar a sessão
$sql = "SELECT * FROM usuarios WHERE email = ? AND id_criador IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($senha, $user['senha'])) {
        // Armazena o usuário principal na sessão
        $_SESSION['usuario_principal'] = $user;
        
        // Garante que a sessão seja salva antes do redirecionamento
        session_write_close();

        // Redireciona para a tela de seleção de usuário
        header('Location: ../pages/selecionar_usuario.php');
        exit;
    }
}

// Se as credenciais forem inválidas ou o usuário não for principal
$_SESSION['erro_login'] = "Credenciais inválidas ou usuário não autorizado.";
header('Location: ../pages/login.php');
exit;
?>