<?php
require_once '../includes/session_init.php';
include('../database.php');

// Se não houver um usuário principal na sessão, redireciona para o login.
if (!isset($_SESSION['usuario_principal'])) {
    $_SESSION['erro_login'] = "Sessão de usuário principal não encontrada. Faça o login novamente.";
    header('Location: ../pages/login.php');
    exit;
}

// Obtém a conexão com o banco de dados do tenant
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id']) && isset($_POST['senha'])) {
    $usuario_selecionado_id = (int)$_POST['usuario_id'];
    $senha_fornecida = $_POST['senha'];
    $usuario_principal_id = $_SESSION['usuario_principal']['id'];

    // Busca o usuário selecionado no banco de dados do tenant
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $usuario_selecionado_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($usuario_selecionado = $result->fetch_assoc()) {
        // Garante que o usuário selecionado é a conta principal ou uma conta criada por ela
        if ($usuario_selecionado['id'] == $usuario_principal_id || $usuario_selecionado['id_criador'] == $usuario_principal_id) {
            // Verifica se a senha fornecida corresponde à senha no banco de dados
            if (password_verify($senha_fornecida, $usuario_selecionado['senha'])) {
                unset($usuario_selecionado['senha']);
                
                // Atualiza a sessão do usuário logado
                $_SESSION['usuario_logado'] = $usuario_selecionado;

                header('Location: ../pages/home.php');
                exit;
            }
        }
    }
}

// Se chegou até aqui, houve um erro
$_SESSION['erro_selecao'] = "Usuário ou senha inválida.";
header('Location: ../pages/selecionar_usuario.php');
exit;
?>