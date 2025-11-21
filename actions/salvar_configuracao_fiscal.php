<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

$conn = getTenantConnection();

try {
    $conn->begin_transaction();

    // 1. Salva dados cadastrais na tabela 'empresa_config'
    // Verifica se já existe registro (o fix_tabela.php deve ter criado um com ID, mas garantimos aqui)
    $check = $conn->query("SELECT id FROM empresa_config LIMIT 1");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO empresa_config (razao_social) VALUES (NULL)");
    }

    // Atualiza o registro unico
    $stmt = $conn->prepare("UPDATE empresa_config SET 
        razao_social=?, fantasia=?, cnpj=?, ie=?, 
        logradouro=?, numero=?, bairro=?, municipio=?, 
        cod_municipio=?, uf=?, cep=? 
        LIMIT 1");
    
    $stmt->bind_param("sssssssssss", 
        $_POST['razao_social'], $_POST['fantasia'], $_POST['cnpj'], $_POST['ie'],
        $_POST['logradouro'], $_POST['numero'], $_POST['bairro'], $_POST['municipio'],
        $_POST['cod_municipio'], $_POST['uf'], $_POST['cep']
    );
    $stmt->execute();

    // 2. Salva dados fiscais na tabela 'configuracoes_tenant' (KV Store)
    $camposFiscais = ['regime_tributario', 'ambiente', 'csc_id', 'csc'];
    $stmtKv = $conn->prepare("INSERT INTO configuracoes_tenant (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");

    foreach ($camposFiscais as $chave) {
        if (isset($_POST[$chave])) {
            $valor = $_POST[$chave];
            $stmtKv->bind_param("ss", $chave, $valor);
            $stmtKv->execute();
        }
    }

    $conn->commit();
    header('Location: ../pages/configuracao_fiscal.php?success=1');

} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../pages/configuracao_fiscal.php?error=' . urlencode($e->getMessage()));
}