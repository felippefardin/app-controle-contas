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
$id_usuario = $usuario_logado['id'];

// Inclui o header após a lógica inicial
include('../includes/header.php');

// Buscar dados atuais do usuário no banco de dados do tenant
$stmt = $conn->prepare("SELECT nome, cpf, telefone, email, foto FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nome, $cpf, $telefone, $email, $foto_atual);
$stmt->fetch();
$stmt->close();

$mensagem = '';
$erro = '';

// Captura mensagens da URL
if (isset($_GET['mensagem'])) {
    $mensagem = htmlspecialchars($_GET['mensagem']);
}
if (isset($_GET['erro'])) {
    $erro = htmlspecialchars($_GET['erro']);
}

$uploadDir = '../img/usuarios/'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_novo = $_POST['nome'];
    $cpf_novo = $_POST['cpf'];
    $telefone_novo = $_POST['telefone'];
    $email_novo = $_POST['email'];
    $senha_nova = $_POST['senha'];
    $senha_confirmar = $_POST['senha_confirmar'];
    $novoNomeFoto = $foto_atual; // Manter a foto atual por padrão

    // Upload da foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $novoNomeFoto = $id_usuario . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadDir . $novoNomeFoto;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Apagar foto antiga se existir e não for padrão
                if ($foto_atual && $foto_atual !== 'default-profile.png' && file_exists($uploadDir . $foto_atual)) {
                    unlink($uploadDir . $foto_atual);
                }
            } else {
                $erro = "Erro ao salvar a imagem no servidor.";
            }
        } else {
            $erro = "Tipo de arquivo inválido. Use jpg, jpeg, png ou gif.";
        }
    }

    if (!$erro) {
        if (empty($nome_novo) || empty($email_novo)) {
            $erro = "Nome e E-mail são obrigatórios.";
        } elseif (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) {
            $erro = "E-mail inválido.";
        } elseif (!empty($senha_nova) && $senha_nova !== $senha_confirmar) {
            $erro = "As novas senhas não coincidem.";
        } else {
            // Verifica duplicidade de email
            $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt_check->bind_param("si", $email_novo, $id_usuario);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $erro = "Este e-mail já está em uso por outro usuário.";
            } else {
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
                    // ✅ ATUALIZA A SESSÃO CORRETA
                    $_SESSION['usuario_logado']['nome'] = $nome_novo;
                    $_SESSION['usuario_logado']['email'] = $email_novo;
                    $_SESSION['usuario_logado']['foto'] = $novoNomeFoto;

                    // Atualiza variáveis para exibir no formulário
                    $nome = $nome_novo; $cpf = $cpf_novo; $telefone = $telefone_novo; $email = $email_novo; $foto_atual = $novoNomeFoto;
                } else {
                    $erro = "Erro ao atualizar os dados.";
                }
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
    /* Seus estilos CSS permanecem os mesmos */
    body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 30px auto; padding: 20px; }
    h2 { color: #00bfff; border-bottom: 2px solid #00bfff; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.8rem; display: flex; align-items: center; gap: 10px; }
    form { background-color: #222; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); }
    label { display: block; margin-bottom: 6px; font-weight: bold; }
    input[type="text"], input[type="email"], input[type="password"], input[type="file"] { width: 100%; padding: 10px; border-radius: 6px; border: none; margin-bottom: 15px; box-sizing: border-box; font-size: 16px; background-color: #333; color: #eee; transition: 0.3s; }
    input:focus { outline: 2px solid #00bfff; }
    .profile-photo-preview { width: 150px; height: 150px; border: 2px solid #00bfff; object-fit: cover; margin-bottom: 15px; display: block; border-radius: 8px; }
    .password-wrapper { position: relative; display: flex; align-items: center; }
    .password-wrapper input { flex: 1; padding-right: 50px; margin-bottom: 15px; }
    .btn-padrao, button[type="submit"], .toggle-password, a.btn-padrao-link { border: none; padding: 10px 14px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background-color 0.3s ease, transform 0.2s; text-decoration: none; display: inline-block; text-align: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25); }
    button[type="submit"], .btn-padrao { background-color: #007BFF; color: white; }
    button[type="submit"]:hover, .btn-padrao:hover { background-color: #0056b3; transform: translateY(-2px); }
    .toggle-password { position: absolute; top: 35%; right: 6px; transform: translateY(-50%); cursor: pointer; color: white; font-size: 1.1rem; }
    .toggle-password:hover { color: #00bfff; }
    a.btn-padrao-link { background-color: #28a745; color: white; }
    a.btn-padrao-link:hover { background-color: #1e7e34; }
    .mensagem { background-color: #28a745; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
    .erro { background-color: #cc4444; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
  </style>
</head>
<body>

<div class="container">
   <h2><i class="fa-solid fa-user"></i> (Editar) Perfil</h2>
  

  <?php if ($mensagem): ?>
    <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <img src="../img/usuarios/<?= htmlspecialchars($foto_atual) ?>" alt="Foto do perfil" class="profile-photo-preview" />

  <form method="POST" enctype="multipart/form-data" autocomplete="off">
    <label for="foto">Alterar Foto de Perfil:</label>
    <input type="file" id="foto" name="foto" accept="image/*" />

    <label for="nome">Nome Completo:</label>
    <input id="nome" type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>

    <label for="cpf">CPF:</label>
    <input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>

    <label for="telefone">Telefone:</label>
    <input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required>

    <label for="email">Email:</label>
    <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label for="senha">Nova Senha (deixe em branco para manter a atual):</label>
    <div class="password-wrapper">
      <input type="password" id="senha" name="senha">
      <i class="fas fa-eye toggle-password" id="toggleSenha"></i>
    </div>

    <label for="senha_confirmar">Confirmar Nova Senha:</label>
    <div class="password-wrapper">
      <input type="password" id="senha_confirmar" name="senha_confirmar">
      <i class="fas fa-eye toggle-password"></i> </div>

    <div>
        <button type="submit">Salvar Alterações</button>
        
       

     <div style="margin-top: 20px; text-align: center;">
    <a href="../actions/enviar_link_exclusao.php" class="btn-padrao-link" style="background-color: #dc3545;" onclick="return confirm('Você tem certeza que deseja iniciar o processo de exclusão da sua conta? Um e-mail de confirmação será enviado.');">Excluir Minha Conta</a>
  </div>
  </form>

</div>

<script>
  // Script corrigido para funcionar em ambos os campos de senha
  document.querySelectorAll('.toggle-password').forEach(toggle => {
      toggle.addEventListener('click', function() {
          // Encontra o input de senha imediatamente antes do ícone (dentro do .password-wrapper)
          const input = this.previousElementSibling;
          if (input && (input.type === 'password' || input.type === 'text')) {
              const tipo = input.getAttribute('type') === 'password' ? 'text' : 'password';
              input.setAttribute('type', tipo);
              this.classList.toggle('fa-eye');
              this.classList.toggle('fa-eye-slash');
          }
      });
  });
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>