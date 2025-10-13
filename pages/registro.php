<?php include('../includes/header.php'); ?>

<style>
  body {     
    background-color: #121212;
    color: #eee;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
  }

  /* Container apenas para o formulário */
  .form-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 120px);
    padding: 20px;
    box-sizing: border-box;
  }

  form {
    background-color: #222;
    padding: 20px 30px;
    border-radius: 8px;
    max-width: 600px;
    width: 100%;
    box-sizing: border-box;
    box-shadow: 0 0 15px rgba(0, 191, 255, 0.3);
  }

  h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #00bfff;
  }

  label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    font-size: 0.9rem;
  }

  input {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    border-radius: 5px;
    border: none;
    background-color: #333;
    color: #eee;
    font-size: 1rem;
    box-sizing: border-box;
  }

  input:focus {
    outline: 2px solid #00bfff;
    background-color: #444;
    color: #fff;
  }

  button {
    margin-top: 25px;
    /* width: 100%; */
    padding: 12px;
    border: none;
    border-radius: 6px;
    background-color: #00bfff;
    color: #fff;
    font-weight: bold;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  button:hover,
  button:focus {
    background-color: #0099cc;
    outline: none;
  }

  /* Responsividade */
  @media (max-width: 480px) {
    form {
      padding: 15px 20px;
      max-width: 90%;
    }
    button {
      font-size: 1rem;
      padding: 10px;
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

    <button type="submit">Cadastrar</button>
  </form>
</div>

<?php include('../includes/footer.php'); ?>
