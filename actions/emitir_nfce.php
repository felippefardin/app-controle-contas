<?php
// actions/emitir_nfce.php
require_once '../vendor/autoload.php';
require_once '../database.php';
require_once '../includes/config/nfe_config.php'; // Nosso novo config

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

header('Content-Type: application/json');

$id_venda = $_POST['id_venda'] ?? 0;

if (empty($id_venda)) {
    echo json_encode(['success' => false, 'message' => 'ID da venda não fornecido.']);
    exit;
}

try {
    // 1. Carrega dados da empresa e certificado
    $empresaConfig = $pdo->query("SELECT * FROM empresa_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    $configJson = getConfigJson();
    $certificadoDigital = file_get_contents(__DIR__ . '/../' . $empresaConfig['certificado_a1_path']);
    $tools = new Tools($configJson, Certificate::readPfx($certificadoDigital, $empresaConfig['certificado_senha']));
    $tools->model('65'); // Modelo 65 é para NFC-e

    // 2. Busca dados da venda e dos itens no banco
    $venda = $pdo->query("SELECT * FROM vendas WHERE id = $id_venda")->fetch(PDO::FETCH_ASSOC);
    $itens = $pdo->query("
        SELECT iv.*, p.nome, p.ncm, p.cfop
        FROM itens_venda iv
        JOIN produtos p ON iv.id_produto = p.id
        WHERE iv.id_venda = $id_venda
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Monta a NFC-e
    $nfe = new Make();

    // Informações da Nota
    $infNFe = $nfe->taginfNFe();
    $infNFe->setAttribute('versao', '4.00');
    // Adicionar a chave de acesso aqui se for uma consulta

    // Detalhes da nota
    $ide = $nfe->tagide();
    $ide->cUF($pdo->query("SELECT codigo_uf FROM estados WHERE sigla = '{$empresaConfig['uf']}'")->fetchColumn());
    $ide->cNF(rand(1, 99999999)); // Código numérico randômico
    $ide->natOp('VENDA');
    $ide->mod(65);
    $ide->serie(1); // Controle a série no seu banco
    $ide->nNF(1); // Controle o número da nota no seu banco
    $ide->dhEmi(date('Y-m-d\TH:i:sP'));
    $ide->tpImp(4); // DANFE NFC-e
    $ide->tpEmis(1); // Emissão Normal
    $ide->tpAmb($tools->tpAmb); // Pega do config
    $ide->finNFe(1); // Finalidade normal
    $ide->indFinal(1); // Consumidor final
    $ide->indPres(1); // Operação presencial
    // ...

    // Emitente (sua empresa)
    $emit = $nfe->tagemit();
    $emit->CNPJ($empresaConfig['cnpj']);
    $emit->xNome($empresaConfig['razao_social']);
    $emit->IE($empresaConfig['ie']);
    $emit->CRT($empresaConfig['regime_tributario']);
    // Endereço do emitente
    $enderEmit = $nfe->tagenderEmit();
    $enderEmit->xLgr($empresaConfig['logradouro']);
    // ...

    // Adiciona os produtos
    foreach ($itens as $i => $item) {
        $prod = $nfe->tagprod();
        $prod->item($i + 1);
        $prod->cProd($item['id_produto']);
        $prod->xProd($item['nome']);
        $prod->NCM($item['ncm']);
        $prod->CFOP($item['cfop']);
        $prod->uCom('UN');
        $prod->qCom(number_format($item['quantidade'], 4, '.', ''));
        $prod->vUnCom(number_format($item['preco_unitario'], 2, '.', ''));
        $prod->vProd(number_format($item['quantidade'] * $item['preco_unitario'], 2, '.', ''));
        $prod->indTot(1); // Valor do item entra no total da nota
        // ...
        
        // Impostos (Exemplo para Simples Nacional)
        $imposto = $nfe->tagimposto();
        $imposto->item($i + 1);
        $icms = $nfe->tagICMS();
        $icms->item($i + 1);
        $icms->ICMSSN102(); // Tributada pelo Simples Nacional sem permissão de crédito
        $icms->orig(0); // Nacional
        $icms->CSOSN('102');
    }
    
    // Totais
    $total = $nfe->tagtotal();
    // ...

    // Pagamento
    $pag = $nfe->tagpag();
    $detPag = $nfe->tagdetPag();
    $detPag->tPag('01'); // 01=Dinheiro
    $detPag->vPag(number_format($venda['valor_total'], 2, '.', ''));
    
    // Monta o XML
    $xml = $nfe->getXML();
    
    // 4. Assina e Envia
    $signedXml = $tools->signNFe($xml);
    $response = $tools->sefazEnviaNFe($signedXml);

    // 5. Trata a resposta
    $st = new \NFePHP\NFe\Common\Standardize();
    $std = $st->toStd($response);

    if ($std->cStat == 100) { // Autorizado o uso da NF-e
        $protocolo = $std->protNFe->infProt->nProt;
        $chaveAcesso = $std->protNFe->infProt->chNFe;

        // Salva o XML em um arquivo
        $xmlPath = "notas_fiscais/{$chaveAcesso}.xml";
        file_put_contents(__DIR__ . '/../' . $xmlPath, $signedXml);

        // Salva no banco de dados
        $stmt = $pdo->prepare("INSERT INTO notas_fiscais (id_venda, ambiente, status, chave_acesso, protocolo, xml_path, data_emissao) VALUES (?, ?, 'autorizada', ?, ?, ?, NOW())");
        $stmt->execute([$id_venda, $tools->tpAmb, $chaveAcesso, $protocolo, $xmlPath]);

        echo json_encode(['success' => true, 'message' => 'NFC-e emitida com sucesso!', 'chave' => $chaveAcesso]);
    } else {
        throw new Exception("[{$std->cStat}] {$std->xMotivo}");
    }

} catch (\Exception $e) {
    // Salva o erro no banco para análise
    $stmt = $pdo->prepare("INSERT INTO notas_fiscais (id_venda, ambiente, status, mensagem_erro, data_emissao) VALUES (?, ?, 'erro', ?, NOW())");
    $stmt->execute([$id_venda, ($tools->tpAmb ?? 2), $e->getMessage()]);
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}