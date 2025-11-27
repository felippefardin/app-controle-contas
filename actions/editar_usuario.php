<?php
require_once '../includes/session_init.php';
include('../database.php');
require_once '../includes/utils.php'; // Importa utils

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

if ($stmt->execute()) {
    set_flash_message('success', 'Usuário atualizado com sucesso!');
} else {
    set_flash_message('danger', 'Erro ao atualizar usuário: ' . $stmt->error);
}
$stmt->close();

// 2. Atualizar Permissões (Apenas Admin)
if ($is_admin) {
    // Limpa atuais (na tabela de permissoes se usar JSON é diferente, mas mantendo a lógica original do seu código que usava usuario_permissoes ou JSON)
    // Nota: Seu código original de add_usuario usava JSON na coluna permissoes, mas o editar estava usando uma tabela auxiliar usuario_permissoes.
    // Vou manter a lógica que estava no seu arquivo 'editar_usuario.php' original enviado, mas atenção a essa inconsistência no seu banco.
    // Se o seu sistema usa JSON na tabela usuarios, o código abaixo precisaria ser ajustado. 
    // Como solicitado, não altero estrutura, apenas a mensagem.

    $conn->query("DELETE FROM usuario_permissoes WHERE usuario_id = $id");

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
header("Location: ../pages/usuarios.php");
exit;
?>