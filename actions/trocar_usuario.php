<?php
require_once '../includes/session_init.php';
include('../database.php');

// --- Início do Código de Depuração ---

// Formata os dados da sessão e do POST para serem legíveis
$debugData = "Timestamp: " . date('Y-m-d H:i:s') . "\n";
$debugData .= "ID da Sessão: " . session_id() . "\n";
$debugData .= "Conteúdo de \$_SESSION:\n" . print_r($_SESSION, true) . "\n";
$debugData .= "Conteúdo de \$_POST:\n" . print_r($_POST, true) . "\n";
$debugData .= "--------------------------------------------------\n\n";

// Guarda os dados no ficheiro debug.log (verifique as permissões de escrita na pasta 'actions')
file_put_contents('debug.log', $debugData, FILE_APPEND);

// --- Fim do Código de Depuração ---

// Se não houver um usuário principal na sessão, redireciona para o login.
if (!isset($_SESSION['usuario_principal'])) {
    // Adiciona uma mensagem de erro específica antes de redirecionar
    $_SESSION['erro_login'] = "Sessão de usuário principal não encontrada. Faça o login novamente.";
    header('Location: ../pages/login.php');
    exit;
}

// O resto do seu código continua aqui...

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
        // Verifica se a senha fornecida corresponde à senha no banco de dados
        if (password_verify($senha_fornecida, $usuario_selecionado['senha'])) {
            unset($usuario_selecionado['senha']);
            $_SESSION['usuario'] = $usuario_selecionado;

            session_write_close();
            header('Location: ../pages/home.php');
            exit;
        }
    }
}

// Se chegou até aqui, houve um erro
$_SESSION['erro_selecao'] = "Usuário ou senha inválida.";

session_write_close();
header('Location: ../pages/selecionar_usuario.php');
exit;
?>