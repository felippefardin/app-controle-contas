<?php
require_once '../database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepara a query de exclusão
    $sql = "DELETE FROM caixa_diario WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    // Executa e redireciona com a mensagem correta
    if ($stmt->execute()) {
        header('Location: ../pages/lancamento_caixa.php?success_delete=true');
    } else {
        header('Location: ../pages/lancamento_caixa.php?error_delete=true');
    }
    $stmt->close();
    $conn->close();
} else {
    // Redireciona se o ID não for fornecido
    header('Location: ../pages/lancamento_caixa.php?error_delete=true');
}
exit;
?>