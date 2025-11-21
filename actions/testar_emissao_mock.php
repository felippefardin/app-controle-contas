<?php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once '../includes/session_init.php';

header('Content-Type: application/json');

// Função de Log Simples
function log_mock($msg) {
    $arquivo = __DIR__ . '/../logs/mock_nfce.log';
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($arquivo, $linha, FILE_APPEND);
}

// Validação de Sessão
if (!isset($_SESSION['usuario_logado'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
    exit;
}

$conn = getTenantConnection();
$id_venda = filter_input(INPUT_GET, 'id_venda', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id_venda', FILTER_VALIDATE_INT);

if (!$id_venda) {
    // Se não passar ID, pega a última venda para teste
    $res = $conn->query("SELECT id FROM vendas ORDER BY id DESC LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $id_venda = $row['id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma venda encontrada para testar.']);
        exit;
    }
}

try {
    log_mock("🚀 Iniciando simulação de emissão para Venda #$id_venda");

    // 1. Simula busca de dados (mesmo se vazios)
    $empresaConfig = $conn->query("SELECT * FROM empresa_config LIMIT 1")->fetch_assoc();
    
    // 2. Gera chave de acesso falsa
    $uf = '35'; // SP
    $aamm = date('ym');
    $cnpj = '00000000000000'; // CNPJ Zerado
    $mod = '65';
    $serie = '001';
    $numero = str_pad(rand(1, 99999), 9, '0', STR_PAD_LEFT);
    $tpEmis = '1';
    $codigo = str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    $chave_sem_dv = "$uf$aamm$cnpj$mod$serie$numero$tpEmis$codigo";
    $chave = $chave_sem_dv . '0'; // DV Falso

    $protocolo = '1' . date('YmdHis') . rand(10,99);

    // 3. Cria XML fake
    $xml_mock = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><nfeProc versao=\"4.00\" xmlns=\"http://www.portalfiscal.inf.br/nfe\"><protNFe versao=\"4.00\"><infProt><tpAmb>2</tpAmb><verAplic>MOCK_1.0</verAplic><chNFe>$chave</chNFe><dhRecb>" . date('Y-m-d\TH:i:sP') . "</dhRecb><nProt>$protocolo</nProt><digVal>FAKE</digVal><cStat>100</cStat><xMotivo>Autorizado o uso da NF-e (MOCK)</xMotivo></infProt></protNFe></nfeProc>";

    // 4. Salva o XML
    $dir = __DIR__ . '/../notas_fiscais/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . $chave . '.xml', $xml_mock);

    // 5. Grava no Banco como se fosse real
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("INSERT INTO notas_fiscais (id_venda, ambiente, status, chave_acesso, protocolo, xml_path, data_emissao) VALUES (?, 2, 'autorizada', ?, ?, ?, NOW())");
    $path = "notas_fiscais/{$chave}.xml";
    $stmt->bind_param("isss", $id_venda, $chave, $protocolo, $path);
    
    if ($stmt->execute()) {
        $conn->commit();
        log_mock("✅ Sucesso! Venda #$id_venda 'autorizada' com chave $chave");
        echo json_encode([
            'success' => true, 
            'message' => 'Nota Fiscal emitida em modo de TESTE (Sem valor fiscal)',
            'chave' => $chave,
            'protocolo' => $protocolo,
            'xml_url' => $path
        ]);
    } else {
        throw new Exception("Erro ao gravar no banco: " . $stmt->error);
    }

} catch (Exception $e) {
    if(isset($conn)) $conn->rollback();
    log_mock("❌ Erro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>