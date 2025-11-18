<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Função auxiliar para segurança HTML
function safe($val) { return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8'); }

// Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

// Verifica Permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$id_usuario_logado = $_SESSION['usuario_id'];
$is_admin = ($nivel === 'admin' || $nivel === 'master' || $nivel === 'proprietario');

$conn = getTenantConnection();
if (!$conn) die("Erro de conexão.");

// Busca usuários
if ($is_admin) {
    $sql = "SELECT id, nome, email, cpf, telefone, status, perfil FROM usuarios ORDER BY nome ASC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT id, nome, email, cpf, telefone, status, perfil FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario_logado);
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
<title>Gestão de Usuários</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
    h1 { color: #00bfff; border-bottom: 2px solid #00bfff; padding-bottom: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 12px; }
    h1 i { font-size: 1.9rem; color: #00bfff; }
    
    .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; color: white; opacity: 1; transition: opacity 0.5s ease-out; }
    .alert-success { background-color: #28a745; }
    .alert-danger { background-color: #dc3545; }

    .table { width: 100%; color: #eee; border-collapse: collapse; margin-top: 20px; }
    .table thead { background-color: #00bfff; color: #ffffff; font-weight: bold; }
    .table th, .table td { padding: 12px 15px; border: 1px solid #444; text-align: left; vertical-align: middle; }
    .table tbody tr { background-color: #2c2c2c; }
    .table tbody tr:hover { background-color: #3c3c3c; }
    
    .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.85em; font-weight: bold; display: inline-block; }
    .badge-ativo { background-color: #28a745; color: white; }
    .badge-inativo { background-color: #dc3545; color: white; }
    .badge-admin { background-color: #17a2b8; color: white; }
    .badge-padrao { background-color: #6c757d; color: white; }

    .btn { padding: 8px 14px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; text-decoration: none; display: inline-block; margin-right: 5px; transition: background-color 0.3s ease; color: white; }
    .btn-primary { background-color: #00bfff; }
    .btn-primary:hover { background-color: #0099cc; }
    .btn-warning { background-color: #ffc107; color: #212529; }
    .btn-warning:hover { background-color: #e0a800; }
    .btn-secondary { background-color: #6c757d; }
    .btn-secondary:hover { background-color: #5a6268; }
    .btn-danger { background-color: #dc3545; }
    .btn-danger:hover { background-color: #c82333; }
    
    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; margin: auto; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(220, 53, 69, 0.5); width: 90%; max-width: 500px; position: relative; text-align: center; border: 1px solid #dc3545; }
    .modal-header h3 { color: #dc3545; margin-top: 0; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .close-btn:hover { color: #fff; }
    .modal-footer { margin-top: 25px; display: flex; justify-content: center; gap: 15px; }

    #searchInput { margin-bottom: 15px; background-color: #333; color: #eee; border: 1px solid #444; padding: 8px 12px; border-radius: 6px; width: 100%; box-sizing: border-box; }
    #searchInput:focus { border-color: #00bfff; outline: none; }
</style>
</head>
<body>

<div class="container">
    <h1><i class="fa-solid fa-users"></i> Gestão de Usuários</h1>

    <!-- Mensagens de Feedback -->
    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg'] ?? 'Operação realizada com sucesso!') ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['msg'] ?? 'Ocorreu um erro.') ?>
        </div>
    <?php endif; ?>

    <!-- Botão Adicionar (Apenas Admin) -->
    <?php if ($is_admin): ?>
        <a href="add_usuario.php" class="btn btn-primary" style="margin-bottom: 20px;">
            <i class="fa-solid fa-plus"></i> Adicionar Novo Usuário
        </a>
        <input type="text" id="searchInput" placeholder="Pesquisar por Nome, Email ou CPF...">
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table" id="usuariosTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                            // Tratamento de status e perfil para classes CSS
                            $statusClass = ($row['status'] === 'ativo') ? 'badge-ativo' : 'badge-inativo';
                            $statusLabel = ucfirst($row['status']);
                            
                            $perfilClass = ($row['perfil'] === 'admin') ? 'badge-admin' : 'badge-padrao';
                            $perfilLabel = ($row['perfil'] === 'admin') ? 'Administrador' : 'Padrão';
                        ?>
                        <tr>
                            <td><?= safe($row['nome']) ?></td>
                            <td><?= safe($row['email']) ?></td>
                            <td><span class="badge <?= $perfilClass ?>"><?= $perfilLabel ?></span></td>
                            <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td class="text-right">
                                <!-- Botão Editar -->
                                <a href="editar_usuario.php?id=<?= $row['id'] ?>" class="btn btn-warning" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <?php if ($is_admin && $row['id'] != $id_usuario_logado): ?>
                                    <!-- Botão Ativar/Desativar -->
                                    <a href="../actions/toggle_status.php?id=<?= $row['id'] ?>&status=<?= $row['status'] ?>" class="btn btn-secondary" title="<?= $row['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                        <i class="fa-solid fa-power-off"></i>
                                    </a>

                                    <!-- Botão Excluir -->
                                    <button type="button" class="btn btn-danger" title="Excluir"
                                            onclick="confirmarExclusao(<?= $row['id'] ?>, '<?= addslashes($row['nome']) ?>')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">Nenhum usuário encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Exclusão -->
<div id="modalExclusao" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="fecharModal()">&times;</span>
        <div class="modal-header">
            <h3><i class="fas fa-triangle-exclamation"></i> Confirmar Exclusão</h3>
        </div>
        <div class="modal-body">
            <p>Tem certeza que deseja excluir o usuário <strong id="nomeUsuarioDel" style="color: #fff;"></strong>?</p>
            <p style="color: #aaa; font-size: 0.9em;">Esta ação não poderá ser desfeita.</p>
        </div>
        <div class="modal-footer">
            <a id="linkExclusao" href="#" class="btn btn-danger">Sim, Excluir</a>
            <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    const modal = document.getElementById('modalExclusao');
    document.getElementById('nomeUsuarioDel').innerText = nome;
    // Define o link correto para a ação de exclusão
    document.getElementById('linkExclusao').href = '../actions/excluir_usuario.php?id=' + id;
    modal.style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modalExclusao').style.display = 'none';
}

// Fecha se clicar fora do conteúdo do modal
window.onclick = function(e) {
    const modal = document.getElementById('modalExclusao');
    if (e.target === modal) {
        fecharModal();
    }
}

// Remove alertas automaticamente
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display='none', 500);
        }, 4000);
    });
});

const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usuariosTable tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>