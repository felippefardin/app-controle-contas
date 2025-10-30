<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']['id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$conn = getTenantConnection();
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

$id_usuario_logado = $_SESSION['usuario_logado']['id'];
$id_conta = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_conta > 0) {
    $conn->begin_transaction();

    try {
        // Pega os detalhes da conta para o estorno no caixa
        $stmt_get_conta = $conn->prepare("SELECT valor, data_baixa, id_venda FROM contas_receber WHERE id = ? AND usuario_id = ?");
        $stmt_get_conta->bind_param("ii", $id_conta, $id_usuario_logado);
        $stmt_get_conta->execute();
        $conta = $stmt_get_conta->get_result()->fetch_assoc();

        if ($conta) {
            $valor_conta = $conta['valor'];
            $data_baixa_conta = $conta['data_baixa'];
            $id_venda = $conta['id_venda'];
            $descricao_estorno = "Estorno da Conta a Receber #$id_conta";
            if ($id_venda) {
                $descricao_estorno .= " (Venda #$id_venda)";
            }

            // Atualiza a conta para 'pendente' e limpa os dados da baixa
            $stmt = $conn->prepare(
                "UPDATE contas_receber
                 SET status = 'pendente', data_baixa = NULL, baixado_por = NULL, forma_pagamento = NULL, juros = 0.00, comprovante = NULL
                 WHERE id = ? AND usuario_id = ?"
            );
            $stmt->bind_param("ii", $id_conta, $id_usuario_logado);
            $stmt->execute();

            // Subtrai o valor do caixa diário correspondente à data da baixa
            if ($data_baixa_conta) {
                $stmt_caixa = $conn->prepare(
                    "UPDATE caixa_diario SET valor = valor - ?, descricao = CONCAT(descricao, ' | ', ?) WHERE usuario_id = ? AND data = ?"
                );
                $stmt_caixa->bind_param("dsis", $valor_conta, $descricao_estorno, $id_usuario_logado, $data_baixa_conta);
                $stmt_caixa->execute();
            }

            $conn->commit();
            $_SESSION['success_message'] = "Conta estornada com sucesso e movida para Contas a Receber.";
        } else {
            throw new Exception("Conta não encontrada ou não pertence a este usuário.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Erro ao estornar a conta: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "ID da conta inválido.";
}

// Redireciona de volta para a lista de contas baixadas
header('Location: ../pages/contas_receber_baixadas.php');
exit;
?>