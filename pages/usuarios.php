<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Incluído no início

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO CORRETA
$usuario_logado = $_SESSION['usuario_logado'];
$usuario_logado_id = $usuario_logado['id'];

// Inclui o header após a lógica inicial
include('../includes/header.php');

// Mensagens
$mensagem_sucesso = '';
$mensagem_erro = '';

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == '1') {
        $mensagem_sucesso = "Usuário salvo com sucesso!";
    } elseif ($_GET['sucesso'] == 'excluido') {
        $mensagem_sucesso = "Usuário excluído com sucesso!";
    }
}

if (isset($_GET['erro'])) {
    switch($_GET['erro']) {
        case 'auto_exclusao':
            $mensagem_erro = "Você não pode excluir seu próprio usuário.";
            break;
        case 'permissao':
            $mensagem_erro = "Você não tem permissão para excluir este usuário.";
            break;
        default:
            $mensagem_erro = "Ocorreu um erro!";
    }
}

// ✅ 3. SIMPLIFICA A QUERY PARA O MODELO SAAS
// A consulta agora busca todos os usuários do banco de dados do tenant atual.
$sql = "SELECT id, nome, email, cpf, telefone FROM usuarios ORDER BY nome ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Erro na consulta: " . $conn->error);
}

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
    .container { background-color: #222; padding: 25px; border-radius: 8px; }
    h1 { color: #00bfff; border-bottom: 2px solid #00bfff; padding-bottom: 10px; margin-bottom: 2rem; display: flex; align-items: center; gap: 12px; }
    h1 i { font-size: 1.9rem; color: #00bfff; }
    .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; color: white; opacity: 1; transition: opacity 0.5s ease-out; }
    .alert-success { background-color: #28a745; }
    .alert-danger { background-color: #cc4444; }
    .table { width: 100%; color: #eee; border-collapse: collapse; }
    .table thead { background-color: #00bfff; color: #ffffff; font-weight: bold; }
    .table th, .table td { padding: 12px 15px; border: 1px solid #444; text-align: left; }
    .table tbody tr { background-color: #2c2c2c; }
    .table tbody tr:hover { background-color: #3c3c3c; }
    .btn { padding: 8px 14px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; text-decoration: none; display: inline-block; margin-right: 5px; transition: background-color 0.3s ease; }
    .btn-primary { background-color: #00bfff; color: white; }
    .btn-primary:hover { background-color: #0099cc; }
    .btn-info { background-color: #17a2b8; color: white; }
    .btn-info:hover { background-color: #117a8b; }
    .btn-danger { background-color: #dc3545; color: white; }
    .btn-danger:hover { background-color: #c82333; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .btn-secondary:hover { background-color: #5a6268; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; }
    .modal-content { background-color: #1f1f1f; margin: auto; padding: 25px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 191, 255, 0.5); width: 90%; max-width: 500px; position: relative; text-align: center; }
    .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-content .close-btn:hover { color: #00bfff; }
    .modal-content h3 { color: #00bfff; margin-bottom: 15px; }
    .modal-content p { margin-bottom: 25px; }
    #searchInput {
        margin-bottom: 15px;
        background-color: #333;
        color: #eee;
        border: 1px solid #444;
        padding: 8px 12px;
        border-radius: 6px;
        width: 100%;
    }
    #searchInput:focus { border-color: #00bfff; outline: none; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-users"></i> Gestão de Usuários</h1>

    <?php if ($mensagem_sucesso): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
    <?php endif; ?>
    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
    <?php endif; ?>

    <a href="add_usuario.php" class="btn btn-primary" style="margin-bottom: 20px;"><i class="fa-solid fa-plus"></i> Adicionar Novo Usuário</a>

    <input type="text" id="searchInput" placeholder="Pesquisar por Nome, Email ou CPF...">

    <div class="table-responsive">
        <table class="table" id="usuariosTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($usuario = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cpf']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                            <td>
                                <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-info"><i class="fa-solid fa-pen"></i> Editar</a>
                                <a href="#" class="btn btn-danger" onclick="openDeleteModal(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars(addslashes($usuario['nome'])); ?>'); return false;">
                                    <i class="fa-solid fa-trash"></i> Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">Nenhum usuário encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content"></div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
    function openDeleteModal(id, nome) {
        const modal = document.getElementById('deleteModal');
        const modalContent = modal.querySelector('.modal-content');
        modalContent.innerHTML = `
            <span class="close-btn" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Confirmar Exclusão</h3>
            <p>Tem certeza que deseja excluir o usuário <strong>${nome}</strong>?<br>Esta ação não poderá ser desfeita.</p>
            <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
                <a href="../actions/excluir_usuario.php?id=${id}" class="btn btn-danger">Sim, Excluir</a>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').style.display='none'">Cancelar</button>
            </div>
        `;
        modal.style.display = 'flex';
    }

    window.addEventListener('click', e => {
        const modal = document.getElementById('deleteModal');
        if (e.target === modal) modal.style.display = 'none';
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display='none', 500);
            }, 4000);
        });
    });

    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usuariosTable tbody tr');
        rows.forEach(row => {
            const nome = row.cells[0].textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            const cpf = row.cells[2].textContent.toLowerCase();
            if (nome.includes(filter) || email.includes(filter) || cpf.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>