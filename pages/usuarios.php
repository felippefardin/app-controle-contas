<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$sql = "SELECT id, nome, email, cpf, telefone FROM usuarios ORDER BY nome ASC";
$result = $conn->query($sql);

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
    include('../includes/footer.php');
    exit;
}
?>

<div class="container">
  <h2>Usuários</h2>

  <!-- Botão para mostrar/esconder o formulário -->
  <div style="text-align: center; margin-bottom: 15px;">
    <button id="toggleFormBtn" class="btn-primary">Adicionar Novo Usuário</button>
  </div>

  <!-- Formulário invisível inicialmente -->
  <form id="addUserForm" action="add_usuario.php" method="POST" style="display:none; background:#222; padding:15px; border-radius:8px; max-width: 500px; margin: 0 auto 30px auto;">
    <label for="nome">Nome Completo:</label>
    <input type="text" id="nome" name="nome" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="cpf">CPF:</label>
    <input type="text" id="cpf" name="cpf" required>

    <label for="telefone">Telefone:</label>
    <input type="text" id="telefone" name="telefone" required>

    <label for="senha">Senha:</label>
    <div class="password-wrapper">
      <input type="password" id="senha" name="senha" required>
      <button type="button" class="toggle-password" data-target="senha" aria-label="Mostrar/Ocultar senha">👁️</button>
    </div>

    <label for="senha_confirmar">Confirmar Senha:</label>
    <div class="password-wrapper">
      <input type="password" id="senha_confirmar" name="senha_confirmar" required>
      <button type="button" class="toggle-password" data-target="senha_confirmar" aria-label="Mostrar/Ocultar senha">👁️</button>
    </div>

    <button type="submit" class="btn-primary" style="margin-top: 10px;">Salvar Usuário</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>CPF</th>
        <th>Telefone</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($usuario = $result->fetch_assoc()): ?>
        <tr>
          <td data-label="Nome"><?= htmlspecialchars($usuario['nome'] ?? '') ?></td>
          <td data-label="Email"><?= htmlspecialchars($usuario['email'] ?? '') ?></td>
          <td data-label="CPF"><?= htmlspecialchars($usuario['cpf'] ?? '') ?></td>
          <td data-label="Telefone"><?= htmlspecialchars($usuario['telefone'] ?? '') ?></td>
          <td data-label="Ações">
            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>">Editar</a> |
            <a href="excluir_usuario.php?id=<?= $usuario['id'] ?>" onclick="return confirm('Deseja realmente excluir este usuário?')">Excluir</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <p><a href="home.php">← Voltar para Home</a></p>
</div>

<style>
  /* Padrão escuro */
  body {
    background-color: #121212;
    color: #eee;
    font-family: Arial, sans-serif;
  }
  .container {
    max-width: 900px;
    margin: 20px auto;
    padding: 0 15px;
  }
  h2 {
    text-align: center;
    color: #00bfff;
    margin-bottom: 20px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    background-color: #1f1f1f;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 8px rgba(0,0,0,0.7);
  }
  thead tr {
    background-color: #222;
    color: #00bfff;
  }
  th, td {
    padding: 12px 10px;
    border-bottom: 1px solid #333;
    text-align: left;
  }
  tr:nth-child(even) {
    background-color: #2a2a2a;
  }
  tr:hover {
    background-color: #333;
  }
  a {
    color: #00bfff;
    font-weight: bold;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }
  p a {
    display: inline-block;
    margin-top: 20px;
  }

  /* Responsividade */
  @media (max-width: 768px) {
    table, thead, tbody, th, td, tr {
      display: block;
    }
    thead tr {
      display: none;
    }
    tr {
      margin-bottom: 20px;
      border: 1px solid #333;
      border-radius: 8px;
      padding: 10px;
    }
    td {
      position: relative;
      padding-left: 50%;
      text-align: right;
      border-bottom: 1px solid #444;
      font-size: 14px;
    }
    td:last-child {
      border-bottom: 0;
    }
    td::before {
      content: attr(data-label);
      position: absolute;
      left: 10px;
      top: 12px;
      font-weight: bold;
      color: #999;
      white-space: nowrap;
      text-align: left;
      font-size: 14px;
    }
  }

  /* Botão estilo */
  .btn-primary {
    background-color: #27ae60;
    border: none;
    color: white;
    padding: 10px 22px;
    font-weight: bold;
    font-size: 14px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  .btn-primary:hover {
    background-color: #1e874b;
  }

  /* Inputs formulário */
  form#addUserForm label {
    display: block;
    margin-top: 10px;
    margin-bottom: 5px;
  }
  form#addUserForm input {
    width: 100%;
    padding: 8px;
    border-radius: 4px;
    border: none;
    background-color: #333;
    color: #eee;
    font-size: 14px;
  }
  form#addUserForm input::placeholder {
    color: #bbb;
  }

  /* Wrapper para senha e botão */
  .password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
  }
  .password-wrapper input {
    flex: 1;
    padding-right: 40px;
  }
  .toggle-password {
    position: absolute;
    right: 5px;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #00bfff;
    font-size: 18px;
    padding: 0 8px;
    user-select: none;
  }
  .toggle-password:focus {
    outline: none;
  }
</style>

<script>
  // Toggle visibilidade do formulário
  document.getElementById('toggleFormBtn').addEventListener('click', function() {
    const form = document.getElementById('addUserForm');
    if (form.style.display === 'none' || form.style.display === '') {
      form.style.display = 'block';
      this.textContent = 'Cancelar';
    } else {
      form.style.display = 'none';
      this.textContent = 'Adicionar Novo Usuário';
    }
  });

  // Toggle mostrar/ocultar senha
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈'; // muda ícone para "esconder"
      } else {
        input.type = 'password';
        button.textContent = '👁️'; // ícone para mostrar
      }
    });
  });
</script>

<?php include('../includes/footer.php'); ?>
