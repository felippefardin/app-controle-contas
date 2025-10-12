<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario_principal'])) {
    header('Location: login.php');
    exit;
}

// Mensagens
$mensagem_sucesso = '';
$mensagem_erro = '';

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == '1') {
        $mensagem_sucesso = "Usuário salvo com sucesso!";
    } elseif ($_GET['sucesso'] == 'excluido') {
        $mensagem_sucesso = "Usuário excluído com sucesso!";
    }
}

if (isset($_GET['erro'])) {
    switch($_GET['erro']) {
        case 'auto_exclusao':
            $mensagem_erro = "Você não pode excluir seu próprio usuário.";
            break;
        case 'permissao':
            $mensagem_erro = "Você não tem permissão para excluir este usuário.";
            break;
        // Adicione outros casos de erro conforme necessário
        default:
            $mensagem_erro = "Ocorreu um erro!";
    }
}

$usuario_principal_id = $_SESSION['usuario_principal']['id'];
$stmt = $conn->prepare("SELECT id, nome, email, cpf, telefone FROM usuarios WHERE id = ? OR id_criador = ? OR owner_id = ? OR criado_por_usuario_id = ? ORDER BY nome ASC");
$stmt->bind_param("iiii", $usuario_principal_id, $usuario_principal_id, $usuario_principal_id, $usuario_principal_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Erro na consulta: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        /* Seu CSS dark mode aqui... */
        body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; }
        h1 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 2rem; font-size: 1.8rem; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; color: white; opacity: 1; transition: opacity 0.5s ease-out; }
        .alert-success { background-color: #28a745; }
        .alert-danger { background-color: #cc4444; }
        /* Estilos da tabela e botões... */
        .table { width: 100%; color: #eee; }
        .table thead { background-color: #0af; color: #ffffff; font-weight: bold; }
        .table th, .table td { padding: 12px 15px; border: 1px solid #444; text-align: left; }
        .table tbody tr { background-color: #2c2c2c; }
        .table tbody tr:hover { background-color: #3c3c3c; }
        .btn { padding: 6px 12px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-primary { background-color: #0af; color: white; }
        .btn-info { background-color: #17a2b8; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa-solid fa-users"></i> Gestão de Usuários</h1>

        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
        <?php endif; ?>
        <?php if ($mensagem_erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php endif; ?>

        <a href="add_usuario.php" class="btn btn-primary mb-4"><i class="fa-solid fa-plus"></i> Adicionar Novo Usuário</a>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($usuario = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['cpf']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['telefone']); ?></td>
                                <td>
                                    <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-info">Editar</a>
                                    <a href="../actions/excluir_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500); // Tempo para a transição de opacidade
                }, 3000); // 3 segundos
            });
        });
    </script>
</body>
</html>