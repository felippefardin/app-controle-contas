<?php 
require_once '../includes/session_init.php';
require_once '../database.php';

// Conexão Master
$conn = getMasterConnection();

// Recebe chat_id via GET e valida
$chatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
if ($chatId <= 0) {
    header("Location: home.php");
    exit;
}

// Verificação consistente de admin (mesma lógica usada em chat_api.php)
$isAdmin = (
    isset($_SESSION['super_admin']) ||
    (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')
);

$userId = $_SESSION['usuario_id'] ?? 0;
$tenantId = $_SESSION['tenant_id'] ?? 0;

// Se não for admin e não estiver logado, redireciona
if (!$isAdmin && $userId == 0) {
    header("Location: login.php");
    exit;
}

// 1. Busca o Chat pelo ID (prepared statement)
$stmt = $conn->prepare("SELECT * FROM chat_sessions WHERE id = ?");
if (!$stmt) {
    // erro grave no DB
    echo "<script>alert('Erro ao preparar consulta.'); window.location.href='home.php';</script>";
    exit;
}
$stmt->bind_param("i", $chatId);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();

// 2. Verificações de Acesso
$acessoPermitido = false;

if ($chat) {
    // Admin sempre pode acessar
    if ($isAdmin) {
        $acessoPermitido = true;
    } else {
        // Usuário Comum: precisa ser dono do chat
        if (isset($chat['usuario_id']) && (int)$chat['usuario_id'] === (int)$userId) {
            $acessoPermitido = true;
        } else {
            // Caso multi-tenant (opcional)
            if ($tenantId > 0) {
                $stmtT = $conn->prepare("SELECT id, usuario_id, tenant_id FROM tenants WHERE id = ? OR tenant_id = ? LIMIT 1");
                if ($stmtT) {
                    $stmtT->bind_param("ii", $tenantId, $tenantId);
                    $stmtT->execute();
                    $resT = $stmtT->get_result();
                    $tenantData = $resT->fetch_assoc();
                    $stmtT->close();

                    if ($tenantData && isset($tenantData['usuario_id']) && (int)$chat['usuario_id'] === (int)$tenantData['usuario_id']) {
                        $acessoPermitido = true;
                    }
                }
            }
        }
    }
}

// 3. Valida se pode exibir a tela (Antes do HTML para evitar renderização parcial)
if (!$chat || !$acessoPermitido) {
    echo "<script>alert('Chat não disponível ou acesso negado.'); window.location.href='home.php';</script>";
    exit;
}

// Inclui o header APÓS as validações de redirecionamento
if (file_exists('../includes/header.php')) {
    include '../includes/header.php';
}

// Segurança: define tipo do usuário para o JS
$jsUserType = $isAdmin ? 'admin' : 'user';
$jsUserId = (int)($userId ?: 0);
$jsChatId = (int)$chatId;
?>

<!-- ======== ESTILOS E SONS ======== -->
<style>
/* Container geral */
.container.mt-4 { max-width: 900px; margin: 28px auto; }

/* Card */
.card { border-radius: 10px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }

/* Header */
.card-header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background: linear-gradient(90deg,#0d6efd,#0069d9); color:#fff; }

/* Timer */
#timerDisplay { font-weight:700; padding:6px 10px; border-radius:8px; }

/* Chat box */
#chatBox {
    background: #e9eef3;
    border: 1px solid #d7e0e9;
    border-radius: 10px;
    padding: 16px;
    height: 400px;
    overflow-y: auto;
    scroll-behavior: smooth;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Mensagens estilo bolha (WhatsApp-like) */
.msg {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 18px;
    line-height: 1.4;
    font-size: 15px;
    max-width: 75%;
    word-wrap: break-word;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    opacity: 0;
    transform: translateY(8px);
    animation: msgIn 220ms ease forwards;
}

@keyframes msgIn {
    to { opacity: 1; transform: translateY(0); }
}

/* Usuário (direita) */
.msg-user {
    background: linear-gradient(180deg,#dbf6d6,#c8efbe);
    border: 1px solid #b7df9e;
    color: #083b05;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}

/* Admin (esquerda) */
.msg-admin {
    background: #fff;
    border: 1px solid #dee2e6;
    color: #222;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}

/* System (centro, ex: protocolo/aviso) */
.msg-system {
    background: transparent;
    color: #555;
    font-size: 0.9rem;
    text-align: center;
    align-self: center;
}

/* Time label */
.msg-time { display:block; font-size: 11px; color:#666; margin-top:6px; opacity:0.8; }

/* Area de input */
.card-footer { padding: 14px; background: #fafafa; border-top: 1px solid #eee; }
.input-group { display:flex; gap:10px; }
.input-control {
    flex:1;
    padding: 12px 16px;
    border-radius: 999px;
    border: 1px solid #d1d7dd;
    outline:none;
    font-size: 15px;
    background:#fff;
}
.btn-send {
    padding: 10px 18px;
    border-radius: 999px;
    background: linear-gradient(90deg,#0d6efd,#007bff);
    color:#fff;
    border:none;
    cursor:pointer;
    box-shadow: 0 6px 16px rgba(13,110,253,0.18);
}

/* Upload button */
.btn-upload { background:transparent; border:1px dashed #cbd6e0; padding:8px 12px; border-radius:8px; cursor:pointer; }

/* typing */
#typingIndicator { font-style:italic; color:#555; margin-left:8px; }

/* small responsive */
@media (max-width:600px) {
    #chatBox { height: 60vh; }
    .msg { font-size: 14px; }
}
</style>

<!-- Sons embedados (data URIs) -->
<audio id="soundMsg" preload="auto">
    <source src="data:audio/ogg;base64,T2dnUwACAAAAAAAAAAA+..." type="audio/ogg">
    <!-- NOTE: Replace base64 chunk above with your real sound if desired. If not, browsers still use default beep. -->
</audio>
<audio id="soundAdmin" preload="auto">
    <source src="data:audio/ogg;base64,T2dnUwACAAAAAAAAAAA+..." type="audio/ogg">
</audio>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <div style="display:flex; align-items:center; gap:12px;">
                <h5 class="mb-0" style="margin:0; font-weight:700;">Chat Suporte Online</h5>
                <small id="chatLabel" style="color: rgba(255,255,255,0.9); opacity:0.95;">Protocolo: <strong><?php echo htmlspecialchars($chat['protocolo'] ?? '—'); ?></strong></small>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span id="timerDisplay" class="badge bg-warning text-dark">Carregando...</span>
                <button class="btn btn-danger btn-sm" onclick="encerrarChat()">Encerrar</button>
            </div>
        </div>

        <div class="card-body" id="chatBox">
            <!-- mensagens serão injetadas aqui -->
        </div>

        <div class="card-footer">
            <div style="display:flex; align-items:center; gap:10px;">
                <label class="btn-upload" title="Anexar arquivo">
                    <input id="fileInput" type="file" style="display:none" />
                    <i class="fas fa-paperclip"></i> Anexar
                </label>

                <div style="flex:1; display:flex; align-items:center;">
                    <input id="msgInput" class="input-control" placeholder="Digite sua mensagem..." autocomplete="off" />
                    <span id="typingIndicator" style="display:none">Digitando...</span>
                </div>
                <button class="btn-send" onclick="enviarMensagem()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal protocolo -->
<div id="modalProtocolo" class="modal" tabindex="-1" style="display:none; background: rgba(0,0,0,0.6); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;">
    <div style="width:100%; display:flex; justify-content:center; align-items:center; height:100%;">
        <div style="background:#fff; padding:24px; border-radius:10px; max-width:420px; text-align:center;">
            <h4>Atendimento Finalizado</h4>
            <p>Protocolo de Atendimento:</p>
            <h2 id="protocoloNum" style="color:#0d6efd;"></h2>
            <a id="backLink" href="#" class="btn btn-primary" style="margin-top:12px;">Voltar</a>
        </div>
    </div>
</div>

<!-- ======== SCRIPT ======== -->
<script>
const chatId = <?php echo json_encode($jsChatId); ?>;
const myType = <?php echo json_encode($jsUserType); ?>;
const myUserId = <?php echo json_encode($jsUserId); ?>;

let lastMsgId = 0;
let chatActive = true;
let typingTimeout = null;
let typingSendThrottle = 0;

// Elements
const chatBox = document.getElementById('chatBox');
const msgInput = document.getElementById('msgInput');
const fileInput = document.getElementById('fileInput');
const typingIndicator = document.getElementById('typingIndicator');
const soundMsg = document.getElementById('soundMsg');
const soundAdmin = document.getElementById('soundAdmin');

// Helper: escape HTML
function escapeHtml(s) {
    return (s||'').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// Render message (handles file JSON or plain)
function renderMessage(msg) {
    // msg: {id, sender_type, mensagem, data_envio}
    const wrapper = document.createElement('div');

    // Determine type
    let sender = msg.sender_type || 'user';
    let isMe = (sender === myType);
    let classList = 'msg ';
    if (sender === 'user' || sender === 'user_file') classList += 'msg-user';
    else if (sender === 'admin' || sender === 'admin_file') classList += 'msg-admin';
    else classList += 'msg-system';

    wrapper.className = classList;

    // detect if mensagem is JSON (file payload) or plain text
    let contentHTML = '';
    let timeLabel = '';
    try {
        if (msg.mensagem) {
            // try parse
            const parsed = JSON.parse(msg.mensagem);
            if (parsed && parsed._is_file) {
                // file object
                const fileUrl = parsed.url;
                const fileName = escapeHtml(parsed.name || '');
                const fileType = parsed.filetype || '';
                if (fileType.startsWith('image/')) {
                    contentHTML = `<div style="max-width:320px;"><a href="${escapeHtml(fileUrl)}" target="_blank"><img src="${escapeHtml(fileUrl)}" style="max-width:100%; border-radius:8px; display:block;" /></a></div>`;
                } else {
                    // pdf or other
                    contentHTML = `<div style="display:flex; align-items:center; gap:10px;"><i class="fas fa-file-pdf" style="font-size:20px;"></i> <a href="${escapeHtml(fileUrl)}" target="_blank">${fileName}</a></div>`;
                }
            } else {
                contentHTML = escapeHtml(msg.mensagem).replace(/\n/g,'<br>');
            }
        }
    } catch (e) {
        contentHTML = escapeHtml(msg.mensagem || '').replace(/\n/g,'<br>');
    }

    // time: use client's local time if provided by server as ISO or DB string
    if (msg.data_envio) {
        const d = new Date(msg.data_envio + ' UTC');
        // If server returns already adjusted timezone, this may be off; this tries to show readable local
        if (!isNaN(d.getTime())) {
            const hh = String(d.getHours()).padStart(2,'0');
            const mm = String(d.getMinutes()).padStart(2,'0');
            timeLabel = `${hh}:${mm}`;
        } else {
            timeLabel = msg.data_envio;
        }
    }

    wrapper.innerHTML = `
        <div>${contentHTML}</div>
        <span class="msg-time">${timeLabel}</span>
    `;

    return wrapper;
}

// Append message and play sound accordingly
function appendMessage(msg) {
    const isMe = (msg.sender_type === myType || (msg.sender_type === 'user' && myType === 'user'));
    const node = renderMessage(msg);

    chatBox.appendChild(node);
    chatBox.scrollTop = chatBox.scrollHeight;

    // play sound only if not me
    if (!isMe) {
        // If admin entered (we will detect via special system message 'admin_joined' in mensagem)
        try {
            const parsed = JSON.parse(msg.mensagem || '');
            if (parsed && parsed._system === 'admin_joined') {
                // admin join sound
                if (soundAdmin) try { soundAdmin.play(); } catch(e) {}
                // visual notify
                showToast('Atendente entrou no chat.');
                return;
            }
        } catch(e) {
            // ignore
        }
        if (soundMsg) try { soundMsg.play(); } catch(e) {}
    }
}

// Small toast notification
function showToast(text) {
    const t = document.createElement('div');
    t.textContent = text;
    t.style.position = 'fixed';
    t.style.right = '20px';
    t.style.top = '20px';
    t.style.background = '#0d6efd';
    t.style.color = '#fff';
    t.style.padding = '10px 14px';
    t.style.borderRadius = '8px';
    t.style.boxShadow = '0 8px 24px rgba(13,110,253,0.18)';
    t.style.zIndex = 99999;
    document.body.appendChild(t);
    setTimeout(()=>{ t.style.opacity = '0'; t.style.transform='translateY(-8px)'; }, 2500);
    setTimeout(()=> document.body.removeChild(t), 3300);
}

// Polling: get_status
function atualizarStatus() {
    if (!chatActive) return;

    const formData = new FormData();
    formData.append('action','get_status');
    formData.append('chat_id', chatId);
    formData.append('last_msg_id', lastMsgId);

    fetch('../actions/chat_api.php',{ method:'POST', body: formData })
    .then(r=>r.json())
    .then(data=>{
        if (!data || data.status !== 'success') return;

        // timer and state
        const timerDisplay = document.getElementById('timerDisplay');
        if (data.chat_state === 'waiting_start') {
            timerDisplay.textContent = "Iniciando em: " + formatTime(data.time_left);
            timerDisplay.className = "badge bg-info text-dark";
        } else if (data.chat_state === 'active') {
            timerDisplay.textContent = "Tempo restante: " + formatTime(data.time_left);
            timerDisplay.className = "badge bg-success";
        } else if (data.chat_state === 'grace') {
            timerDisplay.textContent = "FINALIZANDO EM: " + formatTime(data.time_left);
            timerDisplay.className = "badge bg-danger";
        } else if (data.chat_state === 'timeout' || data.chat_state === 'closed') {
            chatActive = false;
            timerDisplay.textContent = "Encerrado";
            if (data.chat_state === 'timeout') encerrarChat();
            else mostrarProtocolo(data.protocolo);
        }

        // typing indicator (server side)
        if (data.typing && Array.isArray(data.typing) && data.typing.length>0) {
            // show typing if the remote side typed recently and it's not me
            const remoteTyping = data.typing.some(t => t !== myType);
            if (remoteTyping) {
                typingIndicator.style.display = 'inline';
            } else {
                typingIndicator.style.display = 'none';
            }
        } else {
            typingIndicator.style.display = 'none';
        }

        // messages
        if (data.messages && data.messages.length>0) {
            data.messages.forEach(m=>{
                // append
                appendMessage(m);
                lastMsgId = Math.max(lastMsgId, m.id || lastMsgId);
            });
        }
    })
    .catch(err=>{
        console.error('Erro no polling:',err);
    });
}

// helper time formatting
function formatTime(seconds) {
    if (!seconds || seconds <= 0) return '00:00';
    const m = Math.floor(seconds/60);
    const s = Math.floor(seconds % 60);
    return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

// send text message
function enviarMensagem() {
    const msg = msgInput.value.trim();
    if (!msg) return;
    const formData = new FormData();
    formData.append('action','enviar_mensagem');
    formData.append('chat_id',chatId);
    formData.append('mensagem', msg);

    fetch('../actions/chat_api.php',{ method:'POST', body: formData })
    .then(r=>r.json())
    .then(data=>{
        if (data && data.status === 'success') {
            msgInput.value = '';
            // Immediately show local copy (optimistic)
            const localMsg = { id: lastMsgId+1, sender_type: myType, mensagem: msg, data_envio: new Date().toISOString() };
            appendMessage(localMsg);
            lastMsgId = Math.max(lastMsgId, localMsg.id);
            // Notify server typing ended (optional)
        } else {
            console.error('Erro ao enviar mensagem', data);
        }
    })
    .catch(err=> console.error('Erro enviarMensagem:',err));
}

// encerrar chat
function encerrarChat() {
    if (!confirm('Deseja realmente encerrar o atendimento?')) return;
    const formData = new FormData();
    formData.append('action','encerrar_chat');
    formData.append('chat_id',chatId);
    fetch('../actions/chat_api.php',{ method:'POST', body: formData })
    .then(r=>r.json())
    .then(data=>{
        if (data && data.status === 'success') {
            mostrarProtocolo(data.protocolo);
        } else console.error('Erro ao encerrar chat', data);
    })
    .catch(err=> console.error('Erro encerrarChat:',err));
}

function mostrarProtocolo(protocolo) {
    chatActive = false;
    document.getElementById('protocoloNum').innerText = protocolo;
    document.getElementById('modalProtocolo').style.display = 'block';
    document.getElementById('backLink').href = (myType === 'admin') ? 'admin/dashboard.php' : 'home.php';
    msgInput.disabled = true;
}

// typing handler (sends 'typing' to server)
msgInput.addEventListener('input', function(e){
    // show locally
    if (msgInput.value.length > 0) {
        // quick throttle so we don't spam server: every 1.5s maximum
        const now = Date.now();
        if (now - typingSendThrottle > 1500) {
            typingSendThrottle = now;
            const fd = new FormData();
            fd.append('action','typing');
            fd.append('chat_id', chatId);
            fetch('../actions/chat_api.php',{ method:'POST', body: fd }).catch(()=>{});
        }
    }
});

// enter to send
msgInput.addEventListener('keypress', function(e){
    if (e.key === 'Enter') {
        e.preventDefault();
        enviarMensagem();
    }
});

// file upload
fileInput.addEventListener('change', function (ev) {
    const file = this.files[0];
    if (!file) return;
    // preview & upload
    uploadFile(file);
    fileInput.value = '';
});

function uploadFile(file) {
    const allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
    if (!allowed.includes(file.type)) {
        alert('Tipo de arquivo não permitido. Permitidos: JPG, PNG, WEBP, PDF.');
        return;
    }
    if (file.size > 10 * 1024 * 1024) { // 10MB
        alert('Arquivo muito grande. Máx 10MB.');
        return;
    }

    const fd = new FormData();
    fd.append('action','enviar_arquivo');
    fd.append('chat_id', chatId);
    fd.append('arquivo', file);

    // Show temp preview
    const tempMsg = { id: lastMsgId+1, sender_type: myType, mensagem: JSON.stringify({ _is_file:true, url:'#', name: file.name, filetype: file.type }), data_envio: new Date().toISOString() };
    appendMessage(tempMsg);
    lastMsgId = Math.max(lastMsgId, tempMsg.id);

    fetch('../actions/chat_api.php',{ method:'POST', body: fd })
    .then(r=>r.json())
    .then(data=>{
        if (data && data.status === 'success' && data.file) {
            // replace last temp with actual
            const actualMsg = { id: data.message_id || (lastMsgId+1), sender_type: myType, mensagem: JSON.stringify({ _is_file:true, url: data.file, name: data.name, filetype: data.type }), data_envio: data.data_envio || new Date().toISOString() };
            appendMessage(actualMsg);
        } else {
            alert('Erro ao enviar arquivo.');
            console.error(data);
        }
    })
    .catch(err=> { console.error('Erro upload:',err); alert('Erro no upload'); });
}

// Auto welcome: only when page loads and if chat already active (client side duplicate guard)
(function sendWelcomeOnceOnLoad(){
    // We do NOT send auto welcome from client because server already inserts welcome in aceitar_convite.
    // Keep this as safe fallback (no admin message) - will be ignored if already exists.
})();

// Start polling
setInterval(atualizarStatus, 3000);
atualizarStatus();

</script>

<?php
if (file_exists('../includes/footer.php')) {
    include '../includes/footer.php';
}
?>
