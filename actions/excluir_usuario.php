<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Garante que apenas o proprietário da conta pode acessar esta ação
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado']['nivel_acesso'] !== 'proprietario') {
    $_SESSION['erro_usuarios'] = "Você não tem permissão para excluir usuários.";
    header('Location: ../pages/usuarios.php');
    exit;
}

if (isset($_GET['id'])) {
    $id_usuario_para_excluir = (int)$_GET['id'];
    $id_usuario_logado = $_SESSION['usuario_logado']['id'];

    // Impede que o proprietário exclua a própria conta
    if ($id_usuario_para_excluir === $id_usuario_logado) {
        $_SESSION['erro_usuarios'] = "Você não pode excluir sua própria conta de administrador.";
        header('Location: ../pages/usuarios.php');
        exit;
    }

    $conn = getTenantConnection();
    if ($conn) {
        // Verifica se o usuário a ser excluído realmente existe no banco do tenant
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt_check->bind_param('i', $id_usuario_para_excluir);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Procede com a exclusão
            $stmt_delete = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt_delete->bind_param('i', $id_usuario_para_excluir);
            if ($stmt_delete->execute()) {
                $_SESSION['sucesso_usuarios'] = "Usuário excluído com sucesso!";
            } else {
                $_SESSION['erro_usuarios'] = "Erro ao excluir o usuário.";
            }
            $stmt_delete->close();
        } else {
            $_SESSION['erro_usuarios'] = "Usuário não encontrado para exclusão.";
        }
        $stmt_check->close();
        $conn->close();
    } else {
        $_SESSION['erro_usuarios'] = "Falha na conexão com o banco de dados.";
    }
} else {
    $_SESSION['erro_usuarios'] = "ID de usuário não fornecido.";
}

header('Location: ../pages/usuarios.php');
exit;
?>