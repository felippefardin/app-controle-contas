<?php
require_once '../includes/session_init.php';
include('../includes/header.php');
include('../database.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_produto = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_produto, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$produto = $result->fetch_assoc();

if (!$produto) {
    echo "Produto não encontrado.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>Editar Produto</h1>
    <form action="../actions/editar_produto_action.php" method="POST">
        <input type="hidden" name="id" value="<?= $produto['id'] ?>">
        <div class="form-group">
            <label for="nome">Nome do Produto</label>
            <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
        </div>
        <div class="form-group">
            <label for="quantidade">Quantidade</label>
            <input type="number" class="form-control" name="quantidade" value="<?= htmlspecialchars($produto['quantidade']) ?>" required>
        </div>
        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea class="form-control" name="descricao"><?= htmlspecialchars($produto['descricao']) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="preco_compra">Preço de Compra</label>
                <input type="text" class="form-control" name="preco_compra" value="<?= htmlspecialchars($produto['preco_compra']) ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="preco_venda">Preço de Venda</label>
                <input type="text" class="form-control" name="preco_venda" value="<?= htmlspecialchars($produto['preco_venda']) ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="unidade_medida">Unidade de Medida</label>
                <input type="text" class="form-control" name="unidade_medida" value="<?= htmlspecialchars($produto['unidade_medida']) ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="quantidade_minima">Estoque Mínimo</label>
                <input type="number" class="form-control" name="quantidade_minima" value="0">
           </div>
        </div>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    </form>
</div>
</body>
</html>