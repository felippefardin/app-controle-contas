<?php
require_once '../includes/session_init.php';
require_once '../includes/header.php';
require_once '../database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

$produto = null; // Inicializa a variável

// Pega o ID do produto da URL e busca no banco de dados
if (isset($_GET['id'])) {
    $id_produto = $_GET['id'];
    $id_usuario = $_SESSION['usuario']['id'];

    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_produto, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $produto = $result->fetch_assoc();
    } else {
        // Se não encontrar o produto, exibe uma mensagem e encerra
        echo "<div class='container mt-4 alert alert-danger'>Produto não encontrado ou não pertence a este usuário.</div>";
        include('../includes/footer.php');
        exit;
    }
} else {
    echo "<div class='container mt-4 alert alert-warning'>Nenhum ID de produto fornecido.</div>";
    include('../includes/footer.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #121212;
            color: #eee;
        }
        .container {
            background-color: #222;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        h2 {
            color: #eee;
            border-bottom: 2px solid #0af;
            padding-bottom: 10px;
            margin-bottom: 1rem;
        }
        .form-control {
            background-color: #333;
            color: #eee;
            border: 1px solid #444;
        }
        .form-control:focus {
            background-color: #333;
            color: #eee;
            border-color: #0af;
            box-shadow: none;
        }
        .form-control::placeholder {
            color: #888;
        }
        .btn-primary {
            background-color: #0af;
            border: none;
        }
        .btn-secondary {
            background-color: #555;
            border: none;
        }
        hr {
            border-top: 1px solid #444;
        }
        .form-text {
            color: #aaa !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa-solid fa-pen-to-square"></i> Editar Produto</h2>
        
        <form action="../actions/editar_produto_action.php" method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($produto['id']) ?>">
            
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Produto</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="2"><?= htmlspecialchars($produto['descricao']) ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="preco_compra" class="form-label">Preço de Compra</label>
                    <input type="text" class="form-control" id="preco_compra" name="preco_compra" placeholder="0.00" value="<?= htmlspecialchars($produto['preco_compra']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="preco_venda" class="form-label">Preço de Venda</label>
                    <input type="text" class="form-control" id="preco_venda" name="preco_venda" placeholder="0.00" value="<?= htmlspecialchars($produto['preco_venda']) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="quantidade_estoque" class="form-label">Quantidade em Estoque</label>
                    <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque" value="<?= htmlspecialchars($produto['quantidade_estoque']) ?>" required>
                </div>
            </div>

            <hr>
            <h5>Informações Fiscais</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ncm" class="form-label">NCM</label>
                    <input type="text" class="form-control" id="ncm" name="ncm" value="<?= htmlspecialchars($produto['ncm'] ?? '') ?>" placeholder="Ex: 84713000">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cfop" class="form-label">CFOP</label>
                    <input type="text" class="form-control" id="cfop" name="cfop" value="<?= htmlspecialchars($produto['cfop'] ?? '') ?>" placeholder="Ex: 5102">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="controle_estoque.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>