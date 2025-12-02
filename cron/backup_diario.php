<?php
// cron/backup_diario.php
// Script para gerar backup diário dos bancos de dados dos Tenants

// 1. Segurança: Impede execução direta pelo navegador
if (php_sapi_name() !== 'cli') {
    die("⛔ Este script só pode ser executado via linha de comando (CLI).");
}

// Aumenta tempo de execução e memória para evitar falhas em bancos grandes
set_time_limit(0);
ini_set('memory_limit', '1024M');

// Carrega configurações do banco
require_once __DIR__ . '/../database.php';

// 2. Configuração de Diretórios
$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "\n🚀 INICIANDO ROTINA DE BACKUP DIÁRIO - " . date('d/m/Y H:i:s') . "\n";
echo "------------------------------------------------------\n";

$conn = getMasterConnection();

if ($conn->connect_error) {
    die("❌ Erro conexão Master: " . $conn->connect_error . "\n");
}

// 3. Busca Tenants Ativos
// DICA: Se quiser filtrar apenas o plano 'essencial', adicione à query: AND plano = 'essencial'
$sql = "SELECT id, nome_empresa, db_host, db_user, db_password, db_database 
        FROM tenants 
        WHERE status_assinatura = 'ativo'";

$result = $conn->query($sql);
$total = $result->num_rows;
$sucesso = 0;
$erros = 0;

echo "📋 Total de clientes ativos encontrados: $total\n\n";

while ($tenant = $result->fetch_assoc()) {
    $empresa = $tenant['nome_empresa'];
    $dbName = $tenant['db_database'];
    $dbUser = $tenant['db_user'];
    $dbPass = $tenant['db_password'];
    $dbHost = $tenant['db_host'];

    echo "🔄 Processando: $empresa ($dbName)... ";

    // Define nome do arquivo: nomebanco_ANOMESDIA_HORA.sql.gz
    $date = date('Y-m-d_H-i');
    $filename = "{$dbName}_{$date}.sql.gz";
    $filepath = $backupDir . $filename;

    // 4. Monta comando mysqldump
    // --single-transaction: evita travar o banco durante o backup
    // --quick: útil para grandes tabelas
    // 2>&1: captura erros
    $cmd = "mysqldump --single-transaction --quick -h {$dbHost} -u {$dbUser} -p'{$dbPass}' {$dbName} | gzip > {$filepath}";

    // Executa comando no sistema operacional
    $output = [];
    $returnVar = 0;
    exec($cmd, $output, $returnVar);

    if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        echo "✅ OK (Salvo em: $filename)\n";
        $sucesso++;
    } else {
        echo "❌ FALHA\n";
        error_log("Erro backup $dbName: " . implode("\n", $output));
        $erros++;
    }
}

// 5. Rotina de Limpeza (Opcional)
// Remove backups com mais de 7 dias para não lotar o servidor
echo "\n🧹 Limpando backups antigos (> 7 dias)...\n";
$cmdLimpeza = "find {$backupDir} -name '*.sql.gz' -mtime +7 -delete";
exec($cmdLimpeza);

$conn->close();

echo "------------------------------------------------------\n";
echo "🏁 FINALIZADO!\n";
echo "Sucessos: $sucesso | Falhas: $erros\n";
?>