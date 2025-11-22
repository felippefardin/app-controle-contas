<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica Permissão
$nivel = $_SESSION['nivel_acesso'] ?? 'padrao';
$id_logado = $_SESSION['usuario_id'];

// Se não for admin, só pode editar a si mesmo
if ($nivel !== 'admin' && $nivel !== 'master' && $nivel !== 'proprietario') {
    // Se tentar acessar ID de outro, bloqueia
    if (isset($_GET['id']) && $_GET['id'] != $id_logado) {
        header('Location: usuarios.php?erro=1&msg=Acesso negado');
        exit;
    }
}

// Busca dados do usuário
$conn = getTenantConnection();
$id_usuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_usuario) {
    header('Location: usuarios.php?erro=1&msg=ID inválido');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ----------- ESTILO GERAL (NEON) ------------ */
        body {
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-container {
            max-width: 600px;
            margin: 40px auto;
            background: #1e1e1e;
            padding: 35px;
            border-radius: 12px;
            color: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            border: 1px solid #333;
        }

        .page-container h2 {
            color: #00bfff;
            border-bottom: 1px solid #00bfff;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ccc;
        }

        /* Container relativo para posicionar o ícone do olho */
        .input-wrapper { position: relative; }

        .form-control, select {
            width: 100%;
            padding: 12px;
            padding-right: 40px; /* Espaço para o ícone */
            border-radius: 6px;
            border: 1px solid #444;
            background: #252525;
            color: #fff;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease-in-out;
        }

        /* --- EFEITO NEON NO FOCUS --- */
        .form-control:focus, select:focus {
            outline: none;
            border-color: #00bfff;
            background-color: #2a2a2a;
            box-shadow: 0 0 15px rgba(0, 191, 255, 0.8), 0 0 5px rgba(0, 191, 255, 0.5) inset; 
        }

        /* Ícone de Ver Senha */
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
            z-index: 10;
        }
        .toggle-password:hover { color: #00bfff; }

        /* Botões */
        .btn-area {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }

        .btn-custom {
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: bold;
            font-size: 1rem;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-back { background: #444; color: #ddd; }
        .btn-back:hover { background: #555; color: #fff; }

        .btn-submit {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #000;
            flex-grow: 1;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.2);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 193, 7, 0.4);
        }

        /* Alertas */
        .alert-custom {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            background: rgba(220, 53, 69, 0.2); 
            border: 1px solid #dc3545; 
            color: #ff6b6b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="page-container">
    <h2 style="color: #ffc107; border-color: #ffc107;">
        <i class="fa-solid fa-user-pen"></i> Editar Usuário
    </h2>

    <?php if (isset($_GET['erro'])): ?>
        <div class="alert-custom">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($_GET['msg'] ?? 'Erro desconhecido') ?>
        </div>
    <?php endif; ?>

    <form action="../actions/editar_usuario.php" method="POST">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="form-group">
            <label>Nome Completo:</label>
            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($user['nome']) ?>">
        </div>

        <div class="form-group">
            <label>E-mail:</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <div class="form-group">
            <label>CPF:</label>
            <input type="text" name="cpf" id="cpf" class="form-control" value="<?= htmlspecialchars($user['cpf'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Nível de Acesso:</label>
            <select name="nivel" class="form-control">
                <option value="padrao" <?= ($user['nivel_acesso'] ?? $user['perfil']) === 'padrao' ? 'selected' : '' ?>>Padrão (Acesso Restrito)</option>
                <option value="admin" <?= ($user['nivel_acesso'] ?? $user['perfil']) === 'admin' ? 'selected' : '' ?>>Administrador (Acesso Total)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Nova Senha (Opcional):</label>
            <div class="input-wrapper">
                <input type="password" name="senha" id="senha" class="form-control" placeholder="Deixe em branco para manter a atual">
                <i class="fa-solid fa-eye toggle-password" onclick="togglePass('senha', this)"></i>
            </div>
        </div>

        <div class="form-group">
            <label>Confirmar Nova Senha:</label>
            <div class="input-wrapper">
                <input type="password" name="senha_confirmar" id="senha_confirmar" class="form-control" placeholder="Repita se for alterar">
                <i class="fa-solid fa-eye toggle-password" onclick="togglePass('senha_confirmar', this)"></i>
            </div>
        </div>

        <div class="btn-area">
            <a href="usuarios.php" class="btn-custom btn-back">
                <i class="fa-solid fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn-custom btn-submit">
                <i class="fa-solid fa-save"></i> Atualizar Dados
            </button>
        </div>

    </form>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    // Máscara CPF
    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00');
    });

    // Função Toggle Senha
    function togglePass(fieldId, icon) {
        const input = document.getElementById(fieldId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>

<?php
$conn->close();
include('../includes/footer.php'); 
?>