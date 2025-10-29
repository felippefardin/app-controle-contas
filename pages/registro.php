<?php include('../includes/header.php'); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Cadastro - App Controle de Contas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

  input {
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

  input:focus {
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

  .btn-submit {
    width: 100%;
    margin-top: 25px;
    padding: 12px 16px; 
    border: none;
    border-radius: 8px;
    background-color: #00bfff;
    color: #fff;
    font-weight: bold;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .btn-submit:hover,
  .btn-submit:focus {
    background-color: #0099cc;
    outline: none;
    transform: translateY(-1px);
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
    <h2>Cadastro de Usuário</h2>

    <label for="nome">Nome da Empresa ou Seu Nome:</label>
    <input type="text" id="nome" name="nome" required>

    <label for="cpf">CPF:</label>
    <input type="text" id="cpf" name="cpf" required>

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

    <button class="btn-submit" type="submit">Cadastrar</button>
  </form>
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

  const form = document.getElementById('cadastroForm');
  const senha2 = document.getElementById('senha2');
  const email2 = document.getElementById('email2');
  const senhaError = document.getElementById('senhaError');
  const emailError = document.getElementById('emailError');

  form.addEventListener('submit', (e) => {
    let valid = true;

    // Validação de senha
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

    // Validação de email
    if (document.getElementById('email').value !== email2.value) {
      emailError.textContent = "Os emails não coincidem.";
      valid = false;
    } else {
      emailError.textContent = "";
    }

    if (!valid) e.preventDefault(); // impede envio se houver erro
  });
</script>
</body>
</html>
