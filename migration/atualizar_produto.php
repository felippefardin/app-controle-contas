<?php
require_once __DIR__ . '/../database.php';

$conn = getMasterConnection();

$q = $conn->query("SELECT * FROM tenants");
$tenants = $q->fetch_all(MYSQLI_ASSOC);

foreach ($tenants as $t) {
    echo "Atualizando tenant: {$t['db_database']}<br>";

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

    $check = $tenantConn->query("SHOW COLUMNS FROM produtos LIKE 'quantidade_minima'");

    if ($check->num_rows === 0) {
        echo " → Adicionando quantidade_minima<br>";
        $tenantConn->query("ALTER TABLE produtos ADD COLUMN quantidade_minima INT DEFAULT 0");
    } else {
        echo " → Já atualizado.<br>";
    }

    $tenantConn->close();
}

echo "<br>Finalizado.";
