<?php
require_once '../includes/session_init.php';

// Se já estiver logado E não for um redirecionamento de sucesso recente, vai direto
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true && !isset($_GET['sucesso'])) {
    header('Location: selecionar_usuario.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - App Controle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #1e1e1e;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 { color: #00bfff; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; color: #ccc; }
        input {
            width: 100%; padding: 12px; border-radius: 6px;
            border: 1px solid #444; background-color: #2c2c2c;
            color: #fff; box-sizing: border-box; font-size: 1rem;
        }
        input:focus { outline: none; border-color: #00bfff; }
        
        button {
            width: 100%; padding: 12px; border: none; border-radius: 6px;
            background: linear-gradient(135deg, #007bff, #00bfff);
            color: white; font-weight: bold; font-size: 1rem;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 191, 255, 0.4); }
        
        .links { margin-top: 20px; font-size: 0.9rem; }
        .links a { color: #888; text-decoration: none; margin: 0 10px; }
        .links a:hover { color: #fff; }

        /* Mensagens */
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .alert-danger { background-color: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid #dc3545; }
        .alert-success { background-color: rgba(40, 167, 69, 0.2); color: #2ecc71; border: 1px solid #28a745; }
        
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid #2ecc71;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="login-container">
    <h2><i class="fa-solid fa-right-to-bracket"></i> Login</h2>

    <?php if (isset($_SESSION['login_erro'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> 
            <?= htmlspecialchars($_SESSION['login_erro']) ?>
        </div>
        <?php unset($_SESSION['login_erro']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1'): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i> 
            Login registrado com sucesso
        </div>
        <p style="color: #aaa;">Acessando sistema...</p>
        <div class="loading-spinner"></div>
        
        <script>
            // Redireciona após 2 segundos (2000ms)
            setTimeout(function() {
                window.location.href = 'selecionar_usuario.php';
            }, 2000);
        </script>
    <?php else: ?>
        <form action="../actions/login.php" method="POST">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autofocus placeholder="seu@email.com">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="Sua senha">
            </div>

            <button type="submit">Entrar</button>
        </form>

        <div class="links">
            <a href="esqueci_senha_login.php">Esqueci minha senha</a>
            <span style="color: #444;">|</span>
            <a href="registro.php">Criar conta</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>