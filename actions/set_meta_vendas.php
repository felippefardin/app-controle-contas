<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Carrega a função getTenantConnection()

if (!isset($_SESSION['usuario_logado'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
    exit;
}

// Segurança: Apenas Admin ou Proprietário pode definir a meta
$perfil = $_SESSION['usuario_logado']['nivel_acesso'] ?? 'padrao';
if ($perfil !== 'admin' && $perfil !== 'proprietario') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// ✅ 1. PEGA A CONEXÃO CORRETA DO TENANT
$conn = getTenantConnection(); 
if ($conn === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados do cliente.']);
    exit;
}

// Força o mysqli a lançar Exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$nova_meta = $_POST['meta'] ?? 0;
// Converte formato (ex: 10.000,00) para formato float (10000.00)
$valor_meta = (float)str_replace(',', '.', str_replace('.', '', $nova_meta)); 
$ano_mes_atual = date('Y_n');
$chave_meta = "meta_vendas_" . $ano_mes_atual;

try {
    // 2. Insere ou Atualiza a meta (no banco do TENANT)
    // Esta query irá falhar se 'configuracoes_tenant' não existir
    $sql = "
        INSERT INTO configuracoes_tenant (chave, valor) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor)
    ";
    
    $stmt = $conn->prepare($sql);
    
    $valor_meta_str = (string)$valor_meta;
    $stmt->bind_param("ss", $chave_meta, $valor_meta_str);
    
    $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Meta atualizada com sucesso!']);
   
    $stmt->close();

} catch (Exception $e) {
    // Se a tabela não existir, o erro será capturado e enviado como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conn) $conn->close();
}
exit;
?>