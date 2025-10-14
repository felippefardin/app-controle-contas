<?php
require_once '../includes/session_init.php';
include('../database.php'); // Sua conexão com o banco

// 1. Verifica se o usuário principal está logado
if (!isset($_SESSION['usuario_principal'])) {
    // Se não estiver logado, nega o acesso
    header('Location: ../pages/login.php?erro=nao_logado');
    exit;
}

// 2. Pega os IDs importantes
$id_para_excluir = $_GET['id'] ?? 0;
$usuario_principal_id = $_SESSION['usuario_principal']['id'];

// Validação básica do ID
if (empty($id_para_excluir)) {
    header("Location: ../pages/usuarios.php?erro=id_invalido");
    exit;
}

// 3. REGRA DE SEGURANÇA: Impede que o usuário principal exclua a si mesmo
if ($id_para_excluir == $usuario_principal_id) {
    header("Location: ../pages/usuarios.php?erro=auto_exclusao");
    // Adicione uma mensagem de erro correspondente em usuarios.php se desejar
    exit;
}

// 4. Prepara e executa a exclusão com segurança
// Garante que o usuário só pode excluir um sub-usuário que ele mesmo criou
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND id_criador = ?");
$stmt->bind_param("ii", $id_para_excluir, $usuario_principal_id);

if ($stmt->execute()) {
    // Verifica se alguma linha foi de fato afetada.
    // Se affected_rows for 0, significa que o ID não pertencia àquele criador.
    if ($stmt->affected_rows > 0) {
        header("Location: ../pages/usuarios.php?sucesso=excluido");
    } else {
        // Tentativa de excluir um usuário que não pertence a ele
        header("Location: ../pages/usuarios.php?erro=permissao");
    }
} else {
    // Erro genérico de banco de dados
    header("Location: ../pages/usuarios.php?erro=db_error");
}

$stmt->close();
$conn->close();
exit;

?>