<?php
// includes/config/nfe_config.php

function getConfigJson() {
    require_once __DIR__ . '/../../database.php'; // Ajuste o caminho

    // Busca os dados da empresa no banco
    $stmt = $pdo->query("SELECT * FROM empresa_config WHERE id = 1"); // Supondo que só haverá uma config
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        throw new Exception("Configurações fiscais da empresa não encontradas.");
    }

    $configJson = [
        'atualizacao' => date('Y-m-d H:i:s'),
        'tpAmb'       => 2, // 2 para Homologação (testes), 1 para Produção
        'razaosocial' => $config['razao_social'],
        'cnpj'        => $config['cnpj'],
        'fantasia'    => $config['fantasia'],
        'ie'          => $config['ie'],
        'logradouro'  => $config['logradouro'],
        'numero'      => $config['numero'],
        'bairro'      => $config['bairro'],
        'municipio'   => $config['municipio'],
        'uf'          => $config['uf'],
        'cep'         => $config['cep'],
        'codMun'      => $config['cod_municipio'],
        'csc'         => $config['csc'],
        'cscId'       => $config['csc_id'],
        'schemes'     => 'PL_009_V4',
        'versao'      => '4.00',
    ];

    return json_encode($configJson);
}