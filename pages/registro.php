<?php 
// Remova o include do header se ele iniciar a sessão, 
// pois o session_start() está em session_init.php
// include('../includes/header.php'); 

// Inicia a sessão para poder exibir erros de registro
require_once __DIR__ . '/../includes/session_init.php';

$erro = $_SESSION['erro_registro'] ?? '';
unset($_SESSION['erro_registro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Cadastro - App Controle de Contas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<style>
  body {
    background-color: #121212;
    color: #eee;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
  }

  .form-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 120px);
    padding: 20px;
    box-sizing: border-box;
  }

  form {
    background-color: #1f1f1f;
    padding: 25px 35px;
    border-radius: 12px;
    max-width: 600px;
    width: 100%;
    box-sizing: border-box;
    border: 1px solid rgba(0, 191, 255, 0.2);
    box-shadow: 0 0 25px rgba(0, 191, 255, 0.08);
    transition: box-shadow 0.3s ease, transform 0.2s ease;
  }

  h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #00bfff;
    border-bottom: 2px solid #00bfff;
    padding-bottom: 10px;
    letter-spacing: 0.5px;
  }

  label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    font-size: 0.95rem;
    color: #ccc;
  }

  select, input {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    border-radius: 6px;
    border: 1px solid #333;
    background-color: #222;
    color: #eee;
    font-size: 1rem;
    box-sizing: border-box;
    transition: all 0.2s ease; 
  }

  input:focus, select:focus {
    outline: 2px solid #00bfff;
    background-color: #333;
    color: #fff;
  }

  .input-group {
    position: relative;
    width: 100%;
  }

  .input-group input {
    padding-right: 40px;
  }

  .toggle-password {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    color: #aaa;
    cursor: pointer;
    font-size: 1rem;
    transition: color 0.3s ease;
  }

  .toggle-password:hover {
    color: #00bfff;
  }

  .rules {
    font-size: 0.85rem;
    color: #bbb;
    margin-top: 5px;
  }

  .error-message {
    color: #ff4d4d;
    font-size: 0.85rem;
    margin-top: 5px;
  }
  
  /* Bloco de Erro vindo do PHP */
  .erro-php {
    background-color: #cc4444;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    font-weight: 600;
    text-align: center;
    color: white;
  }


  .btn-submit {
    width: 100%;
    margin-top: 25px;
    padding: 12px 16px; 
    border: none;
    border-radius: 8px;
    background-color: #28a745; 
    color: #fff;
    font-weight: bold;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .btn-submit:hover,
  .btn-submit:focus {
    background-color: #218838;
    outline: none;
    transform: translateY(-1px);
  }

  .login-link {
    text-align: center;
    margin-top: 15px;
    font-size: 0.9rem;
  }
  .login-link a {
    color: #00bfff;
    text-decoration: none;
    font-weight: bold;
  }
  .login-link a:hover {
    text-decoration: underline;
  }

  @media (max-width: 480px) {
    form { padding: 20px; }
    input { font-size: 0.95rem; padding: 8px 10px; }
    .btn-submit { font-size: 1rem; padding: 10px; }
    .toggle-password { font-size: 0.9rem; right: 10px; }
  }
</style>
</head>
<body>

<div class="form-container">
  <form id="cadastroForm" action="registro_processa.php" method="post" novalidate>
    <h2>Crie sua Conta</h2>

    <?php if (!empty($erro)) : ?>
      <div class="erro-php"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <label for="tipo_pessoa">Tipo de Pessoa:</label>
    <select id="tipo_pessoa" name="tipo_pessoa" required>
      <option value="fisica" selected>Pessoa Física</option>
      <option value="juridica">Pessoa Jurídica</option>
    </select>

    <label id="labelNome" for="nome">Nome Completo:</label>
    <input type="text" id="nome" name="nome" required>

    <label for="tipo_doc">Tipo de Documento:</label>
    <select id="tipo_doc" name="tipo_doc" required>
      <option value="cpf" selected>CPF</option>
      <option value="cnpj">CNPJ</option>
    </select>

    <label id="labelDocumento" for="documento">CPF:</label>
    <input type="text" id="documento" name="documento" required>

    <label for="telefone">Telefone:</label>
    <input type="text" id="telefone" name="telefone" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="email2">Repetir Email:</label>
    <input type="email" id="email2" name="email2" required>
    <div id="emailError" class="error-message"></div>

    <label for="senha">Senha:</label>
    <div class="input-group">
      <input type="password" id="senha" name="senha" required
             pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$"
             title="Mínimo 6 caracteres, incluindo letra maiúscula, minúscula, número e caractere especial">
      <i class="fas fa-eye toggle-password" id="toggleSenha"></i>
    </div>
    <div class="rules">
      Mínimo 6 caracteres, incluindo letra maiúscula, minúscula, número e caractere especial
    </div>

    <label for="senha2">Repetir Senha:</label>
    <input type="password" id="senha2" name="senha2" required>
    <div id="senhaError" class="error-message"></div>

    <button class="btn-submit" type="submit">Finalizar Cadastro</button>

    <div class="login-link">
      Já tem uma conta? <a href="login.php">Faça Login</a>
    </div>
    </form>
</div>

<script>
  // Alternar visibilidade da senha
  const toggleSenha = document.getElementById('toggleSenha');
  const inputSenha = document.getElementById('senha');
  toggleSenha.addEventListener('click', () => {
    const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
    inputSenha.setAttribute('type', tipo);
    toggleSenha.classList.toggle('fa-eye');
    toggleSenha.classList.toggle('fa-eye-slash');
  });

  // Máscara e troca dinâmica CPF/CNPJ
  function aplicarMascaraDocumento(tipo) {
    const input = $("#documento");
    input.unmask();
    if (tipo === "cpf") {
      input.mask("000.000.000-00");
      $("#labelDocumento").text("CPF:");
    } else {
      input.mask("00.000.000/0000-00");
      $("#labelDocumento").text("CNPJ:");
    }
  }

  // Troca de rótulos de nome e documento conforme seleção
  $("#tipo_pessoa").on("change", function() {
    const tipo = $(this).val();
    if (tipo === "fisica") {
      $("#labelNome").text("Nome Completo:");
      $("#tipo_doc").val("cpf").trigger("change");
    } else {
      $("#labelNome").text("Nome da Empresa:");
      $("#tipo_doc").val("cnpj").trigger("change");
    }
  });

  $("#tipo_doc").on("change", function() {
    aplicarMascaraDocumento($(this).val());
  });

  // Aplicar máscara inicial
  aplicarMascaraDocumento("cpf");
  $("#telefone").mask("(00) 00000-0000");

  // Validações
  const form = document.getElementById('cadastroForm');
  const senha2 = document.getElementById('senha2');
  const email2 = document.getElementById('email2');
  const senhaError = document.getElementById('senhaError');
  const emailError = document.getElementById('emailError');

  form.addEventListener('submit', (e) => {
    let valid = true;
    const senhaRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/;

    if (!senhaRegex.test(inputSenha.value)) {
      senhaError.textContent = "Senha não atende aos requisitos.";
      valid = false;
    } else if (inputSenha.value !== senha2.value) {
      senhaError.textContent = "As senhas não coincidem.";
      valid = false;
    } else {
      senhaError.textContent = "";
    }

    if (document.getElementById('email').value !== email2.value) {
      emailError.textContent = "Os emails não coincidem.";
      valid = false;
    } else {
      emailError.textContent = "";
    }

    if (!valid) e.preventDefault();
  });
</script>
</body>
</html>