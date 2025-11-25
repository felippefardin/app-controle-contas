<?php
require_once '../includes/session_init.php';

// Se já estiver logado E não for redirecionamento de sucesso recente
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

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Bootstrap (para modal melhorar visual) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0d0d0d, #1a1a1a);
            color: #eee;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: #1c1c1c;
            padding: 40px 35px;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.55);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #00bfff;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        label {
            color: #bbb;
            margin-bottom: 6px;
            font-size: 0.95rem;
        }

        input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #333;
            background-color: #262626;
            color: #fff;
            font-size: 1rem;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #00bfff;
            box-shadow: 0 0 5px rgba(0,191,255,0.4);
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #007bff, #00bfff);
            color: white;
            font-weight: bold;
            font-size: 1.05rem;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(0,191,255,0.45);
        }

        .links {
            text-align: center;
            margin-top: 18px;
            font-size: 0.9rem;
        }

        .links a {
            color: #8aa4b1;
            margin: 0 10px;
            text-decoration: none;
            transition: 0.2s;
        }

        .links a:hover {
            color: #00bfff;
            text-decoration: underline;
        }

        /* Alertas */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-danger {
            background-color: rgba(220,53,69,0.15);
            color: #ff6b6b;
            border: 1px solid #dc3545;
        }

        .alert-success {
            background-color: rgba(40,167,69,0.15);
            color: #2ecc71;
            border: 1px solid #28a745;
        }

        .loading-spinner {
            margin: 20px auto;
            width: 32px;
            height: 32px;
            border: 4px solid rgba(255,255,255,0.25);
            border-top: 4px solid #2ecc71;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 
            to { transform: rotate(360deg); } 
        }

        /* Link reativação */
        .reactivate-link {
            text-align: center;
            margin-top: 20px;
        }

        .reactivate-link a {
            color: #00bfff;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .reactivate-link a:hover {
            color: #4dd3ff;
        }
    </style>
</head>
<body>

<div class="login-container">

    <h2><i class="fa-solid fa-right-to-bracket"></i> Login</h2>

    <!-- Erro -->
    <?php if (isset($_SESSION['login_erro'])): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($_SESSION['login_erro']) ?>
        </div>
        <?php unset($_SESSION['login_erro']); ?>
    <?php endif; ?>

    <!-- Sucesso -->
    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1'): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i>
            Login registrado com sucesso
        </div>

        <p style="text-align:center; color:#aaa;">Acessando sistema...</p>
        <div class="loading-spinner"></div>

        <script>
            setTimeout(() => { window.location.href = 'selecionar_usuario.php'; }, 2000);
        </script>

    <?php else: ?>

        <form action="../actions/login.php" method="POST">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autofocus>

            <br><br>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Entrar</button>
        </form>

        <div class="links">
            <a href="esqueci_senha_login.php">Esqueci minha senha</a> |
            <a href="registro.php">Criar conta</a>
        </div>

        <div class="reactivate-link">
            <a href="#" data-bs-toggle="modal" data-bs-target="#modalReativarConta">
                <i class="fas fa-sync-alt"></i> Reativar Minha Assinatura
            </a>
        </div>

        <!-- Modal Reativar Conta -->
        <div class="modal fade" id="modalReativarConta" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="background:#1d1d1d; color:#eee; border-radius:12px;">
                    <div class="modal-header" style="border-bottom:1px solid #333;">
                        <h5 class="modal-title">Reativar Conta Suspensa</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-3">Informe seu e-mail para receber o link de reativação.</p>

                        <form action="../actions/solicitar_reativacao.php" method="POST">
                            <label>E-mail</label>
                            <input type="email" name="email_reativacao" class="form-control" required>

                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                Enviar Link de Reativação
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
