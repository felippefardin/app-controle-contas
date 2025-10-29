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
$usuarioId = $usuario_logado['id'];
$perfil = $usuario_logado['nivel_acesso'];

include('../includes/header.php');

// ✅ 3. SIMPLIFICA A QUERY PARA O MODELO SAAS
$where = ["id_usuario = " . intval($usuarioId)];

$sql = "SELECT * FROM categorias";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY tipo, nome ASC";

$result = $conn->query($sql);
$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

// Mensagens da sessão
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Categorias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 20px auto; background-color: #1f1f1f; padding: 25px; border-radius: 8px; }
        h2, h3 { text-align: center; color: #00bfff; margin-bottom: 20px; }
        .btn-add { background-color: #00bfff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; margin-bottom: 20px; }
        .btn-add:hover { background-color: #0099cc; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #333; text-align: left; }
        tr:hover { background-color: #2a2a2a; }
        .actions a { margin-right: 15px; color: #00bfff; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
        .actions a .fa-trash { color: #e74c3c; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7); justify-content: center; align-items: center; }
        .modal-content { background-color: #282828; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 191, 255, 0.3); width: 90%; max-width: 500px; position: relative; }
        .modal-content .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-content .close-btn:hover { color: #00bfff; }
        .modal-content form { display: flex; flex-direction: column; gap: 15px; }
        .modal-content form input, .modal-content form select, .modal-content form button { width: 100%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
        .modal-content form button { background-color: #27ae60; cursor: pointer; font-weight: bold; }
        .modal-content form button:hover { background-color: #218838; }

        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #fff; text-align: center; }
        .success { background-color: #28a745; }
        .error { background-color: #e74c3c; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-tags"></i> Gerenciar Categorias</h2>
    
    <?php if ($success_message): ?>
        <div class="message success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="message error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <button class="btn-add" onclick="abrirModal()">➕ Nova Categoria</button>

    <h3>Categorias de Despesa</h3>
    <table>
        <tbody>
            <?php foreach ($categorias as $categoria): if($categoria['tipo'] == 'despesa'): ?>
            <tr>
                <td><?= htmlspecialchars($categoria['nome']) ?></td>
                <td class="actions" style="text-align: right;">
                    <a href="#" onclick="abrirModal(<?= htmlspecialchars(json_encode($categoria)) ?>)"><i class="fa fa-pen"></i> Editar</a>
                    <a href="../actions/excluir_categoria.php?id=<?= $categoria['id'] ?>" onclick="return confirm('Atenção: Excluir uma categoria removerá a associação dela com todos os lançamentos existentes. Deseja continuar?')"><i class="fa fa-trash"></i> Excluir</a>
                </td>
            </tr>
            <?php endif; endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin-top: 30px;">Categorias de Receita</h3>
    <table>
        <tbody>
            <?php foreach ($categorias as $categoria): if($categoria['tipo'] == 'receita'): ?>
            <tr>
                <td><?= htmlspecialchars($categoria['nome']) ?></td>
                <td class="actions" style="text-align: right;">
                    <a href="#" onclick="abrirModal(<?= htmlspecialchars(json_encode($categoria)) ?>)"><i class="fa fa-pen"></i> Editar</a>
                    <a href="../actions/excluir_categoria.php?id=<?= $categoria['id'] ?>" onclick="return confirm('Atenção: Excluir uma categoria removerá a associação dela com todos os lançamentos existentes. Deseja continuar?')"><i class="fa fa-trash"></i> Excluir</a>
                </td>
            </tr>
            <?php endif; endforeach; ?>
        </tbody>
    </table>
</div>

<div id="categoriaModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="fecharModal()">&times;</span>
        <h3 id="modalTitle">Nova Categoria</h3>
        <form id="categoriaForm" action="../actions/salvar_categoria.php" method="POST">
            <input type="hidden" name="id" id="categoriaId">
            <input type="text" name="nome" id="categoriaNome" placeholder="Nome da Categoria" required>
            <select name="tipo" id="categoriaTipo" required>
                <option value="despesa">Despesa</option>
                <option value="receita">Receita</option>
            </select>
            <button type="submit">Salvar</button>
        </form>
    </div>
</div>

<script>
    function abrirModal(categoria = null) {
        if (categoria) {
            document.getElementById('modalTitle').innerText = 'Editar Categoria';
            document.getElementById('categoriaId').value = categoria.id;
            document.getElementById('categoriaNome').value = categoria.nome;
            document.getElementById('categoriaTipo').value = categoria.tipo;
        } else {
            document.getElementById('modalTitle').innerText = 'Nova Categoria';
            document.getElementById('categoriaForm').reset();
            document.getElementById('categoriaId').value = '';
        }
        document.getElementById('categoriaModal').style.display = 'flex';
    }

    function fecharModal() {
        document.getElementById('categoriaModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('categoriaModal');
        if (event.target == modal) {
            fecharModal();
        }
    }
</script>

</body>
</html>