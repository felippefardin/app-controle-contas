<?php
require_once '../includes/config/config.php';
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

// ID do usuário logado (correto)
$usuario_id = $_SESSION['usuario_id'];

// Conexão do tenant
$conn = getTenantConnection();
if (!$conn) {
    die("Erro ao conectar ao banco do tenant.");
}

// Recebendo dados do formulário
$id        = isset($_POST['id']) ? (int)$_POST['id'] : null;
$titulo    = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
$data      = isset($_POST['data']) ? $_POST['data'] : '';
$hora      = isset($_POST['hora']) ? $_POST['hora'] : '';
$cor       = isset($_POST['cor']) ? $_POST['cor'] : 'verde';

// Evita títulos vazios
if (empty($titulo)) {
    $_SESSION['msg'] = "<div class='alert alert-danger'>O título é obrigatório.</div>";
    header('Location: ../pages/lembrete.php');
    exit;
}

try {

    if ($id) {
        // ===============================================
        // 📝 ATUALIZAR LEMBRETE EXISTENTE
        // ===============================================
        $sql = "UPDATE lembretes 
                SET titulo=?, descricao=?, data_lembrete=?, hora_lembrete=?, cor=? 
                WHERE id=? AND usuario_id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi",
            $titulo,
            $descricao,
            $data,
            $hora,
            $cor,
            $id,
            $usuario_id
        );

        $stmt->execute();
        $stmt->close();

        $_SESSION['msg'] = "<div class='alert alert-success'>Lembrete atualizado!</div>";
    } 
    else {

        // ===============================================
        // ➕ CRIAR NOVO LEMBRETE
        // ===============================================
        $sql = "INSERT INTO lembretes 
                (usuario_id, titulo, descricao, data_lembrete, hora_lembrete, cor)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss",
            $usuario_id,
            $titulo,
            $descricao,
            $data,
            $hora,
            $cor
        );

        $stmt->execute();
        $stmt->close();

        $_SESSION['msg'] = "<div class='alert alert-success'>Lembrete criado com sucesso!</div>";
    }

} catch (Exception $e) {
    $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao salvar: " . $e->getMessage() . "</div>";
}

header('Location: ../pages/lembrete.php');
exit;
