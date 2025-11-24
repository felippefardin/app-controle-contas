<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Inclui o header se existir
if(file_exists('../includes/header.php')) {
    include '../includes/header.php';
}

// Conexão Master
$conn = getMasterConnection();

$chatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;

// Verifica se é Admin
$isAdmin = isset($_SESSION['super_admin']);
$userId = $_SESSION['usuario_id'] ?? 0;
$tenantId = $_SESSION['tenant_id'] ?? 0;

if (!$isAdmin && $userId == 0) {
    header("Location: login.php");
    exit;
}

// 1. Busca o Chat pelo ID (sem filtrar por usuário ainda)
$stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ?");
$stmt->bind_param("i", $chatId);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();

// 2. Verificações de Acesso
$acessoPermitido = false;

if ($chat) {
    if ($isAdmin) {
        // Admin sempre pode acessar
        $acessoPermitido = true;
    } else {
        // Usuário Comum: Precisa verificar propriedade
        
        // Verifica se o ID do chat bate com o ID da sessão atual
        if ($chat['usuario_id'] == $userId) {
            $acessoPermitido = true;
        } 
        // Verifica se o ID do chat bate com o ID Master do Tenant (Correção para conflito de IDs)
        elseif ($tenantId > 0) {
            $stmtT = $conn->prepare("SELECT usuario_id FROM tenants WHERE id = ?");
            $stmtT->bind_param("i", $tenantId);
            $stmtT->execute();
            $resT = $stmtT->get_result();
            $tenantData = $resT->fetch_assoc();
            
            if ($tenantData && $chat['usuario_id'] == $tenantData['usuario_id']) {
                $acessoPermitido = true;
            }
            $stmtT->close();
        }
    }
}

// 3. Valida se pode exibir a tela
if (!$chat || !$acessoPermitido) {
    echo "<script>alert('Chat não disponível ou acesso negado.'); window.location.href='home.php';</script>";
    exit;
}

if ($chat['status'] == 'pending' && !$isAdmin) {
    echo "<script>alert('Aguardando início do suporte pelo administrador.'); window.location.href='home.php';</script>";
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

        const chatBox = document.getElementById('chatBox');
        const myType = '<?php echo $isAdmin ? 'admin' : 'user'; ?>';

        if(data.messages && data.messages.length > 0) {
            let scroll = false;
            // Verifica se o usuário já estava no final antes de adicionar
            if (chatBox.scrollHeight - chatBox.scrollTop === chatBox.clientHeight) {
                scroll = true;
            }

            data.messages.forEach(msg => {
                const div = document.createElement('div');
                const isMe = (msg.sender_type === myType);
                
                div.style.textAlign = isMe ? 'right' : 'left';
                div.style.margin = '10px';
                
                const bg = isMe ? '#dcf8c6' : '#e2e2e2';
                
                div.innerHTML = `
                    <span style="background: ${bg}; padding: 8px 15px; border-radius: 15px; display: inline-block; border: 1px solid #ccc; text-align: left; color:#333;">
                        ${msg.mensagem}
                        <br><small style="font-size:0.7em; color:#666;">${msg.data_envio}</small>
                    </span>`;
                
                chatBox.appendChild(div);
                lastMsgId = msg.id;
                scroll = true; // Força scroll para nova mensagem
            });
            
            if(scroll) chatBox.scrollTop = chatBox.scrollHeight;
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

document.getElementById('msgInput').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') enviarMensagem();
});

setInterval(atualizarStatus, 3000);
atualizarStatus();
</script>

<?php 
if(file_exists('../includes/footer.php')) {
    include '../includes/footer.php'; 
}
?>