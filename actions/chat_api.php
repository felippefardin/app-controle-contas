<?php
include '../includes/session_init.php';
include '../database.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$userId = $_SESSION['usuario_id'] ?? 0;

// Verificação simples de admin (padronizada)
$isAdmin = (
    isset($_SESSION['super_admin']) ||
    (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')
);

// Conexão Master (central)
$conn = getMasterConnection();
if (!$conn) {
    echo json_encode(['status' => 'error', 'msg' => 'Erro de conexão ao banco']);
    exit;
}

// Função utilitaria para saída de erro
function json_error($msg) {
    echo json_encode(['status' => 'error', 'msg' => $msg]);
    exit;
}

if ($action === 'iniciar_suporte') {
    if (!$isAdmin) json_error('Acesso negado');

    $targetUserId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    if ($targetUserId <= 0) json_error('Usuário alvo inválido');

    // Determina admin atual (tenta pegar id de estrutura de sessão comum)
    $currentAdminId = 0;
    if (isset($_SESSION['super_admin']['id'])) {
        $currentAdminId = (int)$_SESSION['super_admin']['id'];
    } elseif (isset($_SESSION['usuario_id'])) {
        $currentAdminId = (int)$_SESSION['usuario_id'];
    } else {
        $currentAdminId = 1;
    }

    $stmt = $conn->prepare("INSERT INTO chat_sessions (usuario_id, admin_id, status, data_criacao) VALUES (?, ?, 'pending', NOW())");
    if (!$stmt) json_error('Erro DB prepare: ' . $conn->error);
    $stmt->bind_param("ii", $targetUserId, $currentAdminId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'chat_id' => $conn->insert_id]);
    } else {
        json_error('Erro DB execute: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

if ($action === 'aceitar_convite') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    if ($chatId <= 0) json_error('chat_id inválido');

    // Verifica se o chat existe
    $stmtCheck = $conn->prepare("SELECT id, status FROM chat_sessions WHERE id = ?");
    if (!$stmtCheck) json_error('Erro DB prepare: ' . $conn->error);
    $stmtCheck->bind_param("i", $chatId);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $chatData = $resCheck->fetch_assoc();
    $stmtCheck->close();

    if (!$chatData) json_error('Chat não encontrado.');

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

    // Atualiza para ATIVO
    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'active', data_inicio = ?, data_expiracao = ?, data_limite_final = ? WHERE id = ?");
    if (!$stmt) json_error('Erro DB prepare: ' . $conn->error);
    $stmt->bind_param("sssi", $inicioStr, $expiracaoStr, $limiteStr, $chatId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        json_error('Erro DB: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

if ($action === 'enviar_mensagem') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    $msg = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    if ($chatId <= 0 || $msg === '') json_error('Parâmetros inválidos');

    // Sanitização básica (remoção de tags)
    $msgEsc = strip_tags($msg);

    $sender = $isAdmin ? 'admin' : 'user';

    $stmt = $conn->prepare("INSERT INTO chat_messages (chat_session_id, sender_type, mensagem, data_envio) VALUES (?, ?, ?, NOW())");
    if (!$stmt) json_error('Erro DB prepare: ' . $conn->error);
    $stmt->bind_param("iss", $chatId, $sender, $msgEsc);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        json_error('Erro DB execute: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

if ($action === 'get_status') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    $lastId = isset($_POST['last_msg_id']) ? (int)$_POST['last_msg_id'] : 0;
    if ($chatId <= 0) json_error('chat_id inválido');

    $stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ?");
    if (!$stmt) json_error('Erro DB prepare: ' . $conn->error);
    $stmt->bind_param("i", $chatId);
    $stmt->execute();
    $res = $stmt->get_result();
    $chat = $res->fetch_assoc();
    $stmt->close();

    if (!$chat) json_error('Chat não encontrado.');

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
            // Marca o estado grace com prepared statement (se ainda não foi setado)
            if ($chat['status'] !== 'grace') {
                $stmtG = $conn->prepare("UPDATE chat_sessions SET status = 'grace' WHERE id = ?");
                if ($stmtG) {
                    $stmtG->bind_param("i", $chatId);
                    $stmtG->execute();
                    $stmtG->close();
                }
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

    // Busca mensagens novas
    $stmtMsg = $conn->prepare("SELECT id, chat_session_id, sender_type, mensagem, data_envio FROM chat_messages WHERE chat_session_id = ? AND id > ? ORDER BY id ASC");
    if (!$stmtMsg) json_error('Erro DB prepare mensagens: ' . $conn->error);
    $stmtMsg->bind_param("ii", $chatId, $lastId);
    $stmtMsg->execute();
    $resultMsg = $stmtMsg->get_result();
    $messages = [];
    while ($row = $resultMsg->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmtMsg->close();

    echo json_encode([
        'status' => 'success',
        'chat_state' => $state,
        'time_left' => $timeLeft,
        'messages' => $messages,
        'protocolo' => $chat['protocolo'] ?? null
    ]);
    exit;
}

if ($action === 'encerrar_chat') {
    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    if ($chatId <= 0) json_error('chat_id inválido');

    $protocolo = date('Ymd') . rand(1000, 9999);

    $stmt = $conn->prepare("UPDATE chat_sessions SET status = 'closed', closed_at = NOW(), protocolo = ? WHERE id = ?");
    if (!$stmt) json_error('Erro DB prepare: ' . $conn->error);
    $stmt->bind_param("si", $protocolo, $chatId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'protocolo' => $protocolo]);
    } else {
        json_error('Erro DB execute: ' . $stmt->error);
    }
    $stmt->close();
    exit;
}

// Ação desconhecida
json_error('Ação inválida ou ausente.');
