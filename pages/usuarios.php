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
    echo "Erro na consulta: " . $conn->error;
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Usuários</title>
<style>
  body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #f9f9f9;
    color: #333;
  }

  h2 {
    margin-bottom: 20px;
    text-align: center;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }

  thead {
    background-color: #007bff;
    color: white;
  }

  th, td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
  }

  tr:nth-child(even) {
    background-color: #f2f2f2;
  }

  a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
  }

  a:hover {
    text-decoration: underline;
  }

  p a {
    display: inline-block;
    margin-top: 10px;
    font-weight: bold;
  }

  /* Responsivo */
  @media (max-width: 768px) {
    table, thead, tbody, th, td, tr {
      display: block;
      width: 100%;
    }
    thead tr {
      display: none; /* Esconde o cabeçalho */
    }
    tr {
      margin-bottom: 15px;
      border: 1px solid #ccc;
      padding: 10px;
      background: white;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    td {
      border: none;
      position: relative;
      padding-left: 50%;
      text-align: right;
      font-size: 14px;
      border-bottom: 1px solid #eee;
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
      text-align: left;
      white-space: nowrap;
      font-size: 14px;
      color: #555;
    }
  }
</style>
</head>
<body>

<h2>Usuários</h2>

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
<?php
while ($usuario = $result->fetch_assoc()) {
    $nome = htmlspecialchars($usuario['nome'] ?? '');
    $email = htmlspecialchars($usuario['email'] ?? '');
    $cpf = htmlspecialchars($usuario['cpf'] ?? '');
    $telefone = htmlspecialchars($usuario['telefone'] ?? '');

    echo "<tr>";
    echo "<td data-label='Nome'>{$nome}</td>";
    echo "<td data-label='Email'>{$email}</td>";
    echo "<td data-label='CPF'>{$cpf}</td>";
    echo "<td data-label='Telefone'>{$telefone}</td>";
    echo "<td data-label='Ações'>
            <a href='editar_usuario.php?id={$usuario['id']}'>Editar</a> |
            <a href='excluir_usuario.php?id={$usuario['id']}' onclick=\"return confirm('Deseja realmente excluir este usuário?')\">Excluir</a>
          </td>";
    echo "</tr>"; 
}
?>
  </tbody>
</table>

<p><a href="home.php">Voltar</a></p>

<?php include('../includes/footer.php'); ?>

</body>
</html>
