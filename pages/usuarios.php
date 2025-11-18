<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

// Verifica Permissão (Admin, Master ou Proprietário)
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
    <title>Usuários</title>
    <body>
<style>
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; }
    .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
    h1 { color: #00bfff; border-bottom: 2px solid #00bfff; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
    
    .btn { padding: 8px 12px; border-radius: 5px; text-decoration: none; color: #fff; font-size: 14px; display: inline-block; margin-right: 5px; border: none; cursor: pointer; }
    .btn-primary { background-color: #00bfff; }
    .btn-warning { background-color: #ffc107; color: #000; }
    .btn-danger { background-color: #dc3545; }
    .btn-secondary { background-color: #6c757d; }
    .btn-success { background-color: #28a745; }
    .btn:hover { opacity: 0.9; }

    .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .table th { background: #00bfff; color: #fff; padding: 10px; text-align: left; }
    .table td { border-bottom: 1px solid #444; padding: 10px; }
    .badge { padding: 5px 10px; border-radius: 10px; font-size: 12px; }
    .bg-ativo { background: #28a745; color: #fff; }
    .bg-inativo { background: #dc3545; color: #fff; }

    /* Modal Styles */
    .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; }
    .modal-content { background: #333; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px; text-align: center; border: 1px solid #00bfff; }
    .close-btn { float: right; cursor: pointer; font-size: 20px; }
</style>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color:#00bfff;"><i class="fas fa-users"></i> Gestão de Usuários</h2>
        
        <?php if ($is_admin): ?>
            <a href="add_usuario.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Novo Usuário
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg'] ?? 'Ação realizada com sucesso!') ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['msg'] ?? 'Ocorreu um erro.') ?>
        </div>
    <?php endif; ?>

    <div class="card bg-dark text-light border-secondary">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr class="text-light">
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="align-middle">
                                    <strong><?= htmlspecialchars($row['nome']) ?></strong><br>
                                    <small class="text-muted"><?= ucfirst($row['perfil']) ?></small>
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($row['email']) ?></td>
                                <td class="align-middle">
                                    <?php if($row['status'] === 'ativo'): ?>
                                        <span class="badge badge-success p-2">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger p-2">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right align-middle">
                                    <a href="editar_usuario.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <?php if ($is_admin && $row['id'] != $id_usuario_logado): ?>
                                        <a href="../actions/toggle_status.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary" title="<?= $row['status'] === 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                            <i class="fas fa-power-off"></i>
                                        </a>

                                        <button onclick="confirmarExclusao(<?= $row['id'] ?>, '<?= addslashes($row['nome']) ?>')" class="btn btn-sm btn-danger" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExclusao" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content bg-dark text-light border-danger">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Confirmar Exclusão</h5>
        <button type="button" class="close text-light" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja excluir o usuário <strong id="nomeUsuarioDel"></strong>?</p>
        <p class="small text-muted">Essa ação não pode ser desfeita.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <a id="linkExclusao" href="#" class="btn btn-danger">Sim, Excluir</a>
      </div>
    </div>
  </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    $('#nomeUsuarioDel').text(nome);
    $('#linkExclusao').attr('href', '../actions/excluir_usuario.php?id=' + id);
    $('#modalExclusao').modal('show');
}

// Remove alertas automaticamente
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 4000);
</script>

</body>
</head>

<?php include('../includes/footer.php'); ?>