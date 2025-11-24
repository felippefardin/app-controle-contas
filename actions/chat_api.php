<?php
include '../includes/session_init.php';
include '../database.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$userId = $_SESSION['usuario_id'] ?? 0;

// Verificação simples de admin
$isAdmin = (
    isset($_SESSION['super_admin']) ||
    (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')
);

$conn = getMasterConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'msg' => 'Erro de conexão ao banco']);
    exit;
}

function json_error($msg) {
    echo json_encode(['status' => 'error', 'msg' => $msg]);
    exit;
}

// --- INICIAR SUPORTE ---
if ($action === 'iniciar_suporte') {
    if (!$isAdmin) json_error('Acesso negado');

    $targetUserId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    if ($targetUserId <= 0) json_error('Usuário alvo inválido');

    $currentAdminId = $_SESSION['usuario_id'] ?? 1;
    
    // Gera protocolo já na criação
    $protocolo = date('Ymd') . rand(1000, 9999);

    $stmt = $conn->prepare("INSERT INTO chat_sessions (usuario_id, admin_id, status, data_criacao, protocolo) VALUES (?, ?, 'pending', NOW(), ?)");
    $stmt->bind_param("iis", $targetUserId, $currentAdminId, $protocolo);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'chat_id' => $conn->insert_id, 'protocolo' => $protocolo]);
    } else {
        json_error('Erro DB: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

// --- ACEITAR CONVITE ---
if ($action === 'aceitar_convite') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    
    // Configura horários
    $now = new DateTime();
    $inicioStr = $now->format('Y-m-d H:i:s');
    $now->modify('+1 hour');
    $expiracaoStr = $now->format('Y-m-d H:i:s');
    $now->modify('+5 minutes');
    $limiteStr = $now->format('Y-m-d H:i:s');

    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'active', data_inicio = ?, data_expiracao = ?, data_limite_final = ? WHERE id = ?");
    $stmt->bind_param("sssi", $inicioStr, $expiracaoStr, $limiteStr, $chatId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        json_error('Erro DB: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

// --- ENVIAR MENSAGEM ---
if ($action === 'enviar_mensagem') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    $msg = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    
    if ($chatId <= 0 || $msg === '') json_error('Parâmetros inválidos');

    $msgEsc = strip_tags($msg);
    $sender = $isAdmin ? 'admin' : 'user';

    $stmt = $conn->prepare("INSERT INTO chat_messages (chat_session_id, sender_type, mensagem, data_envio) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $chatId, $sender, $msgEsc);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        json_error('Erro DB execute: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

// --- ENVIAR ARQUIVO ---
if ($action === 'enviar_arquivo') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    
    if ($chatId <= 0 || !isset($_FILES['arquivo'])) {
        json_error('Dados inválidos.');
    }

    $file = $_FILES['arquivo'];
    if ($file['error'] !== UPLOAD_ERR_OK) json_error('Erro no upload.');

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) json_error('Tipo de arquivo não permitido.');

    $uploadDir = '../assets/uploads/chat_files/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newInfo = uniqid('chat_' . $chatId . '_') . '.' . $ext;
    $dest = $uploadDir . $newInfo;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $payload = json_encode([
            '_is_file' => true,
            'url' => $dest,
            'name' => $file['name'],
            'filetype' => $mime
        ]);
        
        $sender = $isAdmin ? 'admin' : 'user';
        $stmt = $conn->prepare("INSERT INTO chat_messages (chat_session_id, sender_type, mensagem, data_envio) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $chatId, $sender, $payload);
        
        if($stmt->execute()) {
            echo json_encode(['status'=>'success', 'file'=>$dest, 'name'=>$file['name'], 'type'=>$mime]);
        } else {
            json_error('Erro ao salvar mensagem de arquivo.');
        }
    } else {
        json_error('Falha ao mover arquivo.');
    }
    exit;
}

// --- GET STATUS (POLLING) ---
if ($action === 'get_status') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    $lastId = isset($_POST['last_msg_id']) ? (int)$_POST['last_msg_id'] : 0;

    $stmt = $conn->prepare("SELECT status, data_inicio, data_expiracao, data_limite_final, protocolo FROM chat_sessions WHERE id = ?");
    $stmt->bind_param("i", $chatId);
    $stmt->execute();
    $res = $stmt->get_result();
    $chat = $res->fetch_assoc();
    $stmt->close();

    if (!$chat) json_error('Chat não encontrado.');

    // Lógica de tempo e estado
    $now = time();
    $start = strtotime($chat['data_inicio'] ?? 'now');
    $expire = strtotime($chat['data_expiracao'] ?? 'now');
    $limit = strtotime($chat['data_limite_final'] ?? 'now');
    
    $state = $chat['status'];
    $timeLeft = 0;

    if ($state === 'active') {
        if ($now < $start) $state = 'waiting_start';
        elseif ($now >= $start && $now < $expire) {
            $timeLeft = $expire - $now;
        } elseif ($now >= $expire && $now < $limit) {
            $timeLeft = $limit - $now;
            $state = 'grace';
            $conn->query("UPDATE chat_sessions SET status = 'grace' WHERE id = $chatId");
        } else {
            $state = 'timeout';
        }
    } elseif ($state === 'grace') {
        if ($now < $limit) $timeLeft = $limit - $now;
        else $state = 'timeout';
    }

    // Mensagens novas
    $stmtMsg = $conn->prepare("SELECT id, sender_type, mensagem, data_envio FROM chat_messages WHERE chat_session_id = ? AND id > ? ORDER BY id ASC");
    $stmtMsg->bind_param("ii", $chatId, $lastId);
    $stmtMsg->execute();
    $resultMsg = $stmtMsg->get_result();
    $messages = [];
    while ($row = $resultMsg->fetch_assoc()) $messages[] = $row;
    $stmtMsg->close();

    echo json_encode([
        'status' => 'success',
        'chat_state' => $state,
        'time_left' => $timeLeft,
        'messages' => $messages,
        'protocolo' => $chat['protocolo']
    ]);
    exit;
}

// --- ENCERRAR CHAT ---
if ($action === 'encerrar_chat') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    
    // Busca protocolo existente ou gera novo se vazio
    $stmtGet = $conn->prepare("SELECT protocolo FROM chat_sessions WHERE id = ?");
    $stmtGet->bind_param("i", $chatId);
    $stmtGet->execute();
    $res = $stmtGet->get_result();
    $row = $res->fetch_assoc();
    $stmtGet->close();

    $protocolo = $row['protocolo'] ?? '';
    if (empty($protocolo)) {
        $protocolo = date('Ymd') . rand(1000, 9999);
    }

    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = NOW(), protocolo = ? WHERE id = ?");
    $stmt->bind_param("si", $protocolo, $chatId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'protocolo' => $protocolo]);
    } else {
        json_error('Erro ao encerrar.');
    }
    $stmt->close();
    exit;
}

json_error('Ação inválida.');
?>