<?php
require_once '../includes/config/config.php';
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

$conn = getTenantConnection();
$usuario_id = $_SESSION['usuario_id'];

$id        = $_POST['id'] ? (int)$_POST['id'] : null;
$titulo    = trim($_POST['titulo']);
$descricao = trim($_POST['descricao']);
$data      = $_POST['data'];
$hora      = $_POST['hora'];
$cor       = $_POST['cor'];
$visibilidade = $_POST['tipo_visibilidade'];
// Salva todos os emails digitados (ex: "a@a.com, b@b.com")
$email_notif  = trim($_POST['email_notificacao']); 
if(empty($email_notif)) $email_notif = null;

try {
    if ($id) {
        // Atualizar
        $sql = "UPDATE lembretes 
                SET titulo=?, descricao=?, data_lembrete=?, hora_lembrete=?, cor=?, tipo_visibilidade=?, email_notificacao=?
                WHERE id=? AND usuario_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssii", $titulo, $descricao, $data, $hora, $cor, $visibilidade, $email_notif, $id, $usuario_id);
    } else {
        // Inserir
        $sql = "INSERT INTO lembretes (usuario_id, titulo, descricao, data_lembrete, hora_lembrete, cor, tipo_visibilidade, email_notificacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", $usuario_id, $titulo, $descricao, $data, $hora, $cor, $visibilidade, $email_notif);
    }
    
    $stmt->execute();
    $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show'>Lembrete salvo com sucesso! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";

} catch (Exception $e) {
    $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
}

header('Location: ../pages/lembrete.php');
?>