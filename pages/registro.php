<?php 
// pages/registro.php
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/utils.php'; // Flash messages

// Se houver erro vindo do backend, ele será mostrado pelo utils
display_flash_message();
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

  /* CSS BENEFICIOS */
  .benefits-section { margin-top: 25px; border-top: 1px solid #333; padding-top: 20px; }
  .benefit-toggle { cursor: pointer; color: #ff9f43; font-weight: bold; display: flex; align-items: center; gap: 8px; }
  .benefit-content { display: none; background: #252525; padding: 15px; border-radius: 8px; margin-top: 10px; border: 1px solid #444; }
  .benefit-content.open { display: block; }
  .btn-check { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-top: 5px; font-size: 0.9rem; }
  .btn-check:hover { filter: brightness(1.1); }
  .valid-msg { color: #2ecc71; font-size: 0.85rem; margin-top: 5px; display: block; }
  .invalid-msg { color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: block; }
  .input-valid { border-color: #2ecc71 !important; }
  .input-invalid { border-color: #e74c3c !important; }

  .text-danger { color: #e74c3c !important; }
  .text-success { color: #2ecc71 !important; }
  .fw-bold { font-weight: bold; }
  .d-none { display: none !important; }
  .btn-primary { background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
  .btn-success { background-color: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
  #formIndicacao .mb-3 div { display: flex; gap: 10px; } 
</style>
</head>
<body>

<div class="form-container">
  <form id="cadastroForm" action="registro_processa.php" method="post" novalidate>
    <h2>Crie sua Conta</h2>

    <label>Escolha seu Plano (Teste Grátis):</label>
    <div class="planos-container">
      <label class="plano-card selected" id="card-basico">
        <input type="radio" name="plano" value="basico" checked onchange="selectPlan(this)">
        <span class="trial-badge">15 Dias Grátis</span>
        <span class="plano-titulo">Básico</span>
        <span class="plano-preco">R$ 19,90/mês</span>
        <div class="plano-desc">3 Usuários • Gestão Simples</div>
      </label>

      <label class="plano-card" id="card-plus">
        <input type="radio" name="plano" value="plus" onchange="selectPlan(this)">
        <span class="trial-badge">15 Dias Grátis</span>
        <span class="plano-titulo">Plus</span>
        <span class="plano-preco">R$ 39,90/mês</span>
        <div class="plano-desc">6 Usuários • Intermediário</div>
      </label>

      <label class="plano-card" id="card-essencial">
        <input type="radio" name="plano" value="essencial" onchange="selectPlan(this)">
        <span class="trial-badge">30 Dias Grátis</span>
        <span class="plano-titulo">Essencial</span>
        <span class="plano-preco">R$ 59,90/mês</span>
        <div class="plano-desc">16 Usuários • Completo</div>
      </label>
    </div>

    <label for="tipo_pessoa">Tipo de Pessoa:</label>
    <select id="tipo_pessoa" name="tipo_pessoa" required onchange="saveLocal('tipo_pessoa', this.value)">
      <option value="fisica" selected>Pessoa Física</option>
      <option value="juridica">Pessoa Jurídica</option>
    </select>

    <label id="labelNome" for="nome">Nome Completo:</label>
    <input type="text" id="nome" name="nome" required oninput="saveLocal('nome', this.value)">

    <div style="display: flex; gap: 10px;">
        <div style="flex: 1;">
            <label for="tipo_doc">Doc:</label>
            <select id="tipo_doc" name="tipo_doc" required onchange="saveLocal('tipo_doc', this.value)">
              <option value="cpf" selected>CPF</option>
              <option value="cnpj">CNPJ</option>
            </select>
        </div>
        <div style="flex: 2;">
            <label id="labelDocumento" for="documento">Número:</label>
            <input type="text" id="documento" name="documento" required oninput="saveLocal('documento', this.value)">
        </div>
    </div>

    <label for="telefone">Telefone:</label>
    <input type="text" id="telefone" name="telefone" required oninput="saveLocal('telefone', this.value)">

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required oninput="saveLocal('email', this.value)">

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

    <div class="benefits-section">
        <div class="benefit-toggle" onclick="toggleBenefits()">
            <i class="fas fa-ticket-alt"></i> Possui Cupom ou Indicação? (Opcional) <i class="fas fa-chevron-down"></i>
        </div>
        
        <div id="benefitsContent" class="benefit-content">
            <label style="margin-top: 0;">Código do Cupom:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="cupom" name="cupom" placeholder="Ex: PROMO10" style="text-transform:uppercase;" oninput="saveLocal('cupom', this.value)">
                <button type="button" class="btn-check" onclick="checkCupom()">Validar</button>
            </div>
            <span id="msgCupom"></span>

            <hr style="border-color: #444; margin: 15px 0;">

            <div id="formIndicacao">
                <div class="mb-3">
                    <label for="inputCodigoIndicacao" class="form-label">Código de Indicação</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" class="form-control" id="inputCodigoIndicacao" name="codigo_indicacao" placeholder="Ex: A1B2C3D4" style="text-transform: uppercase;" oninput="saveLocal('codigo_indicacao', this.value)">
                        <button type="button" class="btn btn-primary" onclick="validarCodigoIndicacao()">Validar Código</button>
                    </div>
                    <div id="feedbackIndicacao" class="form-text mt-2"></div>
                </div>
                <input type="hidden" id="id_indicador_validado" name="id_indicador">
                <button type="button" class="btn btn-success d-none" id="btnConfirmarIndicacao" style="width:100%; margin-top:10px; cursor:default;">Indicação Confirmada <i class="fas fa-check"></i></button>
            </div>
        </div>
    </div>

    <button class="btn-submit" type="submit">Finalizar Cadastro e Testar Grátis</button>
    <div class="login-link">Já tem uma conta? <a href="login.php">Faça Login</a></div>
  </form>
</div>

<script>
  function saveLocal(key, value) { localStorage.setItem('reg_' + key, value); }
  function loadLocal() {
      const fields = ['tipo_pessoa', 'nome', 'tipo_doc', 'documento', 'telefone', 'email', 'cupom', 'inputCodigoIndicacao'];
      fields.forEach(id => {
          const val = localStorage.getItem('reg_' + id);
          if (val) {
              const el = document.getElementById(id);
              if(el) {
                  el.value = val;
                  if(id === 'tipo_pessoa' || id === 'tipo_doc') $(el).trigger('change');
              }
          }
      });
  }

  function selectPlan(radio) {
    document.querySelectorAll('.plano-card').forEach(c => c.classList.remove('selected'));
    radio.closest('.plano-card').classList.add('selected');
  }

  function toggleBenefits() {
      document.getElementById('benefitsContent').classList.toggle('open');
  }

  const toggleSenha = document.getElementById('toggleSenha');
  const inputSenha = document.getElementById('senha');
  toggleSenha.addEventListener('click', () => {
    const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
    inputSenha.setAttribute('type', tipo);
    toggleSenha.classList.toggle('fa-eye');
    toggleSenha.classList.toggle('fa-eye-slash');
  });

  function checkCupom() {
      const codigo = document.getElementById('cupom').value;
      const msg = document.getElementById('msgCupom');
      const input = document.getElementById('cupom');
      if(!codigo) return;
      msg.innerHTML = '<span style="color:#aaa">Verificando...</span>';
      const formData = new FormData();
      formData.append('codigo', codigo);
      fetch('../actions/validar_cupom_api.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
          if(data.valid) {
              msg.innerHTML = `<span class='valid-msg'><i class='fas fa-check'></i> Cupom válido! Desconto de ${data.valor}${data.tipo=='porcentagem'?'%':''} será aplicado.</span>`;
              input.classList.add('input-valid'); input.classList.remove('input-invalid');
          } else {
              msg.innerHTML = `<span class='invalid-msg'><i class='fas fa-times'></i> ${data.msg}</span>`;
              input.classList.add('input-invalid'); input.classList.remove('input-valid');
          }
      });
  }

  function validarCodigoIndicacao() {
    let codigo = document.getElementById('inputCodigoIndicacao').value;
    let feedback = document.getElementById('feedbackIndicacao');
    let btnConfirmar = document.getElementById('btnConfirmarIndicacao');
    let hiddenId = document.getElementById('id_indicador_validado');
    let input = document.getElementById('inputCodigoIndicacao'); 

    if(codigo.length < 3) { feedback.innerHTML = "<span class='text-danger'>Código muito curto.</span>"; return; }
    feedback.innerHTML = "<span style='color: #ccc;'>Verificando...</span>";

    fetch('../actions/validar_indicacao_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'codigo_indicacao=' + encodeURIComponent(codigo)
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            feedback.innerHTML = "<span class='text-success fw-bold'>" + data.message + "</span>";
            hiddenId.value = data.id_indicador;
            btnConfirmar.classList.remove('d-none');
            input.classList.add('input-valid'); input.classList.remove('input-invalid');
        } else {
            feedback.innerHTML = "<span class='text-danger'>" + data.message + "</span>";
            btnConfirmar.classList.add('d-none');
            hiddenId.value = "";
            input.classList.add('input-invalid'); input.classList.remove('input-valid');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        feedback.innerHTML = "<span class='text-danger'>Erro ao validar.</span>";
    });
  }

  $(document).ready(function() {
      function aplicarMascaraDocumento(tipo) {
        const input = $("#documento");
        input.unmask();
        if (tipo === "cpf") { input.mask("000.000.000-00"); $("#labelDocumento").text("CPF:"); } 
        else { input.mask("00.000.000/0000-00"); $("#labelDocumento").text("CNPJ:"); }
      }
      $("#tipo_pessoa").on("change", function() {
        if ($(this).val() === "fisica") { $("#labelNome").text("Nome Completo:"); $("#tipo_doc").val("cpf").trigger("change"); } 
        else { $("#labelNome").text("Nome da Empresa:"); $("#tipo_doc").val("cnpj").trigger("change"); }
      });
      $("#tipo_doc").on("change", function() { aplicarMascaraDocumento($(this).val()); });
      $("#telefone").mask("(00) 00000-0000");
      loadLocal();
      aplicarMascaraDocumento($("#tipo_doc").val() || "cpf");
  });

  const form = document.getElementById('cadastroForm');
  const senha2 = document.getElementById('senha2');
  const email2 = document.getElementById('email2');
  const senhaError = document.getElementById('senhaError');
  const emailError = document.getElementById('emailError');

  form.addEventListener('submit', (e) => {
    let valid = true;
    const senhaRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/;
    if (!senhaRegex.test(inputSenha.value)) { senhaError.textContent = "Senha fraca."; valid = false; } 
    else if (inputSenha.value !== senha2.value) { senhaError.textContent = "Senhas não coincidem."; valid = false; } 
    else { senhaError.textContent = ""; }

    if (document.getElementById('email').value !== email2.value) { emailError.textContent = "Emails não coincidem."; valid = false; } 
    else { emailError.textContent = ""; }

    if (!valid) e.preventDefault();
    else { localStorage.clear(); }
  });
</script>
</body>
</html>