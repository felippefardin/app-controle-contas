<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Garante que está logado para pegar a conexão certa
if (!isset($_SESSION['usuario_logado'])) {
    die("Por favor, faça login antes de acessar esta página.");
}

echo "<h2>🛠️ Corrigindo Tabela Lembretes...</h2>";

// Conecta no banco do Tenant Atual
$conn = getTenantConnection();
$dbName = $_SESSION['db_database'] ?? 'Desconhecido';

echo "<p>Conectado ao banco: <strong>" . htmlspecialchars($dbName) . "</strong></p>";

try {
    // 1. Tenta adicionar a coluna tipo_visibilidade
    $conn->query("ALTER TABLE lembretes ADD COLUMN tipo_visibilidade ENUM('particular', 'grupo') DEFAULT 'particular'");
    echo "<p style='color: green;'>✅ Coluna 'tipo_visibilidade' adicionada.</p>";
} catch (Exception $e) {
    echo "<p style='color: orange;'>⚠️ Aviso: " . $e->getMessage() . "</p>";
}

try {
    // 2. Tenta adicionar a coluna email_notificacao
    $conn->query("ALTER TABLE lembretes ADD COLUMN email_notificacao VARCHAR(150) DEFAULT NULL");
    echo "<p style='color: green;'>✅ Coluna 'email_notificacao' adicionada.</p>";
} catch (Exception $e) {
    echo "<p style='color: orange;'>⚠️ Aviso: " . $e->getMessage() . "</p>";
}

// 3. Converte para InnoDB para suportar chaves estrangeiras (Opcional, mas recomendado)
try {
    $conn->query("ALTER TABLE lembretes ENGINE=InnoDB");
    echo "<p style='color: blue;'>ℹ️ Engine convertida para InnoDB.</p>";
} catch (Exception $e) {}

echo "<hr>";
echo "<p><strong>Concluído!</strong> Tente acessar a página de lembretes novamente.</p>";
echo "<a href='lembrete.php'><button>Ir para Lembretes</button></a>";
?>