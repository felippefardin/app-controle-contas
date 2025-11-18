<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Verifica Permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$ja_impersonando = isset($_SESSION['usuario_original_id']);

// Permite acesso se for admin/master/proprietario OU se já estiver impersonando
if (($nivel !== 'admin' && $nivel !== 'master' && $nivel !== 'proprietario') && !$ja_impersonando) {
    header('Location: home.php?erro=sem_permissao');
    exit;
}

$conn = getTenantConnection();
if (!$conn) die("Erro de conexão.");

$id_atual = $_SESSION['usuario_id'];

// Busca todos os usuários exceto o atual
$sql = "SELECT id, nome, email, foto, nivel_acesso, status FROM usuarios WHERE id != ? AND status = 'ativo' ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_atual);
$stmt->execute();
$result = $stmt->get_result();

include('../includes/header.php');

// Tratamento de mensagens de erro
$erro_msg = '';
if (isset($_GET['erro'])) {
    switch($_GET['erro']) {
        case 'id_invalido': $erro_msg = 'Selecione um usuário válido para acessar.'; break;
        case 'senha_vazia': $erro_msg = 'A senha é obrigatória para acessar a conta.'; break;
        case 'senha_incorreta': $erro_msg = 'A senha informada está incorreta.'; break;
        case 'db_error': $erro_msg = 'Erro de conexão com o banco de dados.'; break;
        case 'usuario_nao_encontrado': $erro_msg = 'Usuário não encontrado ou inativo.'; break;
        case 'sem_permissao_troca': $erro_msg = 'Você não tem permissão para trocar de usuário.'; break;
        default: $erro_msg = 'Ocorreu um erro inesperado.'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>\Selecionar Usuário</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <body>        
   
<style>
    /* Ajustes locais para o Grid de Usuários mantendo o padrão do style.css */
    .user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .user-card {
        background-color: #333; /* Cor de fundo padrão dos inputs do style.css */
        border: 1px solid #444;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s, border-color 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .user-card:hover {
        transform: translateY(-5px);
        border-color: #0af;
        background-color: #3a3a3a;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #222;
        margin-bottom: 15px;
    }

    .user-info h4 {
        margin: 0 0 5px 0;
        color: #eee;
        font-size: 1.1rem;
    }

    .user-info p {
        margin: 0;
        font-size: 0.9rem;
        color: #bbb;
    }

    .badge-role {
        display: inline-block;
        margin-top: 10px;
        padding: 4px 8px;
        background-color: rgba(0, 170, 255, 0.2);
        color: #0af;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    /* Modal Style Overlay */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(3px);
    }

    /* Reutiliza estilos do form do style.css mas centralizado */
    .modal-content {
        background-color: #222;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 0 20px rgba(0, 175, 255, 0.3);
        position: relative;
    }

    .close-modal {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5rem;
        cursor: pointer;
        color: #888;
    }
    .close-modal:hover { color: #fff; }
</style>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #fff; margin: 0;"><i class="fas fa-users-cog"></i> Selecionar Usuário</h2>
        <a href="home.php" class="btn" style="background-color: #444; color: #fff; border: 1px solid #666; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if (!empty($erro_msg)): ?>
        <div class="erro"><?= htmlspecialchars($erro_msg) ?></div>
    <?php endif; ?>

    <p style="color: #ccc;">Selecione o usuário que deseja acessar. Será necessário informar a senha do usuário selecionado.</p>

    <div class="user-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="user-card" onclick="abrirModalConfirmacao('<?= $row['id'] ?>', '<?= htmlspecialchars($row['nome'], ENT_QUOTES) ?>')">
                    <img src="../img/usuarios/<?= htmlspecialchars($row['foto'] ?? 'default-profile.png') ?>" class="user-avatar" alt="Foto">
                    
                    <div class="user-info">
                        <h4><?= htmlspecialchars($row['nome']) ?></h4>
                        <p><?= htmlspecialchars($row['email']) ?></p>
                    </div>

                    <div class="badge-role">
                        <?= ucfirst($row['nivel_acesso']) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">
                <i class="fas fa-user-slash fa-3x"></i>
                <p style="margin-top: 10px;">Nenhum outro usuário ativo encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalSenha" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal" onclick="fecharModal()">&times;</span>
        <h3 style="color: #0af; margin-top: 0; margin-bottom: 20px;">Confirmar Acesso</h3>
        
        <form action="../actions/trocar_usuario.php" method="POST" style="box-shadow: none; padding: 0; margin: 0;">
            <input type="hidden" name="id_usuario" id="modalIdUsuario">
            
            <p>Acessar como: <strong id="modalNomeUsuario" style="color: #fff;"></strong></p>
            
            <label for="senha_usuario">Senha do Usuário:</label>
            <div style="position: relative;">
                <input type="password" name="senha_usuario" id="senha_usuario" required placeholder="Digite a senha deste usuário" style="padding-right: 40px;">
                <i class="fas fa-eye" id="toggleSenhaModal" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888;"></i>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn" style="background-color: #555; flex: 1;" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn" style="flex: 1;">Acessar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalConfirmacao(id, nome) {
        document.getElementById('modalIdUsuario').value = id;
        document.getElementById('modalNomeUsuario').innerText = nome;
        document.getElementById('senha_usuario').value = ''; // Limpa o campo
        document.getElementById('modalSenha').style.display = 'flex';
        setTimeout(() => document.getElementById('senha_usuario').focus(), 100);
    }

    function fecharModal() {
        document.getElementById('modalSenha').style.display = 'none';
    }

    // Fechar ao clicar fora
    document.getElementById('modalSenha').addEventListener('click', function(e) {
        if (e.target === this) fecharModal();
    });

    // Toggle senha no modal
    const toggleSenhaModal = document.getElementById('toggleSenhaModal');
    const inputSenhaModal = document.getElementById('senha_usuario');
    toggleSenhaModal.addEventListener('click', () => {
        const tipo = inputSenhaModal.getAttribute('type') === 'password' ? 'text' : 'password';
        inputSenhaModal.setAttribute('type', tipo);
        toggleSenhaModal.classList.toggle('fa-eye');
        toggleSenhaModal.classList.toggle('fa-eye-slash');
    });
</script>

 </body>
 </html>

<?php include('../includes/footer.php'); ?>