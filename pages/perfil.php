<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php'; // Importa o sistema de Flash Messages

// 1. Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

// Conexão com o Banco do Tenant (para dados do usuário)
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados.");
}

$id_usuario = $_SESSION['usuario_id'];
$nivel_acesso = $_SESSION['nivel_acesso'] ?? 'padrao';

$perfil_texto = ($nivel_acesso === 'admin' || $nivel_acesso === 'master' || $nivel_acesso === 'proprietario') 
    ? 'Administrador' 
    : 'Usuário Padrão';

// Inicializa variáveis
$nome = '';
$cpf = '';
$telefone = '';
$email = '';
$codigo_indicacao = ''; 
$foto_atual = 'default-profile.png';

// 2. Busca dados atuais do usuário no Banco do Tenant
$stmt = $conn->prepare("SELECT nome, cpf, telefone, email, foto FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
if ($stmt->execute()) {
    $stmt->bind_result($nome, $cpf, $telefone, $email, $foto_db);
    if ($stmt->fetch()) {
        if (!empty($foto_db)) {
            $foto_atual = $foto_db;
        }
    }
}
$stmt->close();

// 2.1 Busca o Código de Indicação no Banco Master
$connMaster = getMasterConnection();
if ($connMaster) {
    $stmtM = $connMaster->prepare("SELECT codigo_indicacao FROM usuarios WHERE email = ? LIMIT 1");
    $stmtM->bind_param("s", $email);
    if ($stmtM->execute()) {
        $stmtM->bind_result($cod_db_master);
        if ($stmtM->fetch()) {
            $codigo_indicacao = $cod_db_master;
        }
    }
    $stmtM->close();
}

$uploadDir = '../img/usuarios/';
$erro = '';

// Verifica mensagens via GET e converte para Flash Message
if (isset($_GET['mensagem'])) {
    set_flash_message('success', $_GET['mensagem']);
}
if (isset($_GET['erro'])) {
    set_flash_message('danger', $_GET['erro']);
}

// 3. Processa o formulário de atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lógica de exclusão é feita via JS + GET, aqui tratamos apenas atualização
    if (!isset($_POST['excluir_conta'])) {
        $nome_novo = trim($_POST['nome'] ?? '');
        $cpf_novo = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''); 
        $telefone_novo = trim($_POST['telefone'] ?? '');
        $email_novo = trim($_POST['email'] ?? '');
        $senha_nova = trim($_POST['senha'] ?? '');
        $senha_confirmar = trim($_POST['senha_confirmar'] ?? '');
        $novoNomeFoto = $foto_atual;

        // Upload de Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $novoNomeFoto = $id_usuario . '_' . time() . '.' . $fileExtension;
                $dest_path = $uploadDir . $novoNomeFoto;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    if ($foto_atual && $foto_atual !== 'default-profile.png' && file_exists($uploadDir . $foto_atual)) {
                        unlink($uploadDir . $foto_atual);
                    }
                } else $erro = "Erro ao salvar a imagem no servidor.";
            } else $erro = "Tipo de arquivo inválido. Use jpg, jpeg, png ou gif.";
        }

        if (!$erro) {
            if (empty($nome_novo) || empty($email_novo)) $erro = "Nome e E-mail são obrigatórios.";
            elseif (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) $erro = "E-mail inválido.";
            elseif (!empty($senha_nova) && $senha_nova !== $senha_confirmar) $erro = "As novas senhas não coincidem.";
            else {
                // Verifica duplicidade de email
                $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt_check->bind_param("si", $email_novo, $id_usuario);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) $erro = "Este e-mail já está em uso.";
                else {
                    if (!empty($senha_nova)) {
                        $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                        $stmt_update = $conn->prepare("UPDATE usuarios SET nome=?, cpf=?, telefone=?, email=?, senha=?, foto=? WHERE id=?");
                        $stmt_update->bind_param("ssssssi", $nome_novo, $cpf_novo, $telefone_novo, $email_novo, $senha_hash, $novoNomeFoto, $id_usuario);
                    } else {
                        $stmt_update = $conn->prepare("UPDATE usuarios SET nome=?, cpf=?, telefone=?, email=?, foto=? WHERE id=?");
                        $stmt_update->bind_param("sssssi", $nome_novo, $cpf_novo, $telefone_novo, $email_novo, $novoNomeFoto, $id_usuario);
                    }

                    if ($stmt_update->execute()) {
                        $_SESSION['usuario_nome'] = $nome_novo;
                        $_SESSION['usuario_foto'] = $novoNomeFoto;
                        
                        // Define mensagem de sucesso
                        set_flash_message('success', "Dados atualizados com sucesso!");
                        header("Location: perfil.php");
                        exit;
                    } else $erro = "Erro ao atualizar os dados no banco.";
                }
                $stmt_check->close();
            }
        }
        
        // Se houver erro no POST
        if ($erro) {
            set_flash_message('danger', $erro);
        }
    }
}

include('../includes/header.php');

