<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Incluído no início

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
// ❗️❗️ CORREÇÃO 1: Verificar se é 'true' e não apenas se 'isset' ❗️❗️
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO CORRETA
// ❗️❗️ CORREÇÃO 2: Ler 'usuario_id' e 'email' direto da SESSÃO ❗️❗️
// A variável $usuario_logado não é mais um array
$id_usuario_atual = $_SESSION['usuario_id'] ?? null; // ID do usuário do tenant
$email = $_SESSION['email'] ?? null; // Email do usuário (do master)

// ✅ 3. BUSCA TODOS OS USUÁRIOS (INCLUINDO A FOTO) DO CLIENTE (TENANT) ATUAL
// A consulta agora inclui o campo 'foto'
$sql = "SELECT id, nome, nivel_acesso, foto FROM usuarios ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result_usuarios = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Selecionar Usuário - App Controle de Contas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style> 
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        .selection-container {
            padding: 40px;
            background-color: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 191, 255, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        h2 {
            color: #00bfff;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #bbb;
        }
        select, input[type="password"] {
            width: 100%;
            padding: 12px;
            padding-right: 40px;
            border-radius: 5px;
            border: 1px solid #444;
            background-color: #333;
            color: #eee;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            top: 37px;
            right: 12px;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .toggle-password:hover {
            color: #00bfff;
        }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #00bfff;
            color: #121212;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0095cc;
        }
        .mensagem-erro {
            background-color: #dc3545; /* Vermelho */
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: white;
        }
        
        /* ✅ **NOVO ESTILO PARA MENSAGEM DE SUCESSO** */
        .mensagem-sucesso {
            background-color: #28a745; /* Verde */
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: white;
        }

        /* --- NOVOS ESTILOS PARA A LISTA DE USUÁRIOS --- */
        .user-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            max-height: 200px; /* Altura máxima para a lista rolável */
            overflow-y: auto; /* Adiciona barra de rolagem */
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #333;
        }
        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #444;
            cursor: pointer;
        }
        .user-item:last-child {
            border-bottom: none;
        }
        .user-item input[type="radio"] {
            margin-right: 15px;
            width: auto; /* Reseta o width 100% */
        }
        .user-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%; /* Foto redonda */
            margin-right: 10px;
            object-fit: cover; /* Garante que a imagem cubra o espaço */
            border: 1px solid #555;
        }
        /* O label agora é o container do clique */
        .user-item label {
            display: flex;
            align-items: center;
            width: 100%;
            margin: 0;
            font-weight: normal;
            color: #eee;
        }
        /* --- FIM DOS NOVOS ESTILOS --- */
    </style>
</head>
<body>
    <div class="selection-container">
        <h2>Selecionar Usuário</h2>
        
        <?php if (isset($_SESSION['erro_selecao'])): ?>
            <div class="mensagem-erro"><?= htmlspecialchars($_SESSION['erro_selecao']); ?></div>
            <?php unset($_SESSION['erro_selecao']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['sucesso_selecao'])): ?>
            <div class="mensagem-sucesso"><?= htmlspecialchars($_SESSION['sucesso_selecao']); ?></div>
            <?php unset($_SESSION['sucesso_selecao']); ?>
        <?php endif; ?>

        <form action="../actions/trocar_usuario.php" method="POST">
            
            <div class="form-group">
                <label>Acessar como:</label>
                <div class="user-list">
                    <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                        <?php
                            // Define a foto padrão caso o usuário não tenha uma
                            $foto_usuario = $usuario['foto'] ? $usuario['foto'] : 'default-profile.png';
                        ?>
                        <div class="user-item">
                            <input type="radio" name="usuario_id" id="user_<?= $usuario['id'] ?>" value="<?= $usuario['id'] ?>" <?= ($usuario['id'] === $id_usuario_atual) ? 'checked' : '' ?> required>
                            
                            <label for="user_<?= $usuario['id'] ?>">
                                <img src="../img/usuarios/<?= htmlspecialchars($foto_usuario) ?>" alt="Foto de <?= htmlspecialchars($usuario['nome']) ?>">
                                <span>
                                    <?= htmlspecialchars($usuario['nome']) ?>
                                    <?= ($usuario['nivel_acesso'] === 'proprietario') ? ' (Principal)' : '' ?>
                                </span>
                            </label>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($result_usuarios->num_rows === 0): ?>
                        <div style="padding: 15px; text-align: center; color: #aaa;">
                            Nenhum usuário encontrado neste tenant.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha do usuário selecionado:</label>
                <input type="password" name="senha" id="senha" required>
                <i class="fas fa-eye toggle-password" id="toggleSenha"></i>
            </div>
            <button type="submit">Acessar Sistema</button>
        </form>
       <a href="../actions/enviar_link_email_perfil.php" 
          class="btn-padrao-link" 
          style="background-color: #17a2b8; color: white; margin-left: 10px; display: inline-block; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-top: 15px;" 
          
          onclick="return confirm('Deseja enviar um link de redefinição de senha para o seu e-mail cadastrado (<?= htmlspecialchars($email ?? 'email.nao.encontrado@error.com') ?>)?');">
          Redefinir por E-mail
       </a>
    </div>

    
    </div>

    <script>
    const toggleSenha = document.getElementById('toggleSenha');
    const inputSenha = document.getElementById('senha');

    toggleSenha.addEventListener('click', () => {
      const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
      inputSenha.setAttribute('type', tipo);
      toggleSenha.classList.toggle('fa-eye');
      toggleSenha.classList.toggle('fa-eye-slash');
    });
    </script>
</body>
</html>