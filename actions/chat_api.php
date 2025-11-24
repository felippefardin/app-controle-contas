<?php
include '../includes/session_init.php';
include '../database.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$userId = $_SESSION['usuario_id'] ?? 0; 

// Verificação simples de admin
$isAdmin = (isset($_SESSION['super_admin']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'));

$conn = getMasterConnection(); 

if ($action === 'iniciar_suporte') {
    if (!$isAdmin) { echo json_encode(['status' => 'error', 'msg' => 'Acesso negado']); exit; }
    
    $targetUserId = $_POST['target_user_id'];
    $currentAdminId = isset($_SESSION['super_admin']['id']) ? $_SESSION['super_admin']['id'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1); 
    
    $stmt = $conn->prepare("INSERT INTO chat_sessions (usuario_id, admin_id, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $targetUserId, $currentAdminId);
    
    if($stmt->execute()) {
        echo json_encode(['status' => 'success', 'chat_id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => $conn->error]);
    }
    exit;
}

if ($action === 'aceitar_convite') {
    $chatId = (int)$_POST['chat_id'];
    
    // 1. Verifica se o chat existe
    $stmtCheck = $conn->prepare("SELECT id, status FROM chat_sessions WHERE id = ?");
    $stmtCheck->bind_param("i", $chatId);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $chatData = $resCheck->fetch_assoc();
    $stmtCheck->close();

    if (!$chatData) {
        echo json_encode(['status' => 'error', 'msg' => 'Chat não encontrado.']);
        exit;
    }

    // Se já estiver ativo, apenas sucesso
    if ($chatData['status'] === 'active') {
        echo json_encode(['status' => 'success', 'msg' => 'Chat retomado.']);
        exit;
    }

    // 2. Configura horários
    $now = new DateTime();
    $inicio = clone $now; 
    $inicio->modify('+0 minutes'); // Inicia agora
    $expiracao = clone $inicio;
    $expiracao->modify('+1 hour');
    $limite = clone $expiracao;
    $limite->modify('+5 minutes');

    $inicioStr = $inicio->format('Y-m-d H:i:s');
    $expiracaoStr = $expiracao->format('Y-m-d H:i:s');
    $limiteStr = $limite->format('Y-m-d H:i:s');

    // 3. Atualiza para ATIVO (Sem WHERE usuario_id para evitar erro de ID cruzado)
    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'active', data_inicio = ?, data_expiracao = ?, data_limite_final = ? WHERE id = ?");
    $stmt->bind_param("sssi", $inicioStr, $expiracaoStr, $limiteStr, $chatId);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Erro DB: ' . $conn->error]);
    }
    exit;
}

if ($action === 'enviar_mensagem') {
    $chatId = $_POST['chat_id'];
    $msg = strip_tags($_POST['mensagem']);
    $sender = $isAdmin ? 'admin' : 'user';
    
    $stmt = $conn->prepare("INSERT INTO chat_messages (chat_session_id, sender_type, mensagem) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $chatId, $sender, $msg);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'get_status') {
    $chatId = $_POST['chat_id'];
    
    $stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ?");
    $stmt->bind_param("i", $chatId);
    $stmt->execute();
    $res = $stmt->get_result();
    $chat = $res->fetch_assoc();
    
    if (!$chat) { echo json_encode(['status' => 'error']); exit; }

    $now = time();
    $start = strtotime($chat['data_inicio'] ?? 'now');
    $expire = strtotime($chat['data_expiracao'] ?? 'now');
    $limit = strtotime($chat['data_limite_final'] ?? 'now');
    
    $state = $chat['status'];
    $timeLeft = 0;

    if ($chat['status'] === 'active') {
        if ($now < $start) {
            $timeLeft = $start - $now;
            $state = 'waiting_start';
        } elseif ($now >= $start && $now < $expire) {
            $timeLeft = $expire - $now;
            $state = 'active';
        } elseif ($now >= $expire && $now < $limit) {
            $timeLeft = $limit - $now;
            $state = 'grace';
            if ($chat['status'] !== 'grace') {
                $conn->query("UPDATE chat_sessions SET status = 'grace' WHERE id = $chatId");
            }
        } else {
            $state = 'timeout';
        }
    } elseif ($chat['status'] === 'grace') {
         if ($now < $limit) {
            $timeLeft = $limit - $now;
         } else {
            $state = 'timeout';
         }
    }

    $lastId = $_POST['last_msg_id'] ?? 0;
    $stmtMsg = $conn->prepare("SELECT * FROM chat_messages WHERE chat_session_id = ? AND id > ? ORDER BY id ASC");
    $stmtMsg->bind_param("ii", $chatId, $lastId);
    $stmtMsg->execute();
    $resultMsg = $stmtMsg->get_result();
    $messages = [];
    while($row = $resultMsg->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'chat_state' => $state,
        'time_left' => $timeLeft,
        'messages' => $messages,
        'protocolo' => $chat['protocolo']
    ]);
    exit;
}

if ($action === 'encerrar_chat') {
    $chatId = $_POST['chat_id'];
    $protocolo = date('Ymd') . rand(1000, 9999);
    
    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = NOW(), protocolo = ? WHERE id = ?");
    $stmt->bind_param("si", $protocolo, $chatId);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'protocolo' => $protocolo]);
    exit;
}
?>