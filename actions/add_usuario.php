<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// Verifica permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
if ($nivel !== 'admin' && $nivel !== 'master' && $nivel !== 'proprietario') {
    header('Location: ../pages/usuarios.php?erro=1&msg=Sem permissão');
    exit;
}

$conn = getTenantConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $senha = $_POST['senha'];
    $senha_conf = $_POST['senha_confirmar'];
    $nivel_novo = $_POST['nivel'] === 'admin' ? 'admin' : 'padrao';
    $tenant_id = $_SESSION['tenant_id'] ?? null; // Se usar sistema multi-tenant

    // Validações
    if ($senha !== $senha_conf) {
        header('Location: ../pages/add_usuario.php?erro=1&msg=As senhas não conferem');
        exit;
    }

    // Verifica email duplicado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header('Location: ../pages/add_usuario.php?erro=1&msg=Email já cadastrado');
        exit;
    }

    // Insere
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    // Ajuste os campos conforme seu schema.sql exato. 
    // Assumindo: nome, email, cpf, senha, nivel_acesso, status, tenant_id
    $sql = "INSERT INTO usuarios (nome, email, cpf, senha, nivel_acesso, status, tenant_id, criado_por_usuario_id) VALUES (?, ?, ?, ?, ?, 'ativo', ?, ?)";
    $stmt = $conn->prepare($sql);
    $criador = $_SESSION['usuario_id'];
    
    // Se tenant_id for string ou int, ajuste o "s" ou "i" abaixo. Assumi string/misto.
    $stmt->bind_param("ssssssi", $nome, $email, $cpf, $senha_hash, $nivel_novo, $tenant_id, $criador);

    if ($stmt->execute()) {
        header('Location: ../pages/usuarios.php?sucesso=1&msg=Usuário criado com sucesso');
    } else {
        header('Location: ../pages/add_usuario.php?erro=1&msg=Erro ao salvar no banco');
    }
}
?>