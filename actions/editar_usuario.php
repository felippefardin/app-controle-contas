<?php
require_once '../includes/session_init.php';
include('../database.php');

$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$is_admin = in_array($nivel, ['admin', 'master', 'proprietario']);

if (!isset($_POST['id'])) { header('Location: ../pages/usuarios.php'); exit; }

$id = intval($_POST['id']);
$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$cpf = trim($_POST['cpf']);
$senha = $_POST['senha'];
$novo_nivel = $_POST['nivel'] ?? 'padrao';

$conn = getTenantConnection();

// 1. Atualizar Dados Básicos
$sql = "UPDATE usuarios SET nome=?, email=?, cpf=?, nivel_acesso=? WHERE id=?";
$params = [$nome, $email, $cpf, $novo_nivel, $id];
$types = "ssssi";

if (!empty($senha)) {
    $sql = "UPDATE usuarios SET nome=?, email=?, cpf=?, nivel_acesso=?, senha=? WHERE id=?";
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $params = [$nome, $email, $cpf, $novo_nivel, $senha_hash, $id];
    $types = "sssssi";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->close();

// 2. Atualizar Permissões (Apenas Admin)
if ($is_admin) {
    // Limpa atuais
    $conn->query("DELETE FROM usuario_permissoes WHERE usuario_id = $id");

    // Insere novas se houver checkboxes marcados
    if (isset($_POST['permissoes']) && is_array($_POST['permissoes'])) {
        $stmtPerm = $conn->prepare("INSERT INTO usuario_permissoes (usuario_id, permissao_id) VALUES (?, ?)");
        foreach ($_POST['permissoes'] as $permId) {
            $permId = intval($permId);
            $stmtPerm->bind_param("ii", $id, $permId);
            $stmtPerm->execute();
        }
        $stmtPerm->close();
    }
}

$conn->close();
header("Location: ../pages/usuarios.php?msg=Usuário atualizado com sucesso");
exit;
?>