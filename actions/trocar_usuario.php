<?php
require_once '../includes/session_init.php';
include('../database.php');

// Verifica se há um usuário logado na sessão.
if (!isset($_SESSION['usuario_logado'])) {
    $_SESSION['erro_login'] = "Sessão de usuário não encontrada. Faça o login novamente.";
    header('Location: ../pages/login.php');
    exit;
}

// Obtém a conexão com o banco de dados do tenant.
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id']) && isset($_POST['senha'])) {
    $usuario_selecionado_id = (int)$_POST['usuario_id'];
    $senha_fornecida = $_POST['senha'];

    // Busca o usuário selecionado no banco de dados do tenant.
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $usuario_selecionado_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($usuario_selecionado = $result->fetch_assoc()) {
        // Verifica se a senha fornecida corresponde à do banco de dados.
        if (password_verify($senha_fornecida, $usuario_selecionado['senha'])) {
            unset($usuario_selecionado['senha']);
            
            // Garante que a sessão 'usuario_principal' seja mantida, se já existir.
            if (!isset($_SESSION['usuario_principal'])) {
                $_SESSION['usuario_principal'] = $_SESSION['usuario_logado'];
            }
            
            // Atualiza a sessão do usuário logado para o usuário selecionado.
            $_SESSION['usuario_logado'] = $usuario_selecionado;

            header('Location: ../pages/home.php');
            exit;
        }
    }
}

// Se a autenticação falhar, redireciona de volta com uma mensagem de erro.
$_SESSION['erro_selecao'] = "Dados incorretos, não foi possível trocar de usuário.";
header('Location: ../pages/selecionar_usuario.php');
exit;
?>