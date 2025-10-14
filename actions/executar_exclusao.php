<?php
include('../database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';

    if (!empty($token)) {
        $conn->begin_transaction();

        try {
            // 1️⃣ Valida o token
            $stmt = $conn->prepare("SELECT id_usuario FROM solicitacoes_exclusao WHERE token = ? AND expira_em > NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $id_usuario = $result->fetch_assoc()['id_usuario'];

                // 2️⃣ Exclui o usuário
                // Todas as contas_pagar, contas_receber e solicitacoes_exclusao vinculadas serão deletadas automaticamente
                $stmt_delete_user = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt_delete_user->bind_param("i", $id_usuario);
                $stmt_delete_user->execute();
                $stmt_delete_user->close();

                $conn->commit();

                // 3️⃣ Finaliza a sessão e redireciona
                session_start();
                session_destroy();
                header("Location: ../pages/login.php?sucesso=conta_excluida");
                exit;

            } else {
                throw new Exception("Token inválido ou expirado.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            // Para depuração, você pode logar o erro: error_log($e->getMessage());
            header("Location: ../pages/login.php?erro=falha_exclusao");
            exit;
        }
    }
}

header("Location: ../pages/login.php?erro=acesso_negado");
exit;
?>
