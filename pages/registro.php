<?php 
// pages/registro.php
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
  /* ... (Mantenha o CSS existente do seu arquivo original aqui) ... */
  body { background-color: #121212; color: #eee; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
  .form-container { display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 120px); padding: 20px; box-sizing: border-box; }
  form { background-color: #1f1f1f; padding: 25px 35px; border-radius: 12px; max-width: 700px; width: 100%; box-sizing: border-box; border: 1px solid rgba(0, 191, 255, 0.2); box-shadow: 0 0 25px rgba(0, 191, 255, 0.08); }
  h2 { text-align: center; margin-bottom: 20px; color: #00bfff; border-bottom: 2px solid #00bfff; padding-bottom: 10px; }
  label { display: block; margin-top: 15px; font-weight: bold; font-size: 0.95rem; color: #ccc; }
  select, input { width: 100%; padding: 10px 12px; margin-top: 6px; border-radius: 6px; border: 1px solid #333; background-color: #222; color: #eee; font-size: 1rem; box-sizing: border-box; }
  input:focus, select:focus { outline: 2px solid #00bfff; background-color: #333; color: #fff; }
  .input-group { position: relative; width: 100%; }
  .input-group input { padding-right: 40px; }
  .toggle-password { position: absolute; top: 50%; right: 12px; transform: translateY(-50%); color: #aaa; cursor: pointer; }
  .rules, .error-message { font-size: 0.85rem; margin-top: 5px; }
  .error-message { color: #ff4d4d; }
  .erro-php { background-color: #cc4444; padding: 10px; margin-bottom: 15px; border-radius: 5px; color: white; text-align: center; }
  .btn-submit { width: 100%; margin-top: 25px; padding: 12px 16px; border: none; border-radius: 8px; background-color: #28a745; color: #fff; font-weight: bold; font-size: 1.1rem; cursor: pointer; }
  .login-link { text-align: center; margin-top: 15px; }
  .login-link a { color: #00bfff; text-decoration: none; }

  /* CSS DOS PLANOS */
  .planos-container { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
  .plano-card { flex: 1; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; position: relative; min-width: 180px; }
  .plano-card:hover { border-color: #00bfff; background: #333; }
  .plano-card input[type="radio"] { display: none; }
  .plano-card.selected { border-color: #00bfff; background: #2c3e50; box-shadow: 0 0 10px rgba(0, 191, 255, 0.2); }
  .plano-titulo { color: #00bfff; font-weight: bold; font-size: 1.1rem; display: block; margin-bottom: 5px; }
  .plano-preco { font-size: 1.2rem; color: #fff; font-weight: bold; display: block; margin-bottom: 5px; }
  .plano-desc { font-size: 0.85rem; color: #bbb; line-height: 1.4; }
  .trial-badge { background: #ffc107; color: #000; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; font-weight: bold; position: absolute; top: 10px; right: 10px; }
</style>
</head>
<body>

<div class="form-container">
  <form id="cadastroForm" action="registro_processa.php" method="post" novalidate>
    <h2>Crie sua Conta</h2>

    <?php if (!empty($erro)) : ?>
      <div class="erro-php"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <label>Escolha seu Plano (Teste Grátis 15 Dias):</label>
    <div class="planos-container">
      <label class="plano-card selected" id="card-basico">
        <input type="radio" name="plano" value="basico" checked onchange="selectPlan(this)">
        <span class="trial-badge">15 Dias Grátis</span>
        <span class="plano-titulo">Plano Básico</span>
        <span class="plano-preco">R$ 19,90/mês</span>
        <div class="plano-desc">
          <i class="fas fa-check"></i> 1 Usuário Admin<br>
          <i class="fas fa-check"></i> 2 Usuários Padrão<br>
          <small>Total: 3 Usuários</small>
        </div>
      </label>

      <label class="plano-card" id="card-plus">
        <input type="radio" name="plano" value="plus" onchange="selectPlan(this)">
        <span class="trial-badge">15 Dias Grátis</span>
        <span class="plano-titulo">Plano Plus</span>
        <span class="plano-preco">R$ 39,90/mês</span>
        <div class="plano-desc">
          <i class="fas fa-check"></i> 1 Usuário Admin<br>
          <i class="fas fa-check"></i> 5 Usuários Padrão<br>
          <small>Total: 6 Usuários</small>
        </div>
      </label>

      <label class="plano-card" id="card-essencial">
        <input type="radio" name="plano" value="essencial" onchange="selectPlan(this)">
        <span class="trial-badge">30 Dias Grátis</span>
        <span class="plano-titulo">Plano Essencial</span>
        <span class="plano-preco">R$ 59,90/mês</span>
        <div class="plano-desc">
          <i class="fas fa-check"></i> 1 Usuário Admin<br>
          <i class="fas fa-check"></i> 15 Usuários Padrão<br>
          <small>Total: 16 Usuários</small>
        </div>
      </label>
    </div>

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
    <div class="rules">Mínimo 6 caracteres, maiúscula, minúscula, número e especial</div>

    <label for="senha2">Repetir Senha:</label>
    <input type="password" id="senha2" name="senha2" required>
    <div id="senhaError" class="error-message"></div>

    <button class="btn-submit" type="submit">Finalizar Cadastro e Testar Grátis</button>

    <div class="login-link">Já tem uma conta? <a href="login.php">Faça Login</a></div>
  </form>
</div>

<script>
  // Função Visual Seleção de Plano
  function selectPlan(radio) {
    document.querySelectorAll('.plano-card').forEach(c => c.classList.remove('selected'));
    radio.closest('.plano-card').classList.add('selected');
  }

  // ... (Mantenha o restante dos scripts JS do arquivo original: toggleSenha, mascaras, validações) ...
  const toggleSenha = document.getElementById('toggleSenha');
  const inputSenha = document.getElementById('senha');
  toggleSenha.addEventListener('click', () => {
    const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
    inputSenha.setAttribute('type', tipo);
    toggleSenha.classList.toggle('fa-eye');
    toggleSenha.classList.toggle('fa-eye-slash');
  });

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

  aplicarMascaraDocumento("cpf");
  $("#telefone").mask("(00) 00000-0000");

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