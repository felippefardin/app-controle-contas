<?php
require_once '../includes/session_init.php';
require_once '../database.php'; // Espera-se que $pdo (PDO) venha daqui

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/configuracao_fiscal.php?error=Método inválido');
    exit;
}

// Diretório seguro para salvar os certificados
$certDir = __DIR__ . '/../certificados/';
if (!is_dir($certDir)) {
    mkdir($certDir, 0755, true);
}

// Pega os dados do formulário
$id = $_POST['id']; // ID do usuário, vindo do formulário
$razao_social = $_POST['razao_social'];
$cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj']); // Remove formatação do CNPJ
$fantasia = $_POST['fantasia'];
$ie = $_POST['ie'];
$logradouro = $_POST['logradouro'];
$numero = $_POST['numero'];
$bairro = $_POST['bairro'];
$municipio = $_POST['municipio'];
$uf = $_POST['uf'];
$cep = preg_replace('/[^0-9]/', '', $_POST['cep']);
$cod_municipio = $_POST['cod_municipio'];
$regime_tributario = $_POST['regime_tributario'];
$csc = $_POST['csc'];
$csc_id = $_POST['csc_id'];
$certificado_senha = $_POST['certificado_senha'];

// ✅ CORRIGIDO (Problema 4): Captura o novo campo
$ambiente = $_POST['ambiente'];


try {
    // Verifica se já existe uma configuração
    $stmt = $pdo->prepare("SELECT * FROM empresa_config WHERE id = ?");
    $stmt->execute([$id]);
    $existingConfig = $stmt->fetch();

    $params = [
        'razao_social' => $razao_social,
        'cnpj' => $cnpj,
        'fantasia' => $fantasia,
        'ie' => $ie,
        'logradouro' => $logradouro,
        'numero' => $numero,
        'bairro' => $bairro,
        'municipio' => $municipio,
        'uf' => $uf,
        'cep' => $cep,
        'cod_municipio' => $cod_municipio,
        'regime_tributario' => $regime_tributario,
        'csc' => $csc,
        'csc_id' => $csc_id,
        'ambiente' => $ambiente, // ✅ CORRIGIDO (Problema 4): Adiciona ao array de parâmetros
    ];

    // Lida com o upload do certificado
    if (isset($_FILES['certificado_a1']) && $_FILES['certificado_a1']['error'] == UPLOAD_ERR_OK) {
        $certFileName = 'cert_' . $cnpj . '.pfx';
        $certPath = 'certificados/' . $certFileName;
        
        if (move_uploaded_file($_FILES['certificado_a1']['tmp_name'], $certDir . $certFileName)) {
            $params['certificado_a1_path'] = $certPath;
        } else {
            throw new Exception("Falha ao mover o arquivo do certificado.");
        }
    }

    // Lida com a senha (só atualiza se uma nova for fornecida)
    if (!empty($certificado_senha)) {
        $params['certificado_senha'] = $certificado_senha;
    }


    if ($existingConfig) {
        // Atualiza a configuração existente
        $sql = "UPDATE empresa_config SET ";
        foreach ($params as $key => $value) {
            $sql .= "$key = :$key, ";
        }
        $sql = rtrim($sql, ', ');
        $sql .= " WHERE id = :id";
        $params['id'] = $id;

    } else {
        // Insere uma nova configuração
        // ✅ CORRIGIDO (Problema 5): Inicializa o número da NF como 0
        $params['ultimo_numero_nfce'] = 0; 
        
        $sql = "INSERT INTO empresa_config (id, " . implode(', ', array_keys($params)) . ") VALUES (:id, :" . implode(', :', array_keys($params)) . ")";
        $params['id'] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Location: ../pages/configuracao_fiscal.php?success=1');
    exit;

} catch (Exception $e) {
    header('Location: ../pages/configuracao_fiscal.php?error=' . urlencode($e->getMessage()));
    exit;
}