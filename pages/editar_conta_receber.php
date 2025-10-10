<?php
session_start();
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$conta = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM contas_receber WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $conta = $result->fetch_assoc();
    $stmt->close();
}

if (!$conta) {
    echo "<p class='text-center'>Conta não encontrada.</p>";
    include('../includes/footer.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Conta a Receber</title>
    <style>
        /* Estilos do formulário (pode colocar no style.css) */
        .form-container { max-width: 600px; margin: 20px auto; padding: 20px; background-color: #1f1f1f; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #00bfff; }
        .form-group input { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #444; background-color: #333; color: #eee; }
        .btn-salvar { background-color: #27ae60; color: white; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Editar Conta a Receber</h2>
        <form action="../actions/editar_conta_receber.php" method="POST">
            <input type="hidden" name="id" value="<?= $conta['id'] ?>">
            <div class="form-group">
                <label for="responsavel">Responsável</label>
                <input type="text" name="responsavel" value="<?= htmlspecialchars($conta['responsavel']) ?>" required>
            </div>
            <div class="form-group">
                <label for="numero">Número</label>
                <input type="text" name="numero" value="<?= htmlspecialchars($conta['numero']) ?>" required>
            </div>
            <div class="form-group">
                <label for="valor">Valor</label>
                <input type="text" name="valor" value="<?= number_format($conta['valor'], 2, ',', '.') ?>" required>
            </div>
            <div class="form-group">
                <label for="data_vencimento">Data de Vencimento</label>
                <input type="date" name="data_vencimento" value="<?= $conta['data_vencimento'] ?>" required>
            </div>
            <button type="submit" class="btn btn-salvar">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>
<?php include('../includes/footer.php'); ?>