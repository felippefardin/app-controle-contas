<?php
require_once '../includes/config/config.php';
require_once '../includes/session_init.php';
require_once '../database.php'; 
require_once '../includes/header.php';


// 🔒 Usuário precisa estar logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php?erro=nao_logado");
    exit();
}

// 🔒 Verificar tenant ativo
if (!isset($_SESSION['tenant_id'])) {
    session_destroy();
    header("Location: login.php?erro=tenant_inexistente");
    exit();
}

// 📌 Pega dados do usuário
$usuario_id    = $_SESSION['usuario_id']; 
$tenant_id     = $_SESSION['tenant_id'];
$nome_usuario  = $_SESSION['nome'];

// 📌 Conexão do tenant
$conn = getTenantConnection();
if (!$conn) {
    die("Erro ao conectar com o banco de dados do tenant.");
}

// ====================================================================
// 🛠️ AUTO-CORREÇÃO: CRIA A TABELA SE ELA NÃO EXISTIR
// ====================================================================
try {
    $sqlCriaTabela = "
    CREATE TABLE IF NOT EXISTS lembretes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        titulo VARCHAR(100) NOT NULL,
        descricao TEXT,
        data_lembrete DATE NOT NULL,
        hora_lembrete TIME NOT NULL,
        cor VARCHAR(20) NOT NULL,
        email_enviado TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $conn->query($sqlCriaTabela);
} catch (Exception $e) {
    die("Erro ao criar tabela de lembretes: " . $e->getMessage());
}

// Buscar lembretes
$sql = "SELECT * FROM lembretes WHERE usuario_id = ? ORDER BY data_lembrete ASC, hora_lembrete ASC";
$stmt = $conn->prepare($sql);

$lembretes = [];

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lembretes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<style>
 /* ==============================
   LAYOUT BASE
============================== */
body {
    background-color: #121212;
    color: #e0e0e0;
}

.page-title {
    color: #00bfff;
    font-weight: bold;
    margin-bottom: 20px;
    border-bottom: 1px solid #333;
    padding-bottom: 10px;
}

/* ==============================
   CARDS DE LEMBRETES
============================== */
.card-lembrete {
    background-color: #1e1e1e;
    border: none;
    border-radius: 10px;
    color: #ccc;
    box-shadow: 0 4px 8px rgba(0,0,0,0.4);
    transition: transform .2s ease, box-shadow .2s ease;
}

.card-lembrete:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,191,255,0.12);
}

.card-lembrete h5 {
    color: #fff;
    font-weight: bold;
}

.card-lembrete small {
    color: #888;
}

