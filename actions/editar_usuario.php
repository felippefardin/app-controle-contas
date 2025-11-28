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

// [CORREÇÃO DUPLA PERSONALIDADE] 1. Buscar o email ATUAL antes da atualização
// Precisamos disso para encontrar o usuário no banco Master e atualizar lá também
$emailAntigo = '';
$stmtGetOld = $conn->prepare("SELECT email FROM usuarios WHERE id = ?");
$stmtGetOld->bind_param("i", $id);
$stmtGetOld->execute();
$stmtGetOld->bind_result($emailAntigo);
$stmtGetOld->fetch();
$stmtGetOld->close();

// 2. Atualizar Dados Básicos no TENANT
$sql = "UPDATE usuarios SET nome=?, email=?, cpf=?, nivel_acesso=? WHERE id=?";
$params = [$nome, $email, $cpf, $novo_nivel, $id];
$types = "ssssi";

$senha_hash = null;
if (!empty($senha)) {
    $sql = "UPDATE usuarios SET nome=?, email=?, cpf=?, nivel_acesso=?, senha=? WHERE id=?";
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $params = [$nome, $email, $cpf, $novo_nivel, $senha_hash, $id];
    $types = "sssssi";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    
    // [CORREÇÃO DUPLA PERSONALIDADE] 3. Sincronizar com o Banco MASTER
    // Se o usuário foi atualizado no Tenant, atualizamos as credenciais de login no Master
    try {
        $connMaster = getMasterConnection();
        if ($connMaster && !$connMaster->connect_error) {
            
            // Prepara query para o Master (usando o email ANTIGO para achar o registro)
            if (!empty($senha)) {
                // Atualiza Senha, Email e Nome
                $sqlMaster = "UPDATE usuarios SET nome=?, email=?, senha=? WHERE email=?";
                $stmtM = $connMaster->prepare($sqlMaster);
                $stmtM->bind_param("ssss", $nome, $email, $senha_hash, $emailAntigo);
            } else {
                // Atualiza apenas Email e Nome (se mudou)
                $sqlMaster = "UPDATE usuarios SET nome=?, email=? WHERE email=?";
                $stmtM = $connMaster->prepare($sqlMaster);
                $stmtM->bind_param("sss", $nome, $email, $emailAntigo);
            }
            
            $stmtM->execute();
            $stmtM->close();
            $connMaster->close();
        }
    } catch (Exception $e) {
        // Log silencioso para não travar o fluxo, já que o Tenant foi atualizado
        error_log("Erro ao sincronizar Master: " . $e->getMessage());
    }

    set_flash_message('success', 'Usuário atualizado com sucesso (Sincronizado)!');
} else {
    set_flash_message('danger', 'Erro ao atualizar usuário: ' . $stmt->error);
}
$stmt->close();

// 4. Atualizar Permissões (Apenas Admin)
if ($is_admin) {
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