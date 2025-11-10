<?php
require_once '../database.php';
require_once '../includes/session_init.php';

// ---------------------------------------------
// SCRIPT: Migração automática da tabela assinaturas
// Verifica e corrige colunas 'email' e 'plano'
// em todos os bancos tenant do sistema.
// ---------------------------------------------

echo "<pre>";
echo "🔄 Iniciando migração das tabelas de assinaturas...\n\n";

$connMaster = getMasterConnection();
if (!$connMaster) {
    die("❌ Erro: não foi possível conectar ao banco master.\n");
}

// Busca todos os bancos tenant registrados
$query = "SHOW DATABASES LIKE 'tenant_%'";
$result = $connMaster->query($query);

if ($result->num_rows === 0) {
    die("⚠️ Nenhum banco tenant encontrado.\n");
}

while ($row = $result->fetch_array()) {
    $dbName = $row[0];
    echo "🔹 Verificando banco: {$dbName} ... ";

    try {
        $tenantConn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $dbName);
        $tenantConn->set_charset("utf8mb4");

        // 1️⃣ Verifica se a tabela assinaturas existe
        $checkTable = $tenantConn->query("SHOW TABLES LIKE 'assinaturas'");
        if ($checkTable->num_rows === 0) {
            echo "⚠️ Tabela 'assinaturas' não encontrada, ignorando.\n";
            continue;
        }

        // 2️⃣ Verifica as colunas existentes
        $columnsRes = $tenantConn->query("SHOW COLUMNS FROM assinaturas");
        $columns = [];
        while ($col = $columnsRes->fetch_assoc()) {
            $columns[] = $col['Field'];
        }

        $alterations = [];

        // 3️⃣ Adiciona colunas faltantes
        if (!in_array('email', $columns)) {
            $alterations[] = "ADD COLUMN email VARCHAR(255) NOT NULL AFTER id_usuario";
        }
        if (!in_array('plano', $columns)) {
            $alterations[] = "ADD COLUMN plano VARCHAR(50) NOT NULL AFTER email";
        }

        if (!empty($alterations)) {
            $alterSQL = "ALTER TABLE assinaturas " . implode(', ', $alterations);
            $tenantConn->query($alterSQL);
            echo "🆕 Colunas adicionadas com sucesso!\n";
        } else {
            echo "✅ Estrutura já está atualizada.\n";
        }

        $tenantConn->close();
    } catch (Exception $e) {
        echo "❌ Erro no banco {$dbName}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Migração concluída!\n";
echo "</pre>";
?>
