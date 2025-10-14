<?php
require_once '../includes/session_init.php';
include('../database.php');

// Se não houver um usuário principal na sessão, não há como prosseguir
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id']) && isset($_POST['senha'])) {
    $usuario_selecionado_id = (int)$_POST['usuario_id'];
    $senha_fornecida = $_POST['senha'];
    $usuario_principal_id = $_SESSION['usuario_principal']['id'];

    // Garante que o usuário selecionado pertence à conta principal
    $sql = "SELECT * FROM usuarios WHERE id = ? AND (id = ? OR id_criador = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $usuario_selecionado_id, $usuario_principal_id, $usuario_principal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($usuario_selecionado = $result->fetch_assoc()) {
        // Verifica se a senha fornecida corresponde ao hash no banco de dados
        if (password_verify($senha_fornecida, $usuario_selecionado['senha'])) {
            // Remove a senha do array antes de guardar na sessão por segurança
            unset($usuario_selecionado['senha']);
            
            // Define o usuário ativo na sessão
            $_SESSION['usuario'] = $usuario_selecionado;

            // Garante que a sessão seja salva antes do redirecionamento
            session_write_close();

            // Redireciona para a página inicial
            header('Location: ../pages/home.php');
            exit;
        }
    }
}

// Em caso de erro (usuário não encontrado ou senha inválida)
$_SESSION['erro_selecao'] = "Usuário ou senha inválida.";
header('Location: ../pages/selecionar_usuario.php');
exit;
?>