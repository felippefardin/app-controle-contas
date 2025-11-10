<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

$usuario_logado = $_SESSION['usuario_logado'];
$id_usuario = $usuario_logado['id'];

include('../includes/header.php');

$stmt = $conn->prepare("SELECT nome, cpf, telefone, email, foto FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nome, $cpf, $telefone, $email, $foto_atual);
$stmt->fetch();
$stmt->close();

$mensagem = $erro = '';

if (isset($_GET['mensagem'])) $mensagem = htmlspecialchars($_GET['mensagem']);
if (isset($_GET['erro'])) $erro = htmlspecialchars($_GET['erro']);

$uploadDir = '../img/usuarios/';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_novo = $_POST['nome'];
    $cpf_novo = $_POST['cpf'];
    $telefone_novo = $_POST['telefone'];
    $email_novo = $_POST['email'];
    $senha_nova = $_POST['senha'];
    $senha_confirmar = $_POST['senha_confirmar'];
    $novoNomeFoto = $foto_atual;

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
                    $mensagem = "Dados atualizados com sucesso!";
                    $_SESSION['usuario_logado']['nome'] = $nome_novo;
                    $_SESSION['usuario_logado']['email'] = $email_novo;
                    $_SESSION['usuario_logado']['foto'] = $novoNomeFoto;

                    $nome = $nome_novo; 
                    $cpf = $cpf_novo; 
                    $telefone = $telefone_novo; 
                    $email = $email_novo; 
                    $foto_atual = $novoNomeFoto;
                } else $erro = "Erro ao atualizar os dados.";
            }
            $stmt_check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Editar Perfil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
body {
    background-color: #121212;
    color: #eee;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 600px;
    margin: 50px auto;
    padding: 30px;
    background-color: #1e1e1e;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    text-align: center;
}

h2 {
    color: #00bfff;
    border-bottom: 2px solid #00bfff;
    padding-bottom: 10px;
    margin-bottom: 30px;
    font-size: 1.8rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.profile-photo-preview {
    width: 160px;
    height: 160px;
    border: 3px solid #00bfff;
    object-fit: cover;
    margin: 0 auto 25px auto;
    display: block;
    border-radius: 50%;
}

form {
    background-color: #222;
    padding: 25px 30px;
    border-radius: 8px;
    text-align: left;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
}

input[type="text"],
input[type="email"],
input[type="password"],
input[type="file"],
select {
    width: 100%;
    padding: 12px;
    border-radius: 6px;
    border: none;
    margin-bottom: 18px;
    box-sizing: border-box;
    font-size: 16px;
    background-color: #333;
    color: #eee;
    transition: 0.3s;
}

input:focus, select:focus {
    outline: 2px solid #00bfff;
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-wrapper input {
    flex: 1;
    padding-right: 40px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    color: #ccc;
    cursor: pointer;
}

/* --- Grupo de Botões --- */
.button-group {
    display: flex;
    gap: 15px;
    justify-content: space-between;
    margin-top: 20px;
}

.button-group button,
.button-group a {
    flex: 1;
    padding: 14px 0;
    border: none;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
    font-size: 16px;
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
}

/* --- Botão Salvar --- */
.btn-salvar {
    background: linear-gradient(135deg, #007bff, #00bfff);
    box-shadow: 0 3px 10px rgba(0, 191, 255, 0.3);
}
.btn-salvar:hover {
    background: linear-gradient(135deg, #009bff, #1ec8ff);
    box-shadow: 0 4px 15px rgba(0, 191, 255, 0.5);
    transform: translateY(-1px);
}

/* --- Botão Excluir --- */
.btn-excluir {
    background: linear-gradient(135deg, #ff3b3b, #b91d1d);
    box-shadow: 0 3px 10px rgba(255, 59, 59, 0.3);
}
.btn-excluir:hover {
    background: linear-gradient(135deg, #ff4d4d, #d62323);
    box-shadow: 0 4px 15px rgba(255, 59, 59, 0.5);
    transform: translateY(-1px);
}

/* Ícones dentro dos botões */
.btn-salvar i, .btn-excluir i {
    margin-right: 8px;
}

/* Responsivo */
@media (max-width: 600px) {
    .button-group {
        flex-direction: column;
    }
}

/* Card de assinatura */
.card-body {
    background-color: #1f1f1f;
    padding: 20px;
    border-radius: 8px;
    margin-top: 25px;
    border-left: 5px solid #00bfff;
    text-align: center;
}

.card-title {
    color: #00bfff;
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.card-text {
    color: #ccc;
    margin-bottom: 15px;
}

.btn-primary {
    background-color: #28a745;
    color: white;
    padding: 12px 25px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
}
.btn-primary:hover { background-color: #1e7e34; }

.mensagem, .erro {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}
.mensagem { background-color: #28a745; }
.erro { background-color: #cc4444; }
</style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-user"></i> Editar Perfil</h2>

    <?php if ($mensagem): ?><div class="mensagem"><?= $mensagem ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="erro"><?= $erro ?></div><?php endif; ?>

    <img src="../img/usuarios/<?= htmlspecialchars($foto_atual) ?>" alt="Foto do perfil" class="profile-photo-preview" />

    <form method="POST" enctype="multipart/form-data" autocomplete="off">
        <label for="foto">Alterar Foto:</label>
        <input type="file" id="foto" name="foto" accept="image/*">

        <label for="nome">Nome:</label>
        <input id="nome" type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

        <label for="cpf">CPF:</label>
        <input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>

        <label for="telefone">Telefone:</label>
        <input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required>

        <label for="email">Email:</label>
        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="senha">Nova Senha:</label>
        <div class="password-wrapper">
            <input type="password" id="senha" name="senha">
            <i class="fas fa-eye toggle-password"></i>
        </div>

        <label for="senha_confirmar">Confirmar Nova Senha:</label>
        <div class="password-wrapper">
            <input type="password" id="senha_confirmar" name="senha_confirmar">
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

        <div class="card-body">
            <h5 class="card-title"><i class="fa-solid fa-gem"></i> Minha Assinatura</h5>
            <p class="card-text">Gerencie seu plano, pagamentos e dados cadastrais.</p>
            <a href="minha_assinatura.php" class="btn-primary">Gerenciar Assinatura</a>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
        const input = icon.previousElementSibling;
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye-slash');
    });
});
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
