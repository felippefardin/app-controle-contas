<?php
require_once '../includes/session_init.php';
include('../database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anonimo = isset($_POST['anonimo']) ? 1 : 0;
    $nome = $anonimo ? 'Anônimo' : trim($_POST['nome']);
    $email = $anonimo ? null : trim($_POST['email']);
    $whatsapp = $anonimo ? null : trim($_POST['whatsapp']);
    $descricao = trim($_POST['descricao']);
    $pontuacao = intval($_POST['pontuacao']);

    if (empty($descricao) || $pontuacao < 1 || $pontuacao > 5) {
        echo json_encode(['status' => 'error', 'msg' => 'Preencha a descrição e a pontuação corretamente.']);
        exit;
    }

    $conn = getMasterConnection();
    $stmt = $conn->prepare("INSERT INTO feedbacks (nome, email, whatsapp, descricao, pontuacao, anonimo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $nome, $email, $whatsapp, $descricao, $pontuacao, $anonimo);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'msg' => 'Feedback enviado! Obrigado pela avaliação.']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Erro ao enviar feedback.']);
    }
    $conn->close();
}
?>