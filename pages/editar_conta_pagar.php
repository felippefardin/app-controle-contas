<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN E PEGA A CONEXÃO
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: ../pages/login.php?error=not_logged_in");
    exit;
}
$conn = getTenantConnection();
if ($conn === null) {
    die("Falha na conexão com o banco de dados.");
}

$id_usuario = $_SESSION['usuario_logado']['id'];
$id_conta = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0; // Pega de GET ou POST

// LÓGICA DE ATUALIZAÇÃO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornecedor = $_POST['fornecedor'];
    $data_vencimento = $_POST['data_vencimento'];
    $numero = $_POST['numero'];
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $id_categoria = (int)$_POST['id_categoria'];
    
    // ✅ CAMPO DE DESCRIÇÃO ADICIONADO
    $descricao = trim($_POST['descricao'] ?? null);

    // ✅ Query atualizada para incluir 'descricao'
    $stmt = $conn->prepare("UPDATE contas_pagar SET fornecedor = ?, data_vencimento = ?, numero = ?, valor = ?, id_categoria = ?, descricao = ? WHERE id = ? AND usuario_id = ?");
    
    // ✅ Bind_param atualizado (adicionado "s" para descricao)
    $stmt->bind_param("sssdisii", $fornecedor, $data_vencimento, $numero, $valor, $id_categoria, $descricao, $id_conta, $id_usuario);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Conta editada com sucesso!";
        header("Location: ../pages/contas_pagar.php");
    } else {
        $_SESSION['error_message'] = "Erro ao atualizar a conta.";
        header("Location: editar_conta_pagar.php?id=" . $id_conta);
    }
    $stmt->close();
    exit;
}

// LÓGICA PARA EXIBIR O FORMULÁRIO (GET)
include('../includes/header.php');

if ($id_conta === 0) {
    echo "<div class='container'><h1>ID da conta não fornecido.</h1></div>";
    exit;
}

// Busca a conta e as categorias
$stmt = $conn->prepare("SELECT * FROM contas_pagar WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $id_conta, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div class='container'><h1>Conta não encontrada ou acesso negado.</h1></div>";
    exit;
}
$conta = $result->fetch_assoc();

// Buscar categorias do usuário
$categorias_result = $conn->query("SELECT id, nome FROM categorias WHERE id_usuario = $id_usuario AND tipo = 'despesa'");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Editar Conta a Pagar</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; }
        .form-control { background-color: #333; color: #eee; border-color: #444; }
        .form-control:focus { background-color: #333; color: #eee; border-color: #0af; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Conta a Pagar</h2>
        <form method="POST" action="editar_conta_pagar.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id_conta) ?>">

            <div class="form-group">
                <label for="fornecedor">Fornecedor</label>
                <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?= htmlspecialchars($conta['fornecedor']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <input type="text" class="form-control" id="descricao" name="descricao" value="<?= htmlspecialchars($conta['descricao'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="numero">Número/Documento</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($conta['numero']) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="valor">Valor</label>
                    <input type="text" class="form-control" id="valor" name="valor" value="<?= number_format($conta['valor'], 2, ',', '.') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="data_vencimento">Data de Vencimento</label>
                    <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" value="<?= htmlspecialchars($conta['data_vencimento']) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="id_categoria">Categoria</label>
                    <select class="form-control" id="id_categoria" name="id_categoria" required>
                        <?php while($cat = $categorias_result->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $conta['id_categoria']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="../pages/contas_pagar.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>