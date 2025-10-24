<?php
require_once '../includes/session_init.php';
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
        // --- VERIFICAÇÃO DE ESTOQUE BAIXO (Acontece somente no login) ---
        $id_usuario_logado = $user['id'];
        $stmt_estoque = $conn->prepare("SELECT nome, quantidade_estoque FROM produtos WHERE id_usuario = ? AND quantidade_estoque <= quantidade_minima AND quantidade_minima > 0");
        $stmt_estoque->bind_param("i", $id_usuario_logado);
        $stmt_estoque->execute();
        $result_estoque = $stmt_estoque->get_result();
        $produtos_estoque_baixo = [];
        while ($produto = $result_estoque->fetch_assoc()) {
            $produtos_estoque_baixo[] = $produto;
        }
        if (!empty($produtos_estoque_baixo)) {
            // Salva os produtos na sessão para mostrar o alerta uma única vez
            $_SESSION['produtos_estoque_baixo'] = $produtos_estoque_baixo;
        }
        // --- FIM DA VERIFICAÇÃO DE ESTOQUE BAIXO ---

        // --- NOVA VERIFICAÇÃO DE NÍVEL DE ACESSO ---
        if ($user['nivel_acesso'] === 'proprietario') {
            $_SESSION['proprietario'] = $user; // Armazena os dados do proprietário em uma sessão separada
            session_write_close();
            // Linha CORRIGIDA
            header('Location: ../pages/admin/selecionar_conta.php'); // Redireciona para a nova página de administração
            exit;
        }
        // --- FIM DA NOVA VERIFICAÇÃO ---

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