<?php
session_start();
include('../includes/header.php');
include('../database.php');

// A verificação agora é baseada no 'usuario_principal'
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: login.php');
    exit;
}

// Mensagens
$mensagem_sucesso = '';
$mensagem_erro = '';

if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1) {
    $mensagem_sucesso = "Usuário salvo com sucesso!";
}

if (isset($_GET['erro'])) {
    switch($_GET['erro']) {
        case 'duplicado_email':
            $mensagem_erro = "Este e-mail já está cadastrado em outro usuário!";
            break;
        case 'duplicado_cpf':
            $mensagem_erro = "Este CPF já está cadastrado em outro usuário!";
            break;
        case 'senha':
            $mensagem_erro = "As senhas não coincidem!";
            break;
        default:
            $mensagem_erro = "Erro ao salvar usuário!";
    }
}
// Pega o ID da conta principal
$usuario_principal_id = $_SESSION['usuario_principal']['id'];

// Consulta usuários: o próprio principal E os que ele criou
$stmt = $conn->prepare("SELECT id, nome, email, cpf, telefone FROM usuarios WHERE id = ? OR id_criador = ? ORDER BY nome ASC");
$stmt->bind_param("ii", $usuario_principal_id, $usuario_principal_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
    include('../includes/footer.php');
    exit;
}
?>