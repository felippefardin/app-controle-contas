<?php include('../includes/header.php'); ?>

<style>
  body {
    background-color: #121212;
    color: #eee;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
  }

  /* Container centralizado */
  .form-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 120px); /* Ajusta considerando header/footer */
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

  form:hover {
    box-shadow: 0 0 35px rgba(0, 191, 255, 0.15);
    transform: translateY(-2px);
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

 /* --- ESTILO ESPECÍFICO PARA O BOTÃO DO FORMULÁRIO --- */
.btn-submit {
  width: 100%; /* Ocupa a largura toda do formulário */
  margin-top: 25px; /* Espaço acima do botão */
  padding: 12px 16px; 
  border: none;
  border-radius: 8px;
  background-color: #00bfff; /* Cor de fundo azul clara */
  color: #fff; /* Cor do texto branca */
  font-weight: bold;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.2s ease; /* Efeitos de transição suaves */
}

/* Estilo para quando o mouse está sobre o botão ou ele está focado */
.btn-submit:hover,
.btn-submit:focus {
  background-color: #0099cc; /* Cor de fundo escurece */
  outline: none;
  transform: translateY(-1px); /* Leve elevação para dar feedback */
}

/* Ajuste para telas menores (responsividade) */
@media (max-width: 768px) {
  .btn-submit {
    font-size: 1rem;
    padding: 12px;
  }
}
</style>

<div class="form-container">
  <form action="registro_processa.php" method="post" novalidate>
    <h2>Cadastro de Usuário</h2>

    <label for="nome">Nome completo:</label>
    <input type="text" id="nome" name="nome" required>

    <label for="cpf">CPF:</label>
    <input type="text" id="cpf" name="cpf" required>

    <label for="telefone">Telefone:</label>
    <input type="text" id="telefone" name="telefone" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="senha">Senha:</label>
    <input type="password" id="senha" name="senha" required>

    <button class="btn-submit" type="submit">Cadastrar</button>
  </form>
</div>



