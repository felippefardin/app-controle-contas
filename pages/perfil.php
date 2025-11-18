<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados.");
}

$id_usuario = $_SESSION['usuario_id'];
$nivel_acesso = $_SESSION['nivel_acesso'] ?? 'padrao';

// Define o texto do perfil para exibição
$perfil_texto = ($nivel_acesso === 'admin' || $nivel_acesso === 'master' || $nivel_acesso === 'proprietario') 
    ? 'Principal (Administrador)' 
    : 'Usuário Padrão';

include('../includes/header.php');

// 2. Busca dados atuais do usuário
$stmt = $conn->prepare("SELECT nome, cpf, telefone, email, foto FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nome, $cpf, $telefone, $email, $foto_atual);
$stmt->fetch();
$stmt->close();

$mensagem = $erro = '';
$uploadDir = '../img/usuarios/';

// 3. Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_novo = trim($_POST['nome'] ?? '');
    $cpf_novo = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''); // Limpa CPF
    $telefone_novo = trim($_POST['telefone'] ?? '');
    $email_novo = trim($_POST['email'] ?? '');
    $senha_nova = trim($_POST['senha'] ?? '');
    $senha_confirmar = trim($_POST['senha_confirmar'] ?? '');
    $novoNomeFoto = $foto_atual;

    // Upload de foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $novoNomeFoto = $id_usuario . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadDir . $novoNomeFoto;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Apaga foto antiga se não for a padrão
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
            // Verifica se e-mail já existe (exceto para o próprio usuário)
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
                    // Atualiza sessão se necessário
                    $_SESSION['usuario_nome'] = $nome_novo;
                    $_SESSION['usuario_foto'] = $novoNomeFoto;

                    // Atualiza variáveis locais para exibir
                    $nome = $nome_novo;
                    $cpf = $cpf_novo;
                    $telefone = $telefone_novo;
                    $email = $email_novo;
                    $foto_atual = $novoNomeFoto;

                    $_SESSION['perfil_msg'] = "Dados atualizados com sucesso!";
                } else $erro = "Erro ao atualizar os dados.";
            }
            $stmt_check->close();
        }
    }

    if ($erro) $_SESSION['perfil_erro'] = $erro;
    header("Location: perfil.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Editar Perfil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<style>
body { background-color: #121212; color: #eee; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }
.container { max-width: 600px; margin: 50px auto; padding: 30px; background-color: #1e1e1e; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4); text-align: center; animation: fadeIn 0.4s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
h2 { color: #00bfff; border-bottom: 2px solid #00bfff; padding-bottom: 10px; margin-bottom: 30px; font-size: 1.8rem; display: flex; justify-content: center; align-items: center; gap: 10px; }
.profile-photo-preview { width: 160px; height: 160px; border: 3px solid #00bfff; object-fit: cover; margin: 0 auto 25px auto; display: block; border-radius: 50%; transition: 0.3s; }
.profile-photo-preview:hover { transform: scale(1.05); }
form { background-color: #222; padding: 25px 30px; border-radius: 8px; text-align: left; }
label { display: block; margin-bottom: 6px; font-weight: bold; }
input[type="text"], input[type="email"], input[type="password"], input[type="file"] { width: 100%; padding: 12px; border-radius: 6px; border: none; margin-bottom: 18px; font-size: 16px; background-color: #333; color: #eee; transition: 0.3s; }
input:focus { outline: 2px solid #00bfff; }
.password-wrapper { position: relative; display: flex; align-items: center; }
.password-wrapper input { flex: 1; padding-right: 40px; }
.toggle-password { position: absolute; right: 10px; color: #ccc; cursor: pointer; }

/* Badge de Perfil */
.badge-perfil {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: bold;
    margin-bottom: 20px;
    width: 100%;
    text-align: center;
    background-color: <?= ($nivel_acesso === 'admin' || $nivel_acesso === 'master') ? '#005f73' : '#4a4e69' ?>;
    color: #fff;
    border: 1px solid #ffffff22;
}

.button-group { display: flex; gap: 15px; justify-content: space-between; margin-top: 20px; }
.button-group button, .button-group a { flex: 1; padding: 14px 0; border: none; border-radius: 8px; text-align: center; font-weight: bold; font-size: 16px; color: #fff; cursor: pointer; text-decoration: none; transition: all 0.3s ease; }
.btn-salvar { background: linear-gradient(135deg, #007bff, #00bfff); box-shadow: 0 3px 10px rgba(0, 191, 255, 0.3); }
.btn-salvar:hover { transform: translateY(-1px); background: linear-gradient(135deg, #009bff, #1ec8ff); }
.btn-excluir { background: linear-gradient(135deg, #ff3b3b, #b91d1d); box-shadow: 0 3px 10px rgba(255, 59, 59, 0.3); }
.btn-excluir:hover { transform: translateY(-1px); background: linear-gradient(135deg, #ff4d4d, #d62323); }
.card-body { background-color: #1f1f1f; padding: 20px; border-radius: 8px; margin-top: 25px; border-left: 5px solid #00bfff; text-align: center; }
.card-title { color: #00bfff; font-size: 1.2rem; margin-bottom: 10px; }
.btn-primary { background-color: #28a745; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: bold; }
.btn-primary:hover { background-color: #1e7e34; }
@media (max-width: 600px) { .button-group { flex-direction: column; } }
</style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-user"></i> Editar Perfil</h2>

    <img src="../img/usuarios/<?= htmlspecialchars($foto_atual ?? 'default-profile.png', ENT_QUOTES, 'UTF-8') ?>" alt="Foto do perfil" class="profile-photo-preview" />

    <form method="POST" enctype="multipart/form-data" autocomplete="off">
        
        <div class="badge-perfil">
            <i class="fa-solid <?= ($nivel_acesso === 'admin' || $nivel_acesso === 'master') ? 'fa-user-shield' : 'fa-user' ?>"></i>
            Tipo de Conta: <?= $perfil_texto ?>
        </div>

        <label for="foto">Alterar Foto:</label>
        <input type="file" id="foto" name="foto" accept="image/*">

        <label for="nome">Nome:</label>
        <input id="nome" type="text" name="nome" value="<?= htmlspecialchars($nome ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" required>

        <label for="cpf">CPF:</label>
        <input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($cpf ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">

        <label for="telefone">Telefone:</label>
        <input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel">

        <label for="email">Email:</label>
        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>

        <label for="senha">Nova Senha:</label>
        <div class="password-wrapper">
            <input type="password" id="senha" name="senha" autocomplete="new-password" placeholder="Deixe em branco para manter">
            <i class="fas fa-eye toggle-password"></i>
        </div>

        <label for="senha_confirmar">Confirmar Nova Senha:</label>
        <div class="password-wrapper">
            <input type="password" id="senha_confirmar" name="senha_confirmar" autocomplete="new-password">
            <i class="fas fa-eye toggle-password"></i>
        </div>

        <div class="button-group">
            <button type="submit" class="btn-salvar">
                <i class="fa-solid fa-save"></i> Salvar Alterações
            </button>

            <a href="../actions/enviar_link_exclusao.php" class="btn-excluir" onclick="return confirm('Você tem certeza que deseja iniciar o processo de exclusão da sua conta?');">
                <i class="fa-solid fa-trash"></i> Excluir Conta
            </a>
        </div>

        <?php if ($nivel_acesso === 'admin' || $nivel_acesso === 'master' || $nivel_acesso === 'proprietario'): ?>
        <div class="card-body">
            <h5 class="card-title"><i class="fa-solid fa-gem"></i> Minha Assinatura</h5>
            <p class="card-text">Gerencie seu plano, pagamentos e dados cadastrais.</p>
            <a href="minha_assinatura.php" class="btn-primary">Gerenciar Assinatura</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00', {reverse: true});
        $('#telefone').mask('(00) 00000-0000');
    });

    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = icon.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye-slash');
        });
    });

    <?php if (!empty($_SESSION['perfil_msg'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Sucesso!',
        text: '<?= addslashes($_SESSION['perfil_msg']) ?>',
        confirmButtonColor: '#3085d6'
    });
    <?php unset($_SESSION['perfil_msg']); endif; ?>

    <?php if (!empty($_SESSION['perfil_erro'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Erro!',
        text: '<?= addslashes($_SESSION['perfil_erro']) ?>',
        confirmButtonColor: '#d33'
    });
    <?php unset($_SESSION['perfil_erro']); endif; ?>
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>