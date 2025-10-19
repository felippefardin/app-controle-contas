<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = $_SESSION['usuario']['id'];
    $nome = trim($_POST['nome']);
    $tipo = $_POST['tipo'];
    $id = $_POST['id'];

    if (empty($nome) || empty($tipo)) {
        // Adicionar mensagem de erro
        header('Location: ../pages/categorias.php');
        exit;
    }

    if (empty($id)) { // Inserir nova categoria
        $stmt = $conn->prepare("INSERT INTO categorias (id_usuario, nome, tipo) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $usuarioId, $nome, $tipo);
    } else { // Atualizar categoria existente
        $stmt = $conn->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ? AND id_usuario = ?");
        $stmt->bind_param("ssii", $nome, $tipo, $id, $usuarioId);
    }

    $stmt->execute();
    $stmt->close();
}

header('Location: ../pages/categorias.php');
exit;
?>