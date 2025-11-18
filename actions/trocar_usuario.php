<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. Verifica Permissão 
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$is_admin = ($nivel === 'admin' || $nivel === 'master' || $nivel === 'proprietario');
$ja_impersonando = isset($_SESSION['usuario_original_id']);

if (!$is_admin && !$ja_impersonando) {
    header('Location: ../pages/selecionar_usuario.php?erro=sem_permissao_troca');
    exit;
}

// 3. Valida Dados
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_usuario'])) {
    header('Location: ../pages/selecionar_usuario.php?erro=id_invalido');
    exit;
}

// Verifica se a senha foi enviada
if (empty($_POST['senha_usuario'])) {
    header('Location: ../pages/selecionar_usuario.php?erro=senha_vazia');
    exit;
}

$target_id = (int)$_POST['id_usuario'];
$senha_input = $_POST['senha_usuario'];

$conn = getTenantConnection();
if (!$conn) {
    header('Location: ../pages/selecionar_usuario.php?erro=db_error');
    exit;
}

// 4. Busca dados do usuário alvo (INCLUINDO A SENHA hash)
$stmt = $conn->prepare("SELECT id, nome, email, nivel_acesso, foto, senha FROM usuarios WHERE id = ? AND status = 'ativo'");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$result = $stmt->get_result();
$target_user = $result->fetch_assoc();

if ($target_user) {
    // 5. Verifica se a senha fornecida corresponde ao hash do banco
    if (!password_verify($senha_input, $target_user['senha'])) {
        header('Location: ../pages/selecionar_usuario.php?erro=senha_incorreta');
        exit;
    }

    // 6. Lógica de Impersonação (Salva o admin original se for a primeira troca)
    if (!isset($_SESSION['usuario_original_id'])) {
        $_SESSION['usuario_original_id'] = $_SESSION['usuario_id'];
        $_SESSION['usuario_original_nome'] = $_SESSION['usuario_nome'] ?? 'Admin';
        $_SESSION['usuario_original_nivel'] = $_SESSION['nivel_acesso'];
    }

    // 7. Atualiza a sessão para o novo usuário
    $_SESSION['usuario_id'] = $target_user['id'];
    $_SESSION['usuario_nome'] = $target_user['nome'];
    $_SESSION['usuario_email'] = $target_user['email'];
    $_SESSION['nivel_acesso'] = $target_user['nivel_acesso']; 
    $_SESSION['usuario_foto'] = $target_user['foto'];

    // 8. Redireciona para o Dashboard
    header('Location: ../pages/home.php?msg=acesso_simulado');
    exit;

} else {
    header('Location: ../pages/selecionar_usuario.php?erro=usuario_nao_encontrado');
    exit;
}
?>