// EXIBE O FLASH CARD CENTRALIZADO
display_flash_message();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ----------- ESTILO GERAL (NEON) ------------ */
        body {
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-container {
            max-width: 650px;
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
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Foto de Perfil */
        .profile-photo-container {
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }
        .profile-photo {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00bfff;
            box-shadow: 0 0 15px rgba(0, 191, 255, 0.4);
        }
        .profile-badge {
            margin-top: 10px;
            display: inline-block;
            background: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #00bfff;
            border: 1px solid #444;
        }

        /* Inputs */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #ccc; }
        .input-wrapper { position: relative; }

        .form-control {
            width: 100%;
            padding: 12px;
            padding-right: 40px; /* Espaço para ícone */
            border-radius: 6px;
            border: 1px solid #444;
            background: #252525;
            color: #fff;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus {
            outline: none;
            border-color: #00bfff;
            background-color: #2a2a2a;
            box-shadow: 0 0 15px rgba(0, 191, 255, 0.8), 0 0 5px rgba(0, 191, 255, 0.5) inset; 
        }

        /* Ícone Ver Senha */
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
        }
        .toggle-password:hover { color: #00bfff; }

        /* Botões */
        .btn-area {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-custom {
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: bold;
            font-size: 1rem;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #00bfff, #0099cc);
            color: #fff;
            flex-grow: 1;
            box-shadow: 0 4px 10px rgba(0, 191, 255, 0.2);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 191, 255, 0.4); }

        .btn-danger-custom {
            background: transparent;
            border: 1px solid #dc3545;
            color: #ff6b6b;
        }
        .btn-danger-custom:hover { background: rgba(220, 53, 69, 0.1); color: #ff4d4d; }

        .btn-secondary {
            background-color: #2c3e50;
            color: #fff;
            width: 94%;
            margin-top: 15px;
        }
        .btn-secondary:hover { background-color: #34495e; }

        .btn-suporte {
            background: linear-gradient(135deg, #6610f2, #520dc2);
            color: #fff;
            /* width: 100%; */
            margin-top: 10px;
            padding: 15px;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(102, 16, 242, 0.3);
        }
        .btn-suporte:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 16, 242, 0.5);
        }

        /* Input Group para Copiar */
        .input-group {
            display: flex;
        }
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .btn-outline-secondary {
            background-color: transparent;
            border: 1px solid #444;
            border-left: none;
            color: #00bfff;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
            padding: 0 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-outline-secondary:hover {
            background-color: rgba(0, 191, 255, 0.1);
        }
        .text-primary { color: #00bfff !important; }
        .fw-bold { font-weight: bold; }
        .text-center { text-align: center; }
        .mb-3 { margin-bottom: 1rem; }
        .text-muted { color: #aaa; font-size: 0.85rem; display: block; margin-top: 5px; }

    </style>
</head>
<body>

<div class="page-container">
    <h2><i class="fa-solid fa-user-pen"></i> Editar Perfil</h2>

    <form method="POST" enctype="multipart/form-data">
        
        <div class="profile-photo-container">
            <img src="../img/usuarios/<?= htmlspecialchars($foto_atual ?? 'default-profile.png', ENT_QUOTES, 'UTF-8') ?>" alt="Foto" class="profile-photo">
            <br>
            <div class="profile-badge">
                <?= $perfil_texto ?>
            </div>
        </div>

        <div class="form-group">
            <label for="foto">Alterar Foto:</label>
            <input type="file" name="foto" id="foto" class="form-control" accept="image/*" style="padding-bottom: 38px;">
        </div>

        <div class="form-group">
            <label>Nome Completo:</label>
            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($nome ?? '') ?>">
        </div>

        <div class="form-group">
            <label>E-mail:</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="codigo_indicacao" class="fw-bold">Seu Código de Indicação (Exclusivo)</label>
            <div class="input-group">
                <input type="text" class="form-control text-center fw-bold text-primary" id="codigo_indicacao" value="<?= htmlspecialchars($codigo_indicacao ?: 'Não disponível') ?>" readonly>
                <button class="btn-outline-secondary" type="button" onclick="copiarCodigo()">
                    <i class="fa-regular fa-copy"></i> Copiar
                </button>
            </div>
            <small class="text-muted">Compartilhe este código para indicar amigos.</small>
        </div>

        <div class="form-group">
            <label>CPF:</label>
            <input type="text" name="cpf" id="cpf" class="form-control" value="<?= htmlspecialchars($cpf ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Telefone:</label>
            <input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($telefone ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Nova Senha (Opcional):</label>
            <div class="input-wrapper">
                <input type="password" name="senha" id="senha" class="form-control" placeholder="Deixe em branco para manter">
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
            <a href="../actions/enviar_link_exclusao.php" class="btn-custom btn-danger-custom" onclick="return confirm('Tem certeza? Você receberá um e-mail para confirmar a exclusão.');">
                <i class="fa-solid fa-trash"></i> Excluir Conta
            </a>
            <button type="submit" class="btn-custom btn-submit">
                <i class="fa-solid fa-save"></i> Salvar Alterações
            </button>
        </div>

        <?php if ($nivel_acesso === 'admin' || $nivel_acesso === 'master' || $nivel_acesso === 'proprietario'): ?>
            <a href="minha_assinatura.php" class="btn-custom btn-secondary">
                <i class="fa-solid fa-gem"></i> Gerenciar Assinatura
            </a>
        <?php endif; ?>
        
        <a href="solicitar_meu_suporte.php" class="btn-custom btn-suporte">
            <i class="fas fa-headset"></i> Solicitar Suporte
        </a>

    </form>

</div> 

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    function copiarCodigo() {
        var copyText = document.getElementById("codigo_indicacao");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        alert("Código copiado: " + copyText.value);
    }

    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00', {reverse: true});
        $('#telefone').mask('(00) 00000-0000');
    });

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

<?php include('../includes/footer.php'); ?>