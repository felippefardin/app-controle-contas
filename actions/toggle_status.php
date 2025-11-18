<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica Sessão
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. Verifica Permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
if ($nivel !== 'admin' && $nivel !== 'master' && $nivel !== 'proprietario') {
    header('Location: ../pages/usuarios.php?erro=1&msg=Permissão negada');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_proprio = $_SESSION['usuario_id'];

if ($id && $id != $id_proprio) {
    $conn = getTenantConnection();
    if (!$conn) {
        header('Location: ../pages/usuarios.php?erro=1&msg=Erro de banco de dados');
        exit;
    }
    
    // Lógica de inversão: Se for 'ativo', vira 'inativo'. Caso contrário, vira 'ativo'.
    $sql = "UPDATE usuarios SET status = CASE WHEN status = 'ativo' THEN 'inativo' ELSE 'ativo' END WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: ../pages/usuarios.php?sucesso=1&msg=Status alterado com sucesso');
    } else {
        header('Location: ../pages/usuarios.php?erro=1&msg=Erro ao atualizar status');
    }
} else {
    header('Location: ../pages/usuarios.php?erro=1&msg=ID inválido');
}
exit;
?>