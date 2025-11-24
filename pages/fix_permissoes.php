<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_logado'])) {
    die("Você precisa estar logado para rodar este fix.");
}

// Conecta no banco do Tenant ATUAL
$conn = getTenantConnection();

echo "<h2>Atualizando Banco do Tenant...</h2>";
echo "<p>Banco conectado: " . $_SESSION['db_database'] . "</p>";

try {
    // Tenta adicionar a coluna
    $sql = "ALTER TABLE usuarios ADD COLUMN permissoes TEXT DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<h3 style='color: green;'>✅ Sucesso! Coluna 'permissoes' criada.</h3>";
    }
} catch (mysqli_sql_exception $e) {
    // Se der erro 1060, é porque já existe
    if ($e->getCode() == 1060) {
        echo "<h3 style='color: orange;'>⚠️ A coluna 'permissoes' já existe neste banco.</h3>";
    } else {
        echo "<h3 style='color: red;'>❌ Erro: " . $e->getMessage() . "</h3>";
    }
}

echo "<br><a href='home.php'>Voltar para Home</a>";
?>