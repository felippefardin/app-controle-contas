<?php include('../includes/header.php'); ?>
<h2>Cadastro de Usuário</h2>
<form action="registro_processa.php" method="post">
  <label>Nome completo:</label>
  <input type="text" name="nome" required>

  <label>CPF:</label>
  <input type="text" name="cpf" required>

  <label>Telefone:</label>
  <input type="text" name="telefone" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <label>Senha:</label>
  <input type="password" name="senha" required>

  <button type="submit">Cadastrar</button>
</form>
<?php include('../includes/footer.php'); ?>
