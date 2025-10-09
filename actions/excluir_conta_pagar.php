<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Inclui a conexão que define a variável $conn
include('../database.php');

// Pega o ID e o perfil do usuário logado
$usuarioId = $_SESSION['usuario']['id'];
$perfil = $_SESSION['usuario']['perfil'];

// Verifica se veio o ID na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}

$id = intval($_GET['id']);

// Monta a query DELETE
$sql = "DELETE FROM contas_pagar WHERE id = ?";
$params = "i";
$bindVars = [$id];

// Se o usuário não for um admin, adiciona o filtro de usuario_id
if ($perfil !== 'admin') {
    $sql .= " AND usuario_id = ?";
    $params .= "i";
    $bindVars[] = $usuarioId;
}

// Prepara e executa o DELETE
$stmt = $conn->prepare($sql);
$stmt->bind_param($params, ...$bindVars);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // Redireciona de volta para a tela de contas a pagar
    header('Location: ../pages/contas_pagar_baixadas.php?excluido=1');
    exit;
} else {
    die("Erro ao excluir conta: " . $conn->error);
}
?>