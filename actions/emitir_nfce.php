<?php


require_once '../vendor/autoload.php';
require_once '../database.php'; // Espera-se que $conn (mysqli) venha daqui
require_once '../includes/config/nfe_config.php';

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

header('Content-Type: application/json');
session_start();

// ---------------------------------------------------------
// 1️⃣ Função de LOG
// ---------------------------------------------------------
function log_nfce($msg) {
    $dirLog = __DIR__ . '/../logs/';
    if (!is_dir($dirLog)) mkdir($dirLog, 0755, true);
    $arquivo = $dirLog . 'nfce_debug.log';
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($arquivo, $linha, FILE_APPEND);
}

// ---------------------------------------------------------
// 2️⃣ Validação de entrada
// ---------------------------------------------------------
$id_venda = filter_input(INPUT_POST, 'id_venda', FILTER_VALIDATE_INT);
if (empty($id_venda)) {
    log_nfce("❌ ID da venda não fornecido.");
    echo json_encode(['success' => false, 'message' => 'ID da venda não fornecido ou inválido.']);
    exit;
}

if ($conn === null) {
    log_nfce("❌ Conexão com banco falhou.");
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados.']);
    exit;
}

// ✅ CORRIGIDO (Problema 1 e 5): Validar sessão do usuário
if (!isset($_SESSION['usuario_logado']['id'])) {
    log_nfce("❌ Sessão do usuário não encontrada.");
    echo json_encode(['success' => false, 'message' => 'Sessão do usuário não encontrada ou expirada.']);
    exit;
}
$usuario_id = (int)$_SESSION['usuario_logado']['id'];
$tpAmb = 2; // Default
$novo_numero_nf = 0;

