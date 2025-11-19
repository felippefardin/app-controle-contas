<?php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('America/Sao_Paulo');

$erro = '';
$sucesso = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$token_valido = false;
$nome_usuario = '';
$email_usuario = '';

// Garante conexão com o banco MASTER
$conn = getMasterConnection();
if (!$conn) {
    die("Erro crítico: Não foi possível conectar ao banco de dados principal.");
}

// 1. VERIFICA SE O TOKEN É VÁLIDO (Ao carregar a página ou enviar o formulário)
if (!empty($token)) {
    // Busca usuário pelo token de reset válido
    $stmt = $conn->prepare("SELECT id, nome, email, tenant_id FROM usuarios WHERE token_reset = ? AND token_expira_em > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $token_valido = true;
        $stmt->bind_result($id_usuario, $nome_usuario, $email_usuario, $tenant_id_str);
        $stmt->fetch();
    } else {
        $erro = "Este link de redefinição é inválido ou já expirou. Solicite uma nova recuperação.";
    }
    $stmt->close();
} else {
    $erro = "Token de segurança não encontrado.";
}

// 2. PROCESSA A TROCA DE SENHA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    if (empty($senha) || empty($senha_confirmar)) {
        $erro = "Preencha todos os campos.";
    } elseif ($senha !== $senha_confirmar) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // A. Atualiza no Banco MASTER e limpa o token
        $stmt_up = $conn->prepare("UPDATE usuarios SET senha = ?, token_reset = NULL, token_expira_em = NULL WHERE id = ?");
        $stmt_up->bind_param("si", $senha_hash, $id_usuario);

        if ($stmt_up->execute()) {
            
            // B. Sincroniza com o Banco do TENANT (Cliente)
            if (!empty($tenant_id_str)) {
                // Busca credenciais do tenant
                $stmt_creds = $conn->prepare("SELECT db_host, db_user, db_password, db_database FROM tenants WHERE tenant_id = ?");
                $stmt_creds->bind_param("s", $tenant_id_str);
                $stmt_creds->execute();
                $res_creds = $stmt_creds->get_result();
                
                if ($tenant_data = $res_creds->fetch_assoc()) {
                    try {
                        $connTenant = new mysqli(
                            $tenant_data['db_host'], 
                            $tenant_data['db_user'], 
                            $tenant_data['db_password'], 
                            $tenant_data['db_database']
                        );
                        
                        if (!$connTenant->connect_error) {
                            // Atualiza a senha no tenant
                            $stmt_t = $connTenant->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
                            $stmt_t->bind_param("ss", $senha_hash, $email_usuario);
                            $stmt_t->execute();
                            $stmt_t->close();
                            $connTenant->close();
                        }
                    } catch (Exception $e) {
                        error_log("Erro ao sincronizar senha no tenant: " . $e->getMessage());
                    }
                }
                $stmt_creds->close();
            }

            // C. Envia email de confirmação (Opcional)
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'] ?? '';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';
                $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
                $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
                $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? '', $_ENV['MAIL_FROM_NAME'] ?? 'App Controle');
                $mail->addAddress($email_usuario);

                $mail->isHTML(true);
                $mail->Subject = 'Senha Alterada com Sucesso';
                $mail->Body    = "
                    <div style='font-family: Arial; color: #333;'>
                        <h2>Senha Alterada</h2>
                        <p>Olá, <strong>$nome_usuario</strong>.</p>
                        <p>Sua senha foi alterada com sucesso.</p>
                    </div>
                ";
                $mail->send();
            } catch (Exception $e) {
                // Falha no email não deve impedir o sucesso da operação
                error_log("Erro ao enviar e-mail de confirmação: {$mail->ErrorInfo}");
            }

            $sucesso = "Senha alterada com sucesso! Você já pode fazer login.";
            $token_valido = false; // Impede reenvio do formulário
        } else {
            $erro = "Erro ao atualizar senha no banco de dados principal.";
        }
        $stmt_up->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Nova Senha</title>
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
    form {
        padding: 40px;
        background-color: #1e1e1e;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 191, 255, 0.2);
        width: 100%;
        max-width: 400px;
        text-align: left;
    }
    h2 {
        color: #00bfff;
        margin-bottom: 25px;
        text-align: center;
    }
    p.info {
        text-align: center;
        color: #aaa;
        margin-bottom: 20px;
    }
    label {
        display: block;
        margin-bottom: 8px;
        color: #bbb;
    }
    input[type="password"] {
        width: 100%;
        padding: 12px;
        border-radius: 5px;
        border: 1px solid #444;
        background-color: #333;
        color: #eee;
        box-sizing: border-box;
        margin-bottom: 15px;
        transition: border 0.3s;
    }
    input[type="password"]:focus {
        border-color: #00bfff;
        outline: none;
    }
    .password-container {
        position: relative;
    }
    .toggle-password {
        position: absolute;
        top: 12px;
        right: 12px;
        color: #aaa;
        cursor: pointer;
    }
    .senha-forca {
        height: 6px;
        background: #444;
        border-radius: 4px;
        margin-top: -10px;
        margin-bottom: 15px;
        overflow: hidden;
    }
    .senha-forca > div {
        height: 100%;
        width: 0;
        transition: width 0.3s ease, background-color 0.3s ease;
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
        margin-top: 10px;
    }
    button:hover {
        background-color: #0095cc;
    }
    .msg-erro { 
        background-color: rgba(220, 53, 69, 0.1);
        border: 1px solid #dc3545;
        color: #ff6b6b; 
        padding: 10px;
        border-radius: 5px;
        text-align: center; 
        margin-bottom: 15px; 
    }
    .msg-sucesso { 
        background-color: rgba(40, 167, 69, 0.1);
        border: 1px solid #28a745;
        color: #2ecc71; 
        padding: 10px;
        border-radius: 5px;
        text-align: center; 
        margin-bottom: 15px; 
    }
    a { color: #00bfff; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .center-link { text-align: center; display: block; margin-top: 15px; }
</style>
</head>
<body>

<div style="width: 100%; max-width: 400px;">
    <?php if ($sucesso): ?>
        <form>
            <h2>Sucesso!</h2>
            <div class="msg-sucesso"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($sucesso) ?></div>
            <div class="center-link"><a href="login.php" style="font-size: 1.1rem;">Ir para Login</a></div>
        </form>

    <?php elseif ($token_valido): ?>
        <form method="POST" novalidate>
            <h2>Redefinir Senha</h2>
            <p class="info">Defina uma nova senha para <strong><?= htmlspecialchars($email_usuario) ?></strong></p>

            <?php if ($erro): ?>
                <div class="msg-erro"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label for="senha">Nova senha:</label>
            <div class="password-container">
                <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres">
                <i class="fas fa-eye toggle-password" onclick="toggleSenha('senha')"></i>
            </div>
            <div class="senha-forca"><div id="barra-forca"></div></div>

            <label for="senha_confirmar">Confirmar senha:</label>
            <div class="password-container">
                <input type="password" id="senha_confirmar" name="senha_confirmar" required placeholder="Repita a nova senha">
                <i class="fas fa-eye toggle-password" onclick="toggleSenha('senha_confirmar')"></i>
            </div>

            <button type="submit">Salvar Nova Senha</button>
            <div class="center-link"><a href="login.php">Cancelar</a></div>
        </form>

    <?php else: ?>
        <form>
            <h2>Link Inválido</h2>
            <div class="msg-erro"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($erro) ?></div>
            <div class="center-link"><a href="esqueci_senha_login.php">Solicitar novo link</a></div>
            <div class="center-link"><a href="login.php">Voltar para Login</a></div>
        </form>
    <?php endif; ?>
</div>

<script>
  function toggleSenha(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling;
    if (input.type === "password") {
      input.type = "text";
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = "password";
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }

  const senhaInput = document.getElementById('senha');
  const barraForca = document.getElementById('barra-forca');

  if(senhaInput) {
      senhaInput.addEventListener('input', function() {
        const valor = senhaInput.value;
        let forca = 0;
        if (valor.length >= 6) forca++;
        if (/[A-Z]/.test(valor)) forca++;
        if (/[0-9]/.test(valor)) forca++;
        if (/[\W]/.test(valor)) forca++;

        if (forca === 0) {
            barraForca.style.width = '0';
        } else if (forca <= 2) {
            barraForca.style.width = '33%';
            barraForca.style.backgroundColor = '#dc3545';
        } else if (forca === 3) {
            barraForca.style.width = '66%';
            barraForca.style.backgroundColor = '#ffc107';
        } else {
            barraForca.style.width = '100%';
            barraForca.style.backgroundColor = '#28a745';
        }
      });
  }
</script>

</body>
</html>