<?php
require_once '../includes/session_init.php';
require_once '../database.php';

/* ============================================================
   1. VERIFICA LOGIN
   ============================================================ */
if (empty($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

/* ============================================================
   2. VARIÁVEIS GERAIS
   ============================================================ */
$nivel       = $_SESSION['nivel_acesso'] ?? 'padrao';
$id_usuario  = $_SESSION['usuario_id'];
$tenant_id   = $_SESSION['tenant_id'] ?? null;
$is_admin    = in_array($nivel, ['admin', 'master', 'proprietario']);

/* ============================================================
   3. CONEXÕES
   ============================================================ */
$connTenant = getTenantConnection();
$connMaster = getMasterConnection();

if (!$connTenant || !$connMaster) {
    die("Erro de conexão com o banco de dados.");
}

/* ============================================================
   4. LÓGICA DE LIMITE DO PLANO
   ============================================================ */
$pode_adicionar = false;
$total_ativos   = 0;
$limite_total   = 0;
$msg_limite     = "";

if ($is_admin && $tenant_id) {

    // --- Buscar o plano ---
    $stmtPlan = $connMaster->prepare("
        SELECT plano_atual, usuarios_extras 
        FROM tenants 
        WHERE tenant_id = ?
    ");
    $stmtPlan->bind_param("s", $tenant_id);
    $stmtPlan->execute();
    $dadosPlano = $stmtPlan->get_result()->fetch_assoc();
    $stmtPlan->close();

    $planoAtual  = $dadosPlano['plano_atual'] ?? 'basico';
    $extras      = intval($dadosPlano['usuarios_extras'] ?? 0);

    // --- Limites base ---
    $limites = [
        'basico'    => 3,
        'plus'      => 6,
        'essencial' => 16
    ];
    $limiteBase = $limites[$planoAtual] ?? 3;

    $limite_total = $limiteBase + $extras;

    // --- Contar usuários ativos ---
    $resCount = $connTenant->query("SELECT COUNT(*) AS total FROM usuarios WHERE status = 'ativo'");
    $total_ativos = intval($resCount->fetch_assoc()['total']);

    // --- Validação ---
    $pode_adicionar = ($total_ativos < $limite_total);
    if (!$pode_adicionar) {
        $msg_limite = "Limite do plano atingido ($total_ativos / $limite_total)";
    }
}

/* ============================================================
   5. CONSULTA DE USUÁRIOS
   ============================================================ */
if ($is_admin) {
    $stmt = $connTenant->prepare("
        SELECT id, nome, email, cpf, telefone, status, perfil, nivel_acesso
        FROM usuarios
        ORDER BY nome ASC
    ");
} else {
    $stmt = $connTenant->prepare("
        SELECT id, nome, email, cpf, telefone, status, perfil, nivel_acesso
        FROM usuarios
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id_usuario);
}

$stmt->execute();
$result = $stmt->get_result();

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerenciar Usuários</title>

<style>
/* ESTILO BASE */
body {
    margin: 0;
    padding: 0;
    background: #121212;
    font-family: Arial, sans-serif;
    color: #ddd;
}

.container-usuarios {
    max-width: 1200px;
    margin: 30px auto;
    padding: 25px;
    background: #1e1e1e;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #333;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

/* Botão add */
.btn-novo {
    background: linear-gradient(135deg, #28a745, #218838);
    padding: 10px 18px;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 10px rgba(40,167,69,0.3);
}
.btn-novo:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(40,167,69,0.5);
}

/* Botão bloqueado */
.btn-bloqueado {
    background: #333;
    color: #aaa;
    padding: 10px 18px;
    border: 1px solid #444;
    border-radius: 6px;
}

/* Tabela */
.custom-table {
    width: 100%;
    border-collapse: collapse;
    background: #252525;
    border-radius: 6px;
    overflow: hidden;
}

.custom-table th {
    background: #2c2c2c;
    color: #00bfff;
    padding: 14px;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.custom-table td {
    padding: 14px;
    border-bottom: 1px solid #333;
}

/* Status */
.status-badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: bold;
}

.status-ativo {
    border: 1px solid #2ecc71;
    color: #2ecc71;
    background: rgba(46,204,113,0.15);
}

.status-inativo {
    border: 1px solid #e74c3c;
    color: #e74c3c;
    background: rgba(231,76,60,0.15);
}

/* Action buttons */
.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 5px;
    text-decoration: none;
    border: none;
    cursor: pointer;
}
.btn-edit { background: #ffc107; color: black; }
.btn-toggle-on { background: #28a745; }
.btn-toggle-off { border: 1px solid #dc3545; color: #dc3545; }
.btn-delete { background: #dc3545; }

/* Alertas */
.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}
.alert-success {
    border: 1px solid #28a745;
    background: rgba(40,167,69,0.18);
    color: #28a745;
}
.alert-danger {
    border: 1px solid #dc3545;
    background: rgba(220,53,69,0.18);
    color: #dc3545;
}
</style>
</head>

<body>

<div class="container-usuarios">

    <!-- Cabeçalho -->
    <div class="page-header">
        <h2><i class="fas fa-users"></i> Gerenciar Usuários</h2>

        <?php if ($is_admin): ?>
            <?php if ($pode_adicionar): ?>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="text-muted">
                        <i class="fas fa-chart-pie"></i> Uso: <b><?= $total_ativos ?>/<?= $limite_total ?></b>
                    </span>
                    <a href="add_usuario.php" class="btn-novo"><i class="fas fa-plus"></i> Novo Usuário</a>
                </div>
            <?php else: ?>
                <div>
                    <button class="btn-bloqueado" disabled>
                        <i class="fas fa-lock"></i> Limite Atingido (<?= $total_ativos ?>/<?= $limite_total ?>)
                    </button>
                    <a href="minha_assinatura.php" style="color:#ffc107;font-weight:bold;margin-left:10px;">
                        Aumentar Limite
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Alertas -->
    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert <?= (isset($_GET['erro']) ? 'alert-danger' : 'alert-success') ?>">
            <i class="fas <?= isset($_GET['erro']) ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nome / Perfil</th>
                    <th>Email / Contato</th>
                    <th>Status</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($u = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['nome']) ?></strong><br>
                            <span class="text-muted"><?= ucfirst($u['nivel_acesso']) ?></span>
                        </td>

                        <td>
                            <?= htmlspecialchars($u['email']) ?><br>
                            <span class="text-muted"><?= htmlspecialchars($u['telefone'] ?? '') ?></span>
                        </td>

                        <td>
                            <?php if ($u['status'] === 'ativo'): ?>
                                <span class="status-badge status-ativo">Ativo</span>
                            <?php else: ?>
                                <span class="status-badge status-inativo">Inativo</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:right;">

                            <!-- Editar -->
                            <a href="editar_usuario.php?id=<?= $u['id'] ?>" 
                               class="action-btn btn-edit" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>

                            <!-- Admin controls -->
                            <?php if ($is_admin && $u['id'] != $id_usuario): ?>

                                <?php if ($u['status'] === 'ativo'): ?>

                                    <!-- Inativar -->
                                    <a href="../actions/toggle_status.php?id=<?= $u['id'] ?>"
                                       class="action-btn btn-toggle-off"
                                       title="Inativar Usuário"
                                       onclick="return confirm('Tem certeza que deseja INATIVAR este usuário? Ele perderá o acesso imediatamente.');">
                                        <i class="fas fa-ban"></i>
                                    </a>

                                <?php else: ?>

                                    <?php if ($pode_adicionar): ?>
                                        <!-- Reativar -->
                                        <a href="../actions/toggle_status.php?id=<?= $u['id'] ?>"
                                           class="action-btn btn-toggle-on"
                                           title="Reativar Usuário">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="action-btn btn-toggle-off" disabled style="opacity:.5;">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>

                                <?php endif; ?>

                                <!-- Exclusão -->
                                <button class="action-btn btn-delete"
                                        onclick="abrirModal(<?= $u['id'] ?>, '<?= addslashes($u['nome']) ?>')"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>

                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;padding:25px;">Nenhum usuário encontrado.</td>
                </tr>
            <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>

<!-- Modal EXCLUSÃO -->
<div id="modalExclusao" 
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;justify-content:center;align-items:center;">
    <div style="background:#252525;padding:25px;border-radius:8px;max-width:400px;width:90%;text-align:center;border:1px solid #444;">
        <h3 style="color:#dc3545;"><i class="fas fa-trash-alt"></i> Confirmar Exclusão</h3>
        <p>Deseja realmente excluir o usuário:</p>
        <p><strong id="nomeDel" style="font-size:1.1rem;"></strong></p>
        <p style="color:#aaa;font-size:0.9rem;">Essa ação é irreversível.</p>
        <div style="margin-top:15px;display:flex;justify-content:center;gap:10px;">
            <button onclick="fecharModal()" 
                    style="padding:10px 18px;background:#444;border:none;border-radius:6px;color:#fff;">
                Cancelar
            </button>
            <a id="linkExcluir" href="#" 
               style="padding:10px 18px;background:#dc3545;border-radius:6px;color:#fff;text-decoration:none;font-weight:bold;">
               Excluir
            </a>
        </div>
    </div>
</div>

<script>
function abrirModal(id, nome) {
    document.getElementById("nomeDel").innerText = nome;
    document.getElementById("linkExcluir").href = "../actions/excluir_usuario.php?id=" + id;
    document.getElementById("modalExclusao").style.display = "flex";
}
function fecharModal() {
    document.getElementById("modalExclusao").style.display = "none";
}
window.onclick = function(e) {
    if (e.target.id === "modalExclusao") fecharModal();
}

/* Fade automático dos alertas */
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = "opacity .5s ease";
        alert.style.opacity = "0";
        setTimeout(() => alert.remove(), 600);
    });
}, 4000);
</script>

</body>
</html>

<?php
$stmt->close();
$connTenant->close();
$connMaster->close();
include('../includes/footer.php');
?>