// ---------------------------------------------------------
// 3️⃣ Início do processo
// ---------------------------------------------------------
try {
    log_nfce("🔹 Iniciando emissão da NFC-e para venda #{$id_venda}");

    // ✅ CORRIGIDO (Problema 5): Inicia a transação
    $conn->begin_transaction();

    // ⚙️ Carrega configurações da empresa
    // ✅ CORRIGIDO (Problema 1 e 5): Busca pelo ID da sessão e trava a linha para update
    $stmtConfig = $conn->prepare("SELECT * FROM empresa_config WHERE id = ? FOR UPDATE");
    $stmtConfig->bind_param("i", $usuario_id);
    $stmtConfig->execute();
    $resultConfig = $stmtConfig->get_result();
    $empresaConfig = $resultConfig->fetch_assoc();

    if (!$empresaConfig) {
        throw new Exception("Configuração fiscal não encontrada para o usuário #{$usuario_id}.");
    }

    // ✅ CORRIGIDO (Problema 5): Calcula o novo número da NF
    $novo_numero_nf = (int)($empresaConfig['ultimo_numero_nfce'] ?? 0) + 1;

    // ✅ CORRIGIDO (Problema 5): Atualiza o número no banco IMEDIATAMENTE
    $stmtUpdateNum = $conn->prepare("UPDATE empresa_config SET ultimo_numero_nfce = ? WHERE id = ?");
    $stmtUpdateNum->bind_param("ii", $novo_numero_nf, $usuario_id);
    $stmtUpdateNum->execute();
    log_nfce("NF #{$novo_numero_nf} reservada para a venda #{$id_venda}.");


    // Caminho do certificado
    $certPath = __DIR__ . '/../' . $empresaConfig['certificado_a1_path'];
    if (!file_exists($certPath)) {
        throw new Exception("Certificado não encontrado em: {$empresaConfig['certificado_a1_path']}");
    }

    // JSON de configuração e ambiente
    // ✅ CORRIGIDO (Problema 2): Passa o array de config para a função
    $configJson = getConfigJson($empresaConfig);
    $configArr = json_decode($configJson, true);
    $tpAmb = $configArr['tpAmb']; // 1=produção, 2=homologação
    log_nfce("🌍 Ambiente detectado: " . ($tpAmb == 1 ? "Produção" : "Homologação"));

    // Inicializa o Tools
    $certificado = file_get_contents($certPath);
    $tools = new Tools($configJson, Certificate::readPfx($certificado, $empresaConfig['certificado_senha']));
    $tools->model('65');

    // 🔍 Busca venda
    $stmt_venda = $conn->prepare("SELECT * FROM vendas WHERE id = ?");
    $stmt_venda->bind_param("i", $id_venda);
    $stmt_venda->execute();
    $venda = $stmt_venda->get_result()->fetch_assoc();

    if (!$venda) throw new Exception("Venda não encontrada.");

    // 🔍 Busca itens
    $stmt_itens = $conn->prepare("
        SELECT iv.*, p.nome, p.ncm, p.cfop 
        FROM venda_items iv
        JOIN produtos p ON iv.id_produto = p.id
        WHERE iv.id_venda = ?
    ");
    $stmt_itens->bind_param("i", $id_venda);
    $stmt_itens->execute();
    $itens = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($itens)) throw new Exception("Nenhum item encontrado para a venda #{$id_venda}.");

    // ---------------------------------------------------------
    // 4️⃣ Monta o XML da NFC-e
    // ---------------------------------------------------------
    $nfe = new Make();

    // infNFe
    $inf = new \stdClass();
    $inf->versao = '4.00';
    $nfe->taginfNFe($inf);

    // ide
    $stmt_uf = $conn->prepare("SELECT codigo_uf FROM estados WHERE sigla = ?");
    $stmt_uf->bind_param("s", $empresaConfig['uf']);
    $stmt_uf->execute();
    $row_uf = $stmt_uf->get_result()->fetch_assoc();
    $codigo_uf = $row_uf['codigo_uf'] ?? '35';

    $ide = new \stdClass();
    $ide->cUF = $codigo_uf;
    $ide->cNF = rand(10000000, 99999999);
    $ide->natOp = 'VENDA';
    $ide->mod = 65;
    $ide->serie = 1;
    // ✅ CORRIGIDO (Problema 5): Usa número sequencial
    $ide->nNF = $novo_numero_nf;
    $ide->dhEmi = date('Y-m-d\TH:i:sP');
    $ide->tpNF = 1;
    $ide->idDest = 1;
    // ✅ CORRIGIDO (Problema 3): Nome do campo
    $ide->cMunFG = $empresaConfig['cod_municipio'] ?? '3550308';
    $ide->tpImp = 4;
    $ide->tpEmis = 1;
    $ide->cDV = 0;
    $ide->tpAmb = $tpAmb;
    $ide->finNFe = 1;
    $ide->indFinal = 1;
    $ide->indPres = 1;
    $ide->procEmi = 0;
    $ide->verProc = '1.0';
    $nfe->tagide($ide);

    // Emitente
    $emit = new \stdClass();
    $emit->CNPJ = $empresaConfig['cnpj'];
    $emit->xNome = $empresaConfig['razao_social'];
    $emit->IE = $empresaConfig['ie'];
    $emit->CRT = $empresaConfig['regime_tributario'];
    $nfe->tagemit($emit);

    // Endereço emitente
    $end = new \stdClass();
    $end->xLgr = $empresaConfig['logradouro'];
    $end->nro = $empresaConfig['numero'];
    $end->xBairro = $empresaConfig['bairro'];
    // ✅ CORRIGIDO (Problema 3): Nome do campo
    $end->cMun = $empresaConfig['cod_municipio'] ?? '3550308';
    $end->xMun = $empresaConfig['municipio'];
    $end->UF = $empresaConfig['uf'];
    $end->CEP = preg_replace('/\D/', '', $empresaConfig['cep']);
    $end->cPais = '1058';
    $end->xPais = 'BRASIL';
    $nfe->tagenderEmit($end);

    // Produtos
    foreach ($itens as $i => $item) {
        $p = new \stdClass();
        $p->item = $i + 1;
        $p->cProd = $item['id_produto'];
        $p->xProd = $item['nome'];
        $p->NCM = $item['ncm'];
        $p->CFOP = $item['cfop'];
        $p->uCom = 'UN';
        $p->qCom = number_format($item['quantidade'], 4, '.', '');
        $p->vUnCom = number_format($item['preco_unitario'], 2, '.', '');
        $p->vProd = number_format($item['quantidade'] * $item['preco_unitario'], 2, '.', '');
        $p->indTot = 1;
        $nfe->tagprod($p);

        $icms = new \stdClass();
        $icms->item = $i + 1;
        $icms->orig = 0;
        $icms->CSOSN = '102';
        $nfe->tagICMSSN($icms);
    }

    // Totais
    $tot = new \stdClass();
    $nfe->tagICMSTot($tot);

    // Pagamento
    $pag = new \stdClass();
    $pag->vTroco = 0.00;
    $nfe->tagpag($pag);

    $detPag = new \stdClass();
    $detPag->tPag = '01'; // Dinheiro
    $detPag->vPag = number_format($venda['valor_total'], 2, '.', '');
    $nfe->tagdetPag($detPag);

    // ---------------------------------------------------------
    // 5️⃣ Assina, envia e grava
    // ---------------------------------------------------------
    $xml = $nfe->getXML();
    log_nfce("📄 XML gerado com sucesso.");

    $signed = $tools->signNFe($xml);
    log_nfce("🔏 XML assinado.");

    $response = $tools->sefazEnviaLote([$signed]);
    log_nfce("📡 Enviado para SEFAZ: " . substr($response, 0, 300) . "...");

    $std = json_decode(json_encode(simplexml_load_string($response)));

    if ($std->cStat == 100 || $std->protNFe->infProt->cStat == 100) {
        $prot = $std->protNFe->infProt;
        $chave = (string) $prot->chNFe;
        $protocolo = (string) $prot->nProt;

        $dir = __DIR__ . '/../notas_fiscais/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $xmlPath = 'notas_fiscais/' . $chave . '.xml';
        file_put_contents(__DIR__ . '/../' . $xmlPath, $signed);

        $stmt = $conn->prepare("INSERT INTO notas_fiscais (id_venda, ambiente, status, chave_acesso, protocolo, xml_path, data_emissao, numero_nf)
                                VALUES (?, ?, 'autorizada', ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("isssssi", $id_venda, $tpAmb, $chave, $protocolo, $xmlPath, $novo_numero_nf);
        $stmt->execute();
        
        // ✅ CORRIGIDO (Problema 5): Confirma a transação (incluindo o incremento do número)
        $conn->commit();

        log_nfce("✅ NFC-e #{$chave} autorizada. Protocolo: {$protocolo}");
        echo json_encode(['success' => true, 'message' => 'NFC-e emitida com sucesso!', 'chave' => $chave]);
    } else {
        $erro = "[{$std->cStat}] {$std->xMotivo}";
        throw new Exception($erro);
    }

} catch (Exception $e) {
    // ✅ CORRIGIDO (Problema 5): Desfaz o incremento do número da NF se algo falhar
    $conn->rollback();
    
    $msg = $e->getMessage();
    $ambiente = $tpAmb ?? 2; // Pega o ambiente que foi carregado, ou usa Homologação

    log_nfce("❌ ERRO: {$msg}");

    // Loga o erro no banco. Isso será uma nova transação (autocommit)
    $stmt = $conn->prepare("INSERT INTO notas_fiscais (id_venda, ambiente, status, mensagem_erro, data_emissao, numero_nf)
                            VALUES (?, ?, 'erro', ?, NOW(), ?)");
    $stmt->bind_param("iisi", $id_venda, $ambiente, $msg, $novo_numero_nf);
    $stmt->execute();

    echo json_encode(['success' => false, 'message' => $msg]);
}