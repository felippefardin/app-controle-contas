<?php
require_once '../includes/session_init.php';

// Verifica Permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
if ($nivel !== 'admin' && $nivel !== 'master' && $nivel !== 'proprietario') {
    header('Location: usuarios.php?erro=1&msg=Acesso negado');
    exit;
}

include('../includes/header.php');
?>

<div class="container mt-5" style="max-width:600px; background:#222; padding:30px; border-radius:10px; color:#fff;">
    <h2 style="color:#00bfff; border-bottom:1px solid #00bfff; padding-bottom:10px;">
        <i class="fa-solid fa-user-plus"></i> Novo Usuário
    </h2>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger" style="background:#dc3545; padding:10px; border-radius:5px; margin:15px 0;">
            <?= htmlspecialchars($_GET['msg'] ?? 'Erro desconhecido') ?>
        </div>
    <?php endif; ?>

    <form action="../actions/add_usuario.php" method="POST">
        <div class="form-group mb-3">
            <label>Nome Completo:</label>
            <input type="text" name="nome" class="form-control" required style="width:100%; padding:8px; margin-top:5px;">
        </div>

        <div class="form-group mb-3">
            <label>E-mail:</label>
            <input type="email" name="email" class="form-control" required style="width:100%; padding:8px; margin-top:5px;">
        </div>

        <div class="form-group mb-3">
            <label>CPF (Opcional):</label>
            <input type="text" name="cpf" class="form-control" style="width:100%; padding:8px; margin-top:5px;">
        </div>

        <div class="form-group mb-3">
            <label>Senha:</label>
            <input type="password" name="senha" class="form-control" required style="width:100%; padding:8px; margin-top:5px;">
        </div>

        <div class="form-group mb-3">
            <label>Confirmar Senha:</label>
            <input type="password" name="senha_confirmar" class="form-control" required style="width:100%; padding:8px; margin-top:5px;">
        </div>
        
        <div class="form-group mb-3">
            <label>Nível de Acesso:</label>
            <select name="nivel" style="width:100%; padding:8px; margin-top:5px;">
                <option value="padrao">Padrão (Acesso Restrito)</option>
                <option value="admin">Administrador (Acesso Total)</option>
            </select>
        </div>

        <div style="margin-top:20px; display:flex; justify-content:space-between;">
            <a href="usuarios.php" class="btn btn-secondary" style="padding:10px; text-decoration:none; background:#666; color:#fff; border-radius:5px;">Voltar</a>
            <button type="submit" class="btn btn-primary" style="padding:10px; cursor:pointer; background:#00bfff; color:#fff; border:none; border-radius:5px;">Salvar Usuário</button>
        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>