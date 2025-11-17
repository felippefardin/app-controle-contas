<?php
require_once __DIR__ . '/../database.php';

$conn = getMasterConnection();

$q = $conn->query("SELECT * FROM tenants");
$tenants = $q->fetch_all(MYSQLI_ASSOC);

foreach ($tenants as $t) {
    echo "<h3>Atualizando tenant: {$t['db_database']}</h3>";

    $tenantConn = new mysqli(
        $t['db_host'],
        $t['db_user'],
        $t['db_password'],
        $t['db_database']
    );

    if ($tenantConn->connect_error) {
        echo "Erro ao conectar no tenant {$t['db_database']}<br>";
        continue;
    }

    // 1. Verifica e cria Quantidade Mínima
    $check = $tenantConn->query("SHOW COLUMNS FROM produtos LIKE 'quantidade_minima'");
    if ($check->num_rows === 0) {
        $tenantConn->query("ALTER TABLE produtos ADD COLUMN quantidade_minima INT DEFAULT 0");
        echo " &#10004; Coluna 'quantidade_minima' adicionada.<br>";
    } else {
        echo " - Coluna 'quantidade_minima' já existe.<br>";
    }

    // 2. Verifica e cria NCM
    $checkNcm = $tenantConn->query("SHOW COLUMNS FROM produtos LIKE 'ncm'");
    if ($checkNcm->num_rows === 0) {
        $tenantConn->query("ALTER TABLE produtos ADD COLUMN ncm VARCHAR(20) DEFAULT NULL");
        echo " &#10004; Coluna 'ncm' adicionada.<br>";
    } else {
        echo " - Coluna 'ncm' já existe.<br>";
    }

    // 3. Verifica e cria CFOP
    $checkCfop = $tenantConn->query("SHOW COLUMNS FROM produtos LIKE 'cfop'");
    if ($checkCfop->num_rows === 0) {
        $tenantConn->query("ALTER TABLE produtos ADD COLUMN cfop VARCHAR(20) DEFAULT NULL");
        echo " &#10004; Coluna 'cfop' adicionada.<br>";
    } else {
        echo " - Coluna 'cfop' já existe.<br>";
    }

    $tenantConn->close();
    echo "<hr>";
}

echo "<br><strong>Finalizado com sucesso!</strong>";
?>