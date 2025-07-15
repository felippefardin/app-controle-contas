<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../pages/login.php");
    exit;
}

include('../database.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Mostrar formulário para edição
    if (!isset($_GET['id'])) {
        echo "ID da conta não fornecido.";
        exit;
    }
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT fornecedor, data_vencimento, numero, valor FROM contas_pagar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        echo "Conta não encontrada.";
        exit;
    }

    $conta = $result->fetch_assoc();
    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <title>Editar Conta a Pagar</title>
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
        <h2>Editar Conta a Pagar</h2>
        <form method="POST" action="editar_conta_pagar.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <label>Fornecedor:
                <input type="text" name="fornecedor" value="<?= htmlspecialchars($conta['fornecedor']) ?>" required>
            </label>
            <label>Data de Vencimento:
                <input type="date" name="data_vencimento" value="<?= htmlspecialchars($conta['data_vencimento']) ?>" required>
            </label>
            <label>Número:
                <input type="text" name="numero" value="<?= htmlspecialchars($conta['numero']) ?>" required>
            </label>
            <label>Valor:
                <input type="number" step="0.01" name="valor" value="<?= htmlspecialchars($conta['valor']) ?>" required>
            </label>
            <button type="submit">Salvar Alterações</button>
        </form>
        <p><a href="../pages/contas_pagar.php">Voltar para Contas a Pagar</a></p>
    </body>
    </html>

    <?php
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processar atualização no banco
    $id = intval($_POST['id']);
    $fornecedor = $_POST['fornecedor'];
    $data_vencimento = $_POST['data_vencimento'];
    $numero = $_POST['numero'];
    $valor = floatval($_POST['valor']);

    $stmt = $conn->prepare("UPDATE contas_pagar SET fornecedor = ?, data_vencimento = ?, numero = ?, valor = ? WHERE id = ?");
    $stmt->bind_param("sssdi", $fornecedor, $data_vencimento, $numero, $valor, $id);

    if ($stmt->execute()) {
        header("Location: ../pages/contas_pagar.php?msg=Conta atualizada com sucesso");
        exit;
    } else {
        echo "Erro ao atualizar: " . $conn->error;
    }
} else {
    echo "Método HTTP não suportado.";
}
?>
