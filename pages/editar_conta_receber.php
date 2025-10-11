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
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        label, input {
            display: block;
            margin-bottom: 10px;
        }
        input[type="text"], input[type="date"], input[type="number"] {
            width: 300px;
            padding: 8px;
            border-radius: 4px;
            border: none;
        }
        button {
            padding: 10px 15px;
            background-color: #007bff;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #0056b3;
        }
        a {
            color: #0af;
            text-decoration: none;
        }
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