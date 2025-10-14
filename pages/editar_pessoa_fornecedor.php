<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_registro = $_GET['id'] ?? 0;

// Busca os dados do registro específico para preencher o formulário
$stmt = $conn->prepare("SELECT * FROM pessoas_fornecedores WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_registro, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$registro = $result->fetch_assoc();

if (!$registro) {
    echo "<p>Registro não encontrado ou você não tem permissão para editá-lo.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente/Fornecedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; padding: 20px; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 1rem; }
        .form-control, .custom-select { background-color: #333; color: #eee; border: 1px solid #444; }
        .form-control:focus, .custom-select:focus { background-color: #333; color: #eee; border-color: #0af; box-shadow: none; }
        .btn-primary { background-color: #0af; border: none; }
        .btn-secondary { background-color: #6c757d; border: none; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-pen-to-square"></i> Editar Cliente/Fornecedor</h1>
    
    <form action="../actions/editar_pessoa_fornecedor_action.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($registro['id']) ?>">
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="nome">Nome Completo</label>
                <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($registro['nome']) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="cpf_cnpj">CPF ou CNPJ</label>
                <input type="text" class="form-control" name="cpf_cnpj" value="<?= htmlspecialchars($registro['cpf_cnpj']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="endereco">Endereço</label>
            <input type="text" class="form-control" name="endereco" value="<?= htmlspecialchars($registro['endereco']) ?>">
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="contato">Contato (Telefone)</label>
                <input type="text" class="form-control" name="contato" value="<?= htmlspecialchars($registro['contato']) ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="email">E-mail</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($registro['email']) ?>" required>
            </div>
        </div>
         <div class="form-group">
            <label for="tipo">Tipo</label>
            <select name="tipo" class="custom-select" required>
                <option value="pessoa" <?= $registro['tipo'] == 'pessoa' ? 'selected' : '' ?>>Pessoa (Cliente)</option>
                <option value="fornecedor" <?= $registro['tipo'] == 'fornecedor' ? 'selected' : '' ?>>Fornecedor</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="cadastrar_pessoa_fornecedor.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>