<?php
session_start();
include('../database.php');

// --- SENHA DE ACESSO PARA A TELA DE DESENVOLVEDOR ---
// --- Troque 'sua_senha_secreta' por uma senha forte ---
define('DEV_PASSWORD', 'sua_senha_secreta');

if (isset($_POST['dev_password'])) {
    if ($_POST['dev_password'] === DEV_PASSWORD) {
        $_SESSION['developer_access'] = true;
    }
}

if (!isset($_SESSION['developer_access']) || $_SESSION['developer_access'] !== true) {
    echo '
        <style>
            body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; }
            form { background: #222; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(255, 0, 0, 0.7); }
            h2 { color: #ff4444; }
            input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #555; background: #333; color: #fff; }
            button { width: 100%; padding: 10px; background: #ff4444; color: white; border: none; border-radius: 5px; cursor: pointer; }
        </style>
        <form method="POST">
            <h2>Acesso Restrito</h2>
            <input type="password" name="dev_password" placeholder="Senha do Desenvolvedor" required>
            <button type="submit">Entrar</button>
        </form>
    ';
    exit;
}

// Lógica para buscar todos os dados
$todos_usuarios = $conn->query("SELECT * FROM usuarios ORDER BY nome ASC");
$contas_a_pagar = $conn->query("SELECT cp.*, u.nome as nome_usuario FROM contas_pagar cp JOIN usuarios u ON cp.usuario_id = u.id ORDER BY cp.data_vencimento DESC");
$contas_a_receber = $conn->query("SELECT cr.*, u.nome as nome_usuario FROM contas_receber cr JOIN usuarios u ON cr.usuario_id = u.id ORDER BY cr.data_vencimento DESC");

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Desenvolvedor</title>
    <style>
        body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        h1, h2 { text-align: center; color: #ff4444; }
        .container { max-width: 1200px; margin: auto; }
        .tab-nav { text-align: center; margin-bottom: 20px; }
        .tab-nav button { background: #333; color: #eee; border: none; padding: 10px 20px; cursor: pointer; margin: 0 5px; border-radius: 5px; }
        .tab-nav button.active { background: #ff4444; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        table { width: 100%; border-collapse: collapse; background-color: #1f1f1f; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #444; text-align: left; }
        th { background-color: #333; color: #ff4444; }
        .btn { padding: 5px 10px; border-radius: 5px; color: white; text-decoration: none; border: none; cursor: pointer; }
        .btn-editar { background-color: #00bfff; }
        .btn-excluir { background-color: #e74c3c; }
        .btn-bloquear { background-color: #f39c12; }
        .btn-desbloquear { background-color: #27ae60; }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fa fa-cogs"></i> Painel do Desenvolvedor</h1>

    <div class="tab-nav">
        <button class="tab-btn active" data-tab="usuarios">Usuários</button>
        <button class="tab-btn" data-tab="pagar">Contas a Pagar</button>
        <button class="tab-btn" data-tab="receber">Contas a Receber</button>
    </div>

    <div id="usuarios" class="tab-content active">
        <h2>Todos os Usuários</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>CPF</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $todos_usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['nome']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['cpf']) ?></td>
                    <td><?= ucfirst($user['status']) ?></td>
                    <td>
                        <?php if ($user['status'] === 'ativo'): ?>
                            <a href="../actions/dev_block_user.php?id=<?= $user['id'] ?>&status=bloqueado" class="btn btn-bloquear" onclick="return confirm('Tem certeza que deseja bloquear este usuário?')">Bloquear</a>
                        <?php else: ?>
                            <a href="../actions/dev_block_user.php?id=<?= $user['id'] ?>&status=ativo" class="btn btn-desbloquear" onclick="return confirm('Tem certeza que deseja desbloquear este usuário?')">Desbloquear</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="pagar" class="tab-content">
        <h2>Todas as Contas a Pagar</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Fornecedor</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($conta = $contas_a_pagar->fetch_assoc()): ?>
                <tr>
                    <td><?= $conta['id'] ?></td>
                    <td><?= htmlspecialchars($conta['nome_usuario']) ?></td>
                    <td><?= htmlspecialchars($conta['fornecedor']) ?></td>
                    <td>R$ <?= number_format($conta['valor'], 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($conta['data_vencimento'])) ?></td>
                    <td><?= ucfirst($conta['status']) ?></td>
                    <td>
                        <a href="../actions/editar_conta_pagar.php?id=<?= $conta['id'] ?>&dev=1" class="btn btn-editar">Editar</a>
                        <a href="../actions/excluir_conta_pagar.php?id=<?= $conta['id'] ?>&dev=1" class="btn btn-excluir" onclick="return confirm('Excluir esta conta permanentemente?')">Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="receber" class="tab-content">
        <h2>Todas as Contas a Receber</h2>
        <table>
             <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Responsável</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($conta = $contas_a_receber->fetch_assoc()): ?>
                <tr>
                    <td><?= $conta['id'] ?></td>
                    <td><?= htmlspecialchars($conta['nome_usuario']) ?></td>
                    <td><?= htmlspecialchars($conta['responsavel']) ?></td>
                    <td>R$ <?= number_format($conta['valor'], 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($conta['data_vencimento'])) ?></td>
                    <td><?= ucfirst($conta['status']) ?></td>
                    <td>
                        <a href="../actions/editar_conta_receber.php?id=<?= $conta['id'] ?>&dev=1" class="btn btn-editar">Editar</a>
                        <a href="../actions/excluir_conta_receber.php?id=<?= $conta['id'] ?>&dev=1" class="btn btn-excluir" onclick="return confirm('Excluir esta conta permanentemente?')">Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn, .tab-content').forEach(el => el.classList.remove('active'));
            button.classList.add('active');
            document.getElementById(button.dataset.tab).classList.add('active');
        });
    });
</script>

</body>
</html>