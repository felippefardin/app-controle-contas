<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario_principal'])) {
    header('Location: login.php');
    exit;
}

$mensagem_erro = '';
if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 'campos_vazios':
            $mensagem_erro = "Nome, e-mail e senha são obrigatórios.";
            break;
        case 'senha':
            $mensagem_erro = "As senhas não coincidem.";
            break;
        case 'duplicado_email':
            $mensagem_erro = "Este e-mail já está em uso.";
            break;
        case 'duplicado_cpf':
            $mensagem_erro = "Este CPF já está em uso.";
            break;
        default:
            $mensagem_erro = "Ocorreu um erro inesperado ao salvar o usuário.";
    }
}
include('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Usuário</title>
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
            max-width: 800px;
            /* Ajustado para 30px para padronizar com as outras telas */
            margin: 30px auto;
        }
        form {
            background-color: #222;
            padding: 25px;
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #eee;
            margin-bottom: 20px;
            border-bottom: 2px solid #0af;
            padding-bottom: 10px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .form-group, .form-row {
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #444;
            box-sizing: border-box;
            font-size: 16px;
            background-color: #333;
            color: #eee;
        }
        .form-control:focus {
            background-color: #333;
            color: #eee;
            border-color: #0af;
            box-shadow: none;
        }
        .btn {
            padding: 10px 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
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
            background-color: #555;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #444;
        }
        .alert {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            color: white;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .alert-danger {
            background-color: #cc4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <form action="../actions/add_usuario.php" method="POST">
            <h2><i class="fa-solid fa-user-plus"></i> Adicionar Novo Usuário</h2>

            <?php if (!empty($mensagem_erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="nome">Nome Completo*</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail*</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="cpf">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf">
                </div>
                <div class="form-group col-md-6">
                    <label for="telefone">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="senha">Senha*</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="senha_confirmar">Confirmar Senha*</label>
                    <input type="password" class="form-control" id="senha_confirmar" name="senha_confirmar" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Usuário</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function(){
            $('#cpf').mask('000.000.000-00', {reverse: true});
            $('#telefone').mask('(00) 00000-0000');
        });

        // Script para a mensagem desaparecer
        document.addEventListener('DOMContentLoaded', (event) => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500); // Tempo para a transição de opacidade
                }, 3000); // 3 segundos
            });
        });
    </script>
</body>
</html>