<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_registro = $_GET['id'] ?? 0;

// Busca os dados da conta bancária para preencher o formulário
$stmt = $conn->prepare("SELECT * FROM contas_bancarias WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_registro, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$registro = $result->fetch_assoc();

if (!$registro) {
    echo "<p>Conta bancária não encontrada ou você não tem permissão para editá-la.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Conta Bancária</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; padding: 20px; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        h1 { color: #eee; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 1rem; }
        .form-control { background-color: #333; color: #eee; border: 1px solid #444; }
        .form-control:focus { background-color: #333; color: #eee; border-color: #0af; box-shadow: none; }
        .btn-primary { background-color: #0af; border: none; }
        .btn-secondary { background-color: #6c757d; border: none; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fa-solid fa-pen-to-square"></i> Editar Conta Bancária</h1>
    
    <form action="../actions/editar_banco_action.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($registro['id']) ?>">
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="nome_banco">Nome do Banco</label>
                <input type="text" class="form-control" name="nome_banco" value="<?= htmlspecialchars($registro['nome_banco']) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="tipo_conta">Tipo de Conta</label>
                <input type="text" class="form-control" name="tipo_conta" value="<?= htmlspecialchars($registro['tipo_conta']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="agencia">Agência</label>
                <input type="text" class="form-control" name="agencia" value="<?= htmlspecialchars($registro['agencia']) ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="conta">Número da Conta</label>
                <input type="text" class="form-control" name="conta" value="<?= htmlspecialchars($registro['conta']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="chave_pix">Chave PIX</label>
            <input type="text" class="form-control" name="chave_pix" value="<?= htmlspecialchars($registro['chave_pix']) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="banco_cadastro.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>