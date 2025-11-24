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
    $tenant_id = $_SESSION['tenant_id'] ?? null;
    $criador = $_SESSION['usuario_id'];

    // Tratamento das permissões
    $json_permissoes = null;
    if ($nivel_novo === 'padrao' && isset($_POST['permissoes']) && is_array($_POST['permissoes'])) {
        $json_permissoes = json_encode($_POST['permissoes']);
    }

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
    
    // Atualizado para incluir a coluna 'permissoes'
    $sql = "INSERT INTO usuarios (nome, email, cpf, senha, nivel_acesso, status, tenant_id, criado_por_usuario_id, permissoes) VALUES (?, ?, ?, ?, ?, 'ativo', ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // ssssssis (string, string, string, string, string, string, int, string/null)
    // O tenant_id pode ser string, criador int, permissoes string(json)
    $stmt->bind_param("ssssssis", $nome, $email, $cpf, $senha_hash, $nivel_novo, $tenant_id, $criador, $json_permissoes);

    if ($stmt->execute()) {
        header('Location: ../pages/usuarios.php?sucesso=1&msg=Usuário criado com sucesso');
    } else {
        header('Location: ../pages/add_usuario.php?erro=1&msg=Erro ao salvar no banco: ' . $conn->error);
    }
}
?>