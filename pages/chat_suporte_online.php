<?php
require_once '../includes/session_init.php';
require_once '../database.php';
// Tenta incluir o header correto dependendo se é admin ou usuário comum
if(file_exists('../includes/header.php')) {
    include '../includes/header.php';
}

// ✅ Conexão via MySQLi
$conn = getMasterConnection();

$chatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;

// ✅ Correção da lógica de Sessão (Admin vs Usuário)
$isAdmin = isset($_SESSION['super_admin']);
$userId = 0;

if ($isAdmin) {
    $adminData = $_SESSION['super_admin'];
    $userId = $adminData['id'] ?? 1; // Fallback para 1 se não houver ID na sessão do admin
} elseif (isset($_SESSION['usuario_id'])) {
    $userId = $_SESSION['usuario_id']; // ✅ Nome correto da variável de sessão do usuário
} else {
    // Se não estiver logado, redireciona
    header("Location: login.php");
    exit;
}

// ✅ Verificação de Permissão (MySQLi)
if ($isAdmin) {
    // Admin pode ver qualquer chat (pode filtrar por admin_id se necessário)
    $stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ?");
    $stmt->bind_param("i", $chatId);
} else {
    // Usuário só pode ver seu próprio chat
    $stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $chatId, $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();

// Se não achou o chat ou status pendente (e não for admin)
if (!$chat || ($chat['status'] == 'pending' && !$isAdmin)) {
    echo "<script>alert('Chat não disponível ou não encontrado.'); window.location.href='home.php';</script>";
    exit;
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
            <h5 class="mb-0">Chat Suporte Online</h5>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span id="timerDisplay" class="badge bg-warning text-dark" style="font-size: 1.1em; padding: 8px;">Carregando...</span>
                <button class="btn btn-danger btn-sm" onclick="encerrarChat()">Encerrar</button>
            </div>
        </div>
        <div class="card-body" id="chatBox" style="height: 400px; overflow-y: scroll; background: #f9f9f9; border-bottom: 1px solid #ddd;">
            </div>
        <div class="card-footer">
            <div class="input-group">
                <input type="text" id="msgInput" class="form-control" placeholder="Digite sua mensagem..." autocomplete="off">
                <button class="btn btn-primary" onclick="enviarMensagem()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<div id="modalProtocolo" class="modal" tabindex="-1" style="display:none; background: rgba(0,0,0,0.8); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered" style="margin-top: 10%;">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Atendimento Finalizado</h5>
            </div>
            <div class="modal-body text-center">
                <h4>Protocolo de Atendimento:</h4>
                <h2 class="text-primary" id="protocoloNum"></h2>
                <p>Anote este número. A conversa foi arquivada.</p>
            </div>
            <div class="modal-footer">
                <a href="<?php echo $isAdmin ? 'admin/dashboard.php' : 'home.php'; ?>" class="btn btn-primary">Voltar</a>
            </div>
        </div>
    </div>
</div>

<script>
const chatId = <?php echo $chatId; ?>;
let lastMsgId = 0;
let chatActive = true;

function formatTime(seconds) {
    if (seconds < 0) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

function atualizarStatus() {
    if (!chatActive) return;

    const formData = new FormData();
    formData.append('action', 'get_status');
    formData.append('chat_id', chatId);
    formData.append('last_msg_id', lastMsgId);

    fetch('../actions/chat_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status !== 'success') return;

        // Atualiza Timer e Estado
        const timerDisplay = document.getElementById('timerDisplay');
        if (data.chat_state === 'waiting_start') {
            timerDisplay.textContent = "Iniciando em: " + formatTime(data.time_left);
            timerDisplay.className = "badge bg-info text-dark";
        } else if (data.chat_state === 'active') {
            timerDisplay.textContent = "Tempo restante: " + formatTime(data.time_left);
            timerDisplay.className = "badge bg-success";
        } else if (data.chat_state === 'grace') {
            timerDisplay.textContent = "FINALIZANDO EM: " + formatTime(data.time_left);
            timerDisplay.className = "badge bg-danger blink";
        } else if (data.chat_state === 'timeout' || data.chat_state === 'closed') {
            chatActive = false;
            timerDisplay.textContent = "Encerrado";
            if (data.chat_state === 'timeout') encerrarChat();
            else mostrarProtocolo(data.protocolo);
        }

        // Atualiza Mensagens
        const chatBox = document.getElementById('chatBox');
        // Identifica quem sou eu no JS para alinhar mensagens
        const myType = '<?php echo $isAdmin ? 'admin' : 'user'; ?>';

        if(data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                const div = document.createElement('div');
                const isMe = (msg.sender_type === myType);
                
                div.style.textAlign = isMe ? 'right' : 'left';
                div.style.margin = '10px';
                
                const bg = isMe ? '#dcf8c6' : '#fff';
                
                div.innerHTML = `
                    <span style="background: ${bg}; padding: 8px 15px; border-radius: 15px; display: inline-block; border: 1px solid #ddd; text-align: left;">
                        ${msg.mensagem}
                        <br><small style="font-size:0.7em; color:#888;">${msg.data_envio}</small>
                    </span>`;
                
                chatBox.appendChild(div);
                lastMsgId = msg.id;
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    })
    .catch(err => console.error("Erro no polling:", err));
}

function enviarMensagem() {
    const input = document.getElementById('msgInput');
    const msg = input.value;
    if (!msg.trim()) return;

    const formData = new FormData();
    formData.append('action', 'enviar_mensagem');
    formData.append('chat_id', chatId);
    formData.append('mensagem', msg);

    fetch('../actions/chat_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            input.value = '';
            atualizarStatus();
        }
    });
}

function encerrarChat() {
    if(!confirm("Deseja realmente encerrar o atendimento?")) return;
    
    const formData = new FormData();
    formData.append('action', 'encerrar_chat');
    formData.append('chat_id', chatId);

    fetch('../actions/chat_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            mostrarProtocolo(data.protocolo);
        }
    });
}

function mostrarProtocolo(protocolo) {
    chatActive = false;
    document.getElementById('protocoloNum').innerText = protocolo;
    document.getElementById('modalProtocolo').style.display = 'block';
    document.getElementById('msgInput').disabled = true;
}

// Enviar com Enter
document.getElementById('msgInput').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') enviarMensagem();
});

// Inicia o loop de atualização
setInterval(atualizarStatus, 3000);
atualizarStatus(); // Primeira chamada imediata
</script>

<?php 
if(file_exists('../includes/footer.php')) {
    include '../includes/footer.php'; 
}
?>