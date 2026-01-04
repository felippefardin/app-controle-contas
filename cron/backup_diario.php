<?php
// cron/backup_diario.php
// Script ajustado para WAMP64 (Windows)

// 1. Segurança: Impede execução via navegador
if (php_sapi_name() !== 'cli') {
    die("⛔ Este script só pode ser executado via linha de comando (CLI).");
}

set_time_limit(0);
ini_set('memory_limit', '1024M');

require_once __DIR__ . '/../database.php';

// 2. Configuração de Diretórios
$backupDir = __DIR__ . '/../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "\n🚀 INICIANDO ROTINA DE BACKUP (WAMP) - " . date('d/m/Y H:i:s') . "\n";
echo "------------------------------------------------------\n";

$conn = getMasterConnection();

if ($conn->connect_error) {
    die("❌ Erro conexão Master: " . $conn->connect_error . "\n");
}

// 3. Busca Tenants Ativos
// CORREÇÃO APLICADA: Alterado 'plano' para 'plano_atual' conforme o schema.sql
$sql = "SELECT id, nome_empresa, db_host, db_user, db_password, db_database 
        FROM tenants 
        WHERE status_assinatura = 'ativo' AND plano_atual = 'essencial'";

$result = $conn->query($sql);
$total = $result->num_rows;
$sucesso = 0;
$erros = 0;

echo "📋 Total de clientes ativos encontrados: $total\n\n";

// ==============================================================================
// ⚠️ ATENÇÃO: Verifique a versão do MySQL na pasta C:\wamp64\bin\mysql\
// Se sua versão for diferente de 8.0.31, mude o número abaixo.
// ==============================================================================
$mysqldump = "C:/wamp64/bin/mysql/mysql8.0.31/bin/mysqldump.exe"; 

if (!file_exists($mysqldump)) {
    die("❌ ERRO CRÍTICO: O arquivo mysqldump.exe não foi encontrado no caminho:\n$mysqldump\nVerifique a versão do MySQL na pasta C:/wamp64/bin/mysql/ e ajuste o código.");
}

while ($tenant = $result->fetch_assoc()) {
    $empresa = $tenant['nome_empresa'];
    $dbName = $tenant['db_database'];
    $dbUser = $tenant['db_user'];
    $dbPass = $tenant['db_password'];
    $dbHost = $tenant['db_host'];

    echo "🔄 Processando: $empresa ($dbName)... ";

    // Define nome do arquivo (Apenas .sql, sem .gz para compatibilidade Windows)
    $date = date('Y-m-d_H-i');
    $filename = "{$dbName}_{$date}.sql";
    $filepath = $backupDir . $filename;

    // 4. Monta comando para Windows
    // --result-file é mais seguro que > no Windows em alguns casos
    $cmd = "\"$mysqldump\" --single-transaction --quick -h {$dbHost} -u {$dbUser} -p\"{$dbPass}\" --result-file=\"{$filepath}\" {$dbName} 2>&1";

    // Executa comando
    $output = [];
    $returnVar = 0;
    exec($cmd, $output, $returnVar);

    // Verifica se o arquivo foi criado e tem conteúdo (> 0 bytes)
    if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        echo "✅ OK\n";
        $sucesso++;
    } else {
        echo "❌ FALHA\n";
        // Mostra o erro real do Windows/MySQL
        echo "   Erro: " . implode("\n   ", $output) . "\n";
        $erros++;
    }
}

// 5. Rotina de Limpeza (Ajustada para Windows)
// O comando 'find' do Linux não existe nativamente no CMD padrão do Windows da mesma forma.
// Vamos usar PHP puro para limpar arquivos antigos, que funciona em qualquer sistema.
echo "\n🧹 Verificando backups antigos (> 7 dias)...\n";

$files = glob($backupDir . "*.sql");
$now   = time();
$days  = 7; // Dias para manter
$deleted = 0;

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
            unlink($file);
            $deleted++;
        }
    }
}
echo "   $deleted arquivos antigos removidos.\n";

$conn->close();

echo "------------------------------------------------------\n";
echo "🏁 FINALIZADO!\n";
echo "Sucessos: $sucesso | Falhas: $erros\n";
?>