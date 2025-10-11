<?php
session_start();
include('../includes/header.php');
include('../database.php');

// A verificação agora é baseada no 'usuario_principal'
if (!isset($_SESSION['usuario_principal'])) {
    header('Location: login.php');
    exit;
}

// Mensagens
$mensagem_sucesso = '';
$mensagem_erro = '';

if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1) {
    $mensagem_sucesso = "Usuário salvo com sucesso!";
}

if (isset($_GET['erro'])) {
    switch($_GET['erro']) {
        case 'duplicado_email':
            $mensagem_erro = "Este e-mail já está cadastrado em outro usuário!";
            break;
        case 'duplicado_cpf':
            $mensagem_erro = "Este CPF já está cadastrado em outro usuário!";
            break;
        case 'senha':
            $mensagem_erro = "As senhas não coincidem!";
            break;
        default:
            $mensagem_erro = "Erro ao salvar usuário!";
    }
}
// Pega o ID da conta principal
$usuario_principal_id = $_SESSION['usuario_principal']['id'];

// ✅ ESTA CONSULTA ESTÁ CORRETA
// Consulta usuários: o próprio principal E os que ele criou
$stmt = $conn->prepare("SELECT id, nome, email, cpf, telefone FROM usuarios WHERE id = ? OR id_criador = ? ORDER BY nome ASC");
$stmt->bind_param("ii", $usuario_principal_id, $usuario_principal_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo "<p>Erro na consulta: " . $conn->error . "</p>";
    include('../includes/footer.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários</title>     
</head>
<body>
    <div class="container">
        <h1 class="my-4">Gestão de Usuários</h1>

        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem_erro): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <a href="add_usuario.php" class="btn btn-primary mb-3">Adicionar Novo Usuário</a>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
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
                                    <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-info">Editar</a>
                                    
                                    <a href="../actions/excluir_usuario.php?id=<?php echo $usuario['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                       Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
// Inclui o rodapé da página
include('../includes/footer.php');
?>
</body>
</html>