/* Borda de prioridade */
.border-verde { border-left: 5px solid #28a745 !important; }
.border-amarelo { border-left: 5px solid #ffc107 !important; }
.border-vermelho { border-left: 5px solid #dc3545 !important; }

/* ==============================
   BOTÕES PERSONALIZADOS
============================== */
.btn-custom-add {
    background-color: #00bfff;
    border: none;
    color: #121212;
    font-weight: bold;
}

.btn-custom-add:hover {
    background-color: #009acd;
    color: #fff;
}

.btn-action {
    background-color: #333;
    border: none;
    color: #ccc;
}

.btn-action:hover {
    background-color: #444;
    color: #fff;
}

/* ==============================
   MODAL
============================== */
.modal-content {
    background-color: #1f1f1f;
    border: 1px solid #444;
    color: #fff;
}

.modal-header,
.modal-footer {
    border-color: #333;
}

.btn-close {
    filter: invert(1); /* Ícone branco */
}

/* ==============================
   INPUTS / FORMS
============================== */
.form-control,
.form-select {
    background-color: #2c2c2c;
    border: 1px solid #444;
    color: #fff;
}

.form-control:focus,
.form-select:focus {
    background-color: #2c2c2c;
    border-color: #00bfff;
    color: #fff;
    box-shadow: 0 0 5px rgba(0,191,255, .3);
}

/* Ícone dentro dos inputs */
.input-group-text {
    background-color: #1a1a1a;
    border: 1px solid #444;
    color: #888;
}

/* ==============================
   ESTILO QUANDO NÃO HÁ LEMBRETES
============================== */
.empty-box {
    background-color: #1e1e1e;
    padding: 50px;
    border-radius: 10px;
    color: #666;
}

</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="page-title"><i class="fas fa-sticky-note"></i> Meus Lembretes</h2>
        <button class="btn btn-custom-add px-4" data-bs-toggle="modal" data-bs-target="#modalLembrete" onclick="limparModal()">
            <i class="fas fa-plus"></i> Novo Lembrete
        </button>
    </div>

    <?php
    if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
    }
    ?>

    <div class="row">
        <?php if (count($lembretes) > 0): ?>
            <?php foreach ($lembretes as $lem): 
                // Define a classe da borda baseada na cor
                $borderClass = '';
                switch($lem['cor']) {
                    case 'verde': $borderClass = 'border-verde'; break;
                    case 'amarelo': $borderClass = 'border-amarelo'; break;
                    case 'vermelho': $borderClass = 'border-vermelho'; break;
                    default: $borderClass = 'border-verde';
                }
            ?>
            <div class="col-md-4 mb-4">
                <div class="card card-lembrete <?= $borderClass ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title m-0"><?= htmlspecialchars($lem['titulo']) ?></h5>
                            <?php if($lem['cor'] == 'vermelho'): ?>
                                <i class="fas fa-exclamation-circle text-danger" title="Urgente"></i>
                            <?php endif; ?>
                        </div>
                        
                        <small class="d-block mb-3">
                            <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($lem['data_lembrete'])) ?> 
                            <i class="far fa-clock ms-2"></i> <?= date('H:i', strtotime($lem['hora_lembrete'])) ?>
                        </small>
                        
                        <p class="card-text"><?= nl2br(htmlspecialchars($lem['descricao'])) ?></p>
                        
                        <div class="text-end mt-3">
                            <button class="btn btn-sm btn-action me-1" onclick='editarLembrete(<?= json_encode($lem) ?>)' title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="../actions/excluir_lembrete.php?id=<?= $lem['id'] ?>" class="btn btn-sm btn-action text-danger" onclick="return confirm('Tem certeza que deseja excluir este lembrete?')" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center mt-5">
                <div class="p-5" style="background: #1e1e1e; border-radius: 10px;">
                    <i class="fas fa-check-circle fa-3x mb-3" style="color: #333;"></i>
                    <p class="text-muted">Nenhum lembrete pendente.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalLembrete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="../actions/salvar_lembrete.php" method="POST" class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Novo Lembrete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="lembreteId">
                
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-heading"></i></span>
                        <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ex: Pagar Fornecedor X">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" id="data" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Hora</label>
                        <input type="time" name="hora" id="hora" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Prioridade</label>
                    <select name="cor" id="cor" class="form-select" required>
                        <option value="verde">🟢 Normal (Verde)</option>
                        <option value="amarelo">🟡 Atenção (Amarelo)</option>
                        <option value="vermelho">🔴 Urgente (Vermelho)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="3" placeholder="Detalhes do lembrete..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-custom-add">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
function limparModal() {
    document.getElementById('lembreteId').value = '';
    document.getElementById('titulo').value = '';
    document.getElementById('data').value = '';
    document.getElementById('hora').value = '';
    document.getElementById('descricao').value = '';
    document.getElementById('cor').value = 'verde';
    document.getElementById('modalTitle').innerText = 'Novo Lembrete';
}

function editarLembrete(data) {
    document.getElementById('lembreteId').value = data.id;
    document.getElementById('titulo').value = data.titulo;
    document.getElementById('data').value = data.data_lembrete;
    
    if(data.hora_lembrete) {
        document.getElementById('hora').value = data.hora_lembrete.substring(0, 5);
    }
    
    document.getElementById('descricao').value = data.descricao;
    document.getElementById('cor').value = data.cor;
    
    document.getElementById('modalTitle').innerText = 'Editar Lembrete';
    var modal = new bootstrap.Modal(document.getElementById('modalLembrete'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>