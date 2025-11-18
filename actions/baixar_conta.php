<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

$conn = getTenantConnection();
if (!$conn) {
    $_SESSION['error_message'] = "Erro de conexão com o banco.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

// 2. CAPTURA DADOS DO USUÁRIO E FORMULÁRIO
$usuario_id = $_SESSION['usuario_id']; // Quem está fazendo a ação
$id_conta = isset($_POST['id_conta']) ? (int)$_POST['id_conta'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$data_baixa = isset($_POST['data_baixa']) ? $_POST['data_baixa'] : date('Y-m-d');
$forma_pagamento = isset($_POST['forma_pagamento']) ? $_POST['forma_pagamento'] : 'outros';

if ($id_conta <= 0) {
    $_SESSION['error_message'] = "Conta inválida.";
    header('Location: ../pages/contas_pagar.php');
    exit;
}

// 3. PROCESSAMENTO DO ARQUIVO (COMPROVANTE)
$caminho_comprovante = null;

if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../comprovantes/';
    
    // Cria a pasta se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extensao = strtolower(pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($extensao, $permitidos)) {
        // Nome único para evitar sobrescrita
        $novoNome = uniqid('comp_') . '_' . $id_conta . '.' . $extensao;
        $destino = $uploadDir . $novoNome;

        if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $destino)) {
            // Salva no banco apenas o caminho relativo (ex: comprovantes/arquivo.jpg)
            $caminho_comprovante = 'comprovantes/' . $novoNome;
        } else {
            $_SESSION['error_message'] = "Erro ao mover o arquivo de comprovante.";
        }
    } else {
        $_SESSION['error_message'] = "Formato de arquivo inválido. Apenas PDF e Imagens.";
    }
}

// 4. ATUALIZA A TABELA (Query dinâmica dependendo se tem anexo ou não)
if ($caminho_comprovante) {
    $sql = "UPDATE contas_pagar SET 
            status = 'baixada', 
            data_baixa = ?, 
            baixado_por = ?, 
            forma_pagamento = ?, 
            comprovante = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissi", $data_baixa, $usuario_id, $forma_pagamento, $caminho_comprovante, $id_conta);
} else {
    $sql = "UPDATE contas_pagar SET 
            status = 'baixada', 
            data_baixa = ?, 
            baixado_por = ?, 
            forma_pagamento = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $data_baixa, $usuario_id, $forma_pagamento, $id_conta);
}

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Conta baixada com sucesso!";
} else {
    $_SESSION['error_message'] = "Erro ao baixar conta: " . $stmt->error;
}

$stmt->close();
header('Location: ../pages/contas_pagar_baixadas.php'); // Redireciona para as baixadas para conferência
exit;
?>