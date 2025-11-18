<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
$id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_logado = $_SESSION['usuario_id'];
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$is_admin = ($nivel === 'admin' || $nivel === 'master' || $nivel === 'proprietario');

// Segurança: Só pode editar se for admin ou se for o próprio usuário
if (!$is_admin && $id_usuario !== $id_logado) {
    header('Location: usuarios.php?erro=1&msg=Acesso negado');
    exit;
}

// Processa o Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $nova_senha = $_POST['senha'];
    
    // Define o perfil
    $perfil_novo = $_POST['perfil'] ?? 'padrao';

    // Se for o próprio usuário se editando, ele não pode mudar seu próprio nível de acesso
    if ($id_usuario === $id_logado) {
        // Mantém o perfil atual para não perder acesso de admin acidentalmente
        $stmt_p = $conn->prepare("SELECT perfil FROM usuarios WHERE id = ?");
        $stmt_p->bind_param("i", $id_usuario);
        $stmt_p->execute();
        $res_p = $stmt_p->get_result();
        $row_p = $res_p->fetch_assoc();
        $perfil_novo = $row_p['perfil']; 
    }

    if (!empty($nova_senha)) {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, cpf=?, senha=?, perfil=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $nome, $email, $telefone, $cpf, $hash, $perfil_novo, $id_usuario);
    } else {
        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, cpf=?, perfil=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $nome, $email, $telefone, $cpf, $perfil_novo, $id_usuario);
    }

    if ($stmt->execute()) {
        header('Location: usuarios.php?sucesso=1&msg=Dados atualizados');
        exit;
    } else {
        $erro = "Erro ao atualizar: " . $conn->error;
    }
}

// Busca dados atuais
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: usuarios.php?erro=1&msg=Usuário não encontrado');
    exit;
}

include('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
        }
        .card {
            background-color: #222;
            border-radius: 8px;
            border: 1px solid #444;
        }
        .card-header {
            padding: 15px 25px;
            border-bottom: 1px solid #444;
        }
        .card-body {
            padding: 25px;
        }
        h3 {
            margin: 0;
            color: #00bfff;
            font-size: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #444;
            margin-bottom: 15px;
            box-sizing: border-box;
            font-size: 16px;
            background-color: #333;
            color: #eee;
        }
        input:focus,
        select:focus {
            border-color: #0af;
            outline: none;
        }
        .btn {
            padding: 10px 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .btn-primary {
            background-color: #0af;
            color: white;
        }
        .btn-primary:hover {
            background-color: #008cdd;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .d-flex {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .alert-danger {
            background-color: #dc3545;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        /* Password toggle styles */
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 38%; /* Ajustado para alinhar melhor verticalmente */
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
            z-index: 10;
        }
        .toggle-password:hover {
            color: #00bfff;
        }
        /* Estilo para inputs desabilitados ou readonly */
        input:disabled, select:disabled, input[readonly] {
            background-color: #2a2a2a;
            color: #888;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-edit"></i> Editar Usuário</h3>
        </div>
        <div class="card-body">
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group mb-3">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="row">
                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label>CPF</label>
                            <input type="text" name="cpf" id="cpf" value="<?= htmlspecialchars($user['cpf'] ?? '') ?>">
                        </div>
                        <div style="flex: 1;">
                            <label>Telefone</label>
                            <input type="text" name="telefone" id="telefone" value="<?= htmlspecialchars($user['telefone'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- SELEÇÃO DE PERFIL (Admin Principal vs Padrão) -->
                <div class="form-group mb-3">
                    <label style="color: #00bfff;">Tipo de Acesso</label>
                    <?php if ($is_admin && $id_usuario != $id_logado): ?>
                        <select name="perfil">
                            <option value="padrao" <?= $user['perfil'] == 'padrao' ? 'selected' : '' ?>>Usuário Padrão</option>
                            <option value="admin" <?= $user['perfil'] == 'admin' ? 'selected' : '' ?>>Administrador Principal</option>
                        </select>
                        <small style="color: #aaa; display: block; margin-top: -10px; margin-bottom: 15px;">O Administrador Principal tem acesso total ao sistema.</small>
                    <?php else: ?>
                        <input type="hidden" name="perfil" value="<?= htmlspecialchars($user['perfil']) ?>">
                        <input type="text" value="<?= $user['perfil'] == 'admin' ? 'Administrador Principal' : 'Usuário Padrão' ?>" disabled>
                        <?php if ($id_usuario == $id_logado): ?>
                            <small style="color: #ffc107; display: block; margin-top: -10px; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Você não pode alterar seu próprio nível de acesso.</small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="form-group mb-4">
                    <label>Nova Senha <small style="color: #aaa; font-weight: normal;">(Deixe em branco para não alterar)</small></label>
                    <div class="password-wrapper">
                        <input type="password" id="senhaInput" name="senha" placeholder="******" autocomplete="new-password">
                        <i class="fas fa-eye toggle-password" onclick="toggleSenha()"></i>
                    </div>
                </div>

                <div class="d-flex">
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00', {reverse: true});
        $('#telefone').mask('(00) 00000-0000');
    });

    function toggleSenha() {
        const input = document.getElementById('senhaInput');
        const icon = document.querySelector('.toggle-password');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>