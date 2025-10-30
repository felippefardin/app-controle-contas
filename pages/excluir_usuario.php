<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?erro=nao_logado');
    exit;
}

// 2. Pega os IDs importantes
$id_para_excluir = $_GET['id'] ?? 0;
$usuario_logado_id = $_SESSION['usuario_logado']['id'];

// Validação básica do ID
if (empty($id_para_excluir)) {
    header("Location: ../pages/usuarios.php?erro=id_invalido");
    exit;
}

// 3. REGRA DE SEGURANÇA: Impede que o usuário exclua a si mesmo
if ($id_para_excluir == $usuario_logado_id) {
    header("Location: ../pages/usuarios.php?erro=auto_exclusao");
    exit;
}

// 4. Prepara e executa a exclusão com segurança
$conn = getTenantConnection();
if ($conn === null) {
    header("Location: ../pages/usuarios.php?erro=db_error");
    exit;
}

$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_para_excluir);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("Location: ../pages/usuarios.php?sucesso=excluido");
    } else {
        header("Location: ../pages/usuarios.php?erro=permissao");
    }
} else {
    header("Location: ../pages/usuarios.php?erro=db_error");
}

$stmt->close();
$conn->close();
exit;
?>