<?php
require_once '../database.php'; // Já inclui getMasterConnection()
require_once '../includes/session_init.php'; // Para garantir

/*
// 🔹 Descomente esta parte se quiser proteger o script
if (!isset($_SESSION['super_admin'])) {
    die("❌ Acesso negado. Logue como super admin.");
}
*/

echo "<!DOCTYPE html><html lang='pt-br'><head><meta charset='UTF-8'>";
echo "<title>Reparo de Bancos</title>";
echo "<style>body { font-family: sans-serif; line-height: 1.6; background: #f4f4f4; color: #333; padding: 20px; } hr { border: 0; border-top: 1px solid #ccc; } b { color: #005a9e; } .success { color: green; } .error { color: red; } .warn { color: #a17000; }</style>";
echo "</head><body>";

echo "<h2>🔧 Reparo e Criação de Bancos de Tenants</h2>";

$master = getMasterConnection(); // Conexão ROOT/MASTER

// Caminho do arquivo de schema
$schemaFile = __DIR__ . '/../includes/tenant_schema.sql';
if (!file_exists($schemaFile)) {
     // Fallback para o schema.sql na raiz (como em register_user.php)
    $schemaFile = __DIR__ . '/../schema.sql';
    if (!file_exists($schemaFile)) {
        die("<p class='error'>❌ Arquivo de schema não encontrado em: <b>{$schemaFile}</b> ou <b>../schema.sql</b></p></body></html>");
    }
}
$schemaSql = file_get_contents($schemaFile);

try {
    $tenants = $master->query("
        SELECT id, nome, nome_empresa, db_host, db_database, db_user, db_password, admin_email
        FROM tenants
    ");

    if ($tenants->num_rows === 0) {
        echo "<p>Nenhum tenant encontrado na tabela.</p>";
        exit;
    }

    while ($tenant = $tenants->fetch_assoc()) {
        $dbName = $tenant['db_database'];
        $dbHost = $tenant['db_host']; // 'localhost'
        $dbUser = $tenant['db_user']; // 'dbu_...' ou 'dbuser'
        $dbPass = $tenant['db_password']; // '...'
        $adminEmail = $tenant['admin_email'];
        
        $adminNome = $tenant['nome']; 
        $adminNomeEmpresa = $tenant['nome_empresa'];

        echo "<hr><b>Tenant #{$tenant['id']} — {$adminNomeEmpresa} ({$dbName})</b><br>";

        // 1️⃣ Cria o banco de dados
        try {
            $master->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<span class='success'>🟢 Banco <b>{$dbName}</b> verificado/criado.</span><br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ Erro ao criar DB: {$e->getMessage()}</span><br>";
            continue;
        }
        
        // --- INÍCIO DA CORREÇÃO ---
        // 2️⃣ Verifica, Cria ou ATUALIZA o usuário MySQL e dá acesso
        try {
            if (empty($dbUser) || empty($dbPass)) {
                 echo "<span class='error'>❌ Falha: db_user ou db_password estão vazios na tabela 'tenants' (master).</span><br>";
                 continue;
            }
            
            // 1. Verifica se o usuário já existe
            $userCheckResult = $master->query("SELECT 1 FROM mysql.user WHERE user = '$dbUser' AND host = 'localhost'");
            $userExists = ($userCheckResult && $userCheckResult->num_rows > 0);
            
            if ($userExists) {
                // 2. Se existe, ALTERA a senha para garantir
                $master->query("ALTER USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPass'");
                echo "<span class='success'>🟢 Usuário <b>{$dbUser}</b> já existia. Senha FORÇADAMENTE ATUALIZADA.</span><br>";
            } else {
                // 3. Se não existe, CRIA o usuário
                $master->query("CREATE USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPass'");
                echo "<span class='success'>🟢 Usuário <b>{$dbUser}</b> não existia. Criado com sucesso.</span><br>";
            }

            // 4. Concede privilégios (executa em ambos os casos)
            $master->query("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$dbUser'@'localhost'");
            echo "<span class='success'>🟢 Privilégios concedidos.</span><br>";
            
            // 5. Limpa os privilégios
            $master->query("FLUSH PRIVILEGES");
            echo "<span class='success'>🟢 Privilégios atualizados (flush).</span><br>";

        } catch (Exception $e) {
            echo "<span class='error'>❌ Erro ao criar/atualizar usuário: {$e->getMessage()}</span><br>";
            continue;
        }
        // --- FIM DA CORREÇÃO ---

        
        // 3️⃣ Conecta ao NOVO banco (agora como o NOVO usuário)
        $tenantConn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($tenantConn->connect_error) {
             echo "<span class='error'>❌ Falha ao conectar no tenant DB APÓS criar: {$tenantConn->connect_error}</span><br>";
             continue;
        }
        $tenantConn->set_charset("utf8mb4");
        
        // 4️⃣ Aplica o schema (só se não houver tabelas)
        $checkTables = $tenantConn->query("SHOW TABLES LIKE 'usuarios'");
        if ($checkTables && $checkTables->num_rows > 0) {
            echo "<span class='warn'>🟡 Schema já existe (tabela 'usuarios' encontrada).</span><br>";
        } else {
            echo "📦 Aplicando schema base...<br>";
            if (!$tenantConn->multi_query($schemaSql)) {
                 echo "<span class='error'>❌ Erro ao executar schema: " . $tenantConn->error . "</span><br>";
            } else {
                while ($tenantConn->more_results() && $tenantConn->next_result()) { /* limpa */ }
                echo "<span class='success'>✅ Schema aplicado com sucesso.</span><br>";
            }
        }
        
        // 5️⃣ Verifica/Insere o usuário admin do tenant (o que se registrou)
        $stmtCheckUser = $tenantConn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtCheckUser->bind_param("s", $adminEmail);
        $stmtCheckUser->execute();
        $userResult = $stmtCheckUser->get_result();
        
        if ($userResult->num_rows == 0) {
            echo "<span class='warn'>👤 Usuário admin '{$adminEmail}' não encontrado. Criando...</span><br>";
            
            $nomeFinal = $adminNome;
            if (empty($nomeFinal)) {
                $nomeFinal = $adminNomeEmpresa;
            }
            if (empty($nomeFinal)) {
                $nomeFinal = 'Administrador Padrão';
            }

            $senhaPadrao = password_hash('mudar123', PASSWORD_DEFAULT);
            $tenantMasterId = $tenant['id'];
            
            $stmtInsert = $tenantConn->prepare("
                INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tenant_id)
                VALUES (?, ?, ?, 'admin', 'ativo', ?)
            ");
            $stmtInsert->bind_param("sssi", $nomeFinal, $adminEmail, $senhaPadrao, $tenantMasterId);
            
            try {
                $stmtInsert->execute();
                echo "<span class='success'>✅ Usuário <b>{$adminEmail}</b> criado com nome '<b>{$nomeFinal}</b>' e senha padrão '<b>mudar123</b>'.</span><br>";
                echo "<span class='warn'><b>AVISO:</b> Este usuário agora precisa usar a função 'Esqueci minha senha'.</span><br>";
            } catch (Exception $e) {
                 echo "<span class='error'>❌ Falha ao inserir usuário admin: " . $e->getMessage() . "</span><br>";
            }
            $stmtInsert->close();

        } else {
             echo "<span class='success'>✅ Usuário admin <b>{$adminEmail}</b> já existe.</span><br>";
        }
        
        $stmtCheckUser->close();
        $tenantConn->close();
    }

    echo "<hr><b>✅ Processo de reparo concluído!</b>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>