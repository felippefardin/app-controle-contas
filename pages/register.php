<?php include('../includes/header.php'); ?>

<style>
  /* Reset box-sizing */
  *, *::before, *::after {
    box-sizing: border-box;
  }

  body {
    background-color: #121212;
    color: #eee;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 40px 20px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
  }

  h2 {
    color: #0af;
    text-align: center;
    width: 100%;
    max-width: 420px;
    margin-bottom: 30px;
    font-weight: 700;
  }

  form {
    background-color: #222;
    padding: 30px 40px;
    border-radius: 10px;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 0 20px rgba(0, 170, 255, 0.5);
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  input, select {
    padding: 12px 15px;
    border-radius: 6px;
    border: none;
    background-color: #333;
    color: #eee;
    font-size: 1rem;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
  }

  input::placeholder {
    color: #bbb;
  }

  input:focus, select:focus {
    background-color: #444;
    outline: 2px solid #0af;
    color: #fff;
    box-shadow: 0 0 6px #0af;
  }

  button {
    padding: 14px;
    background-color: #0af;
    border: none;
    border-radius: 8px;
    color: #eee;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
  }

  button:hover, button:focus {
    background-color: #0088ff;
    outline: none;
  }

  p {
    margin-top: 25px;
    text-align: center;
  }

  a {
    color: #0af;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  a:hover, a:focus {
    color: #0088ff;
    text-decoration: underline;
    outline: none;
  }

  /* Responsividade */
  @media (max-width: 480px) {
    body {
      padding: 30px 15px;
      align-items: center;
    }
    form, h2 {
      max-width: 100%;
    }
  }
</style>

<h2>Cadastro</h2>
<form action="../actions/register_user.php" method="POST" autocomplete="off">
  <input type="text" name="nome" placeholder="Nome completo" required>
  <input type="text" name="cpf" placeholder="CPF" required>
  <input type="text" name="telefone" placeholder="Telefone" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="senha" placeholder="Senha" required>
  <select name="perfil" required>
    <option value="" disabled selected>Selecione o perfil</option>
    <option value="padrao">Padrão</option>
    <option value="admin">Administrador</option>
  </select>
  <button type="submit">Cadastrar</button>
</form>
<p><a href="login.php">Voltar ao login</a></p>

<?php include('../includes/footer.php'); ?>
