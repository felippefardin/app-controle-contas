<?php
// cron/cli_migracao.php

// 1. Apenas permite execução via terminal (Segurança)
if (php_sapi_name() !== 'cli') {
    die("⛔ Este script só pode ser executado via linha de comando (CLI).");
}

// Remove limite de tempo para execução longa
set_time_limit(0);
ini_set('memory_limit', '512M');

// Ajuste o caminho se necessário para encontrar o database.php
require_once __DIR__ . '/../database.php'; 

// ==========================================
// 🛠️ DEFINA AQUI A SUA ATUALIZAÇÃO SQL
// ==========================================
// DICA: Remova o 'IF NOT EXISTS' para funcionar em MySQL antigo.
// O script abaixo vai tratar o erro se a coluna já existir.
$sqlMigration = "
    ALTER TABLE usuarios 
    ADD COLUMN whatsapp_confirmed TINYINT(1) DEFAULT 0;
";
// ==========================================

echo "\n🚀 INICIANDO MIGRAÇÃO EM MASSA...\n";
echo "--------------------------------------\n";
echo "SQL a executar: \n$sqlMigration\n";
echo "--------------------------------------\n\n";

$master = getMasterConnection(); 

if ($master->connect_error) {
    die("Erro conexão Master: " . $master->connect_error . "\n");
}

// Busca todos os tenants ativos
$stmt = $master->prepare("SELECT id, nome_empresa, db_host, db_user, db_password, db_database FROM tenants");
$stmt->execute();
$result = $stmt->get_result();

$total = $result->num_rows;
$sucesso = 0;
$erros = 0;
$ignorados = 0;
$atual = 0;

while ($tenant = $result->fetch_assoc()) {
    $atual++;
    $empresa = str_pad(substr($tenant['nome_empresa'], 0, 20), 20);
    echo "[$atual/$total] $empresa ... ";

    // Tenta conectar no banco do cliente
    $tenantConn = @new mysqli(
        $tenant['db_host'], 
        $tenant['db_user'], 
        $tenant['db_password'], 
        $tenant['db_database']
    );

    if ($tenantConn->connect_error) {
        echo "❌ ERRO CONEXÃO: " . $tenantConn->connect_error . "\n";
        $erros++;
        continue;
    }

    // Executa a migração
    try {
        // Usamos query simples para DDL (Create/Alter) para pegar o errno corretamente
        if ($tenantConn->query($sqlMigration)) {
            echo "✅ SUCESSO\n";
            $sucesso++;
        } else {
            // Tratamento de erros comuns
            if ($tenantConn->errno == 1060) { // Erro 1060: Duplicate column name
                echo "⚠️  JÁ EXISTE (Ignorado)\n";
                $ignorados++;
            } elseif ($tenantConn->errno == 1050) { // Erro 1050: Table already exists
                echo "⚠️  TABELA JÁ EXISTE (Ignorado)\n";
                $ignorados++;
            } else {
                echo "🔥 ERRO SQL ({$tenantConn->errno}): " . $tenantConn->error . "\n";
                $erros++;
            }
        }
    } catch (Exception $e) {
        // Captura exceções do driver mysqli se configurado para lançar
        if ($tenantConn->errno == 1060 || strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠️  JÁ EXISTE (Ignorado)\n";
            $ignorados++;
        } else {
            echo "🔥 EXCEPTION: " . $e->getMessage() . "\n";
            $erros++;
        }
    }

    $tenantConn->close();
}

$master->close();

echo "\n--------------------------------------\n";
echo "🏁 FINALIZADO!\n";
echo "Sucessos:  $sucesso\n";
echo "Ignorados: $ignorados (Já existiam)\n";
echo "Falhas:    $erros\n";
echo "--------------------------------------\n";
?>