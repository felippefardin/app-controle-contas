<?php
// pages/fix_permissoes.php
require_once '../includes/session_init.php';
require_once '../database.php';

// Força o login para pegar a conexão correta do tenant
if (!isset($_SESSION['usuario_logado'])) {
    die("<h2>Erro:</h2> Por favor, faça login no sistema primeiro para que eu saiba qual banco de dados corrigir.");
}

$conn = getTenantConnection();
$dbName = $_SESSION['db_database'] ?? 'banco desconhecido';

echo "<h1>🔧 Reparador de Banco de Dados</h1>";
echo "<p>Conectado ao banco: <strong>$dbName</strong></p>";
echo "<hr>";

try {
    // 1. Verifica se a coluna 'permissoes' existe na tabela 'usuarios'
    $check = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'permissoes'");

    if ($check->num_rows == 0) {
        // Se não existe, cria
        $sql = "ALTER TABLE usuarios ADD COLUMN permissoes TEXT DEFAULT NULL";
        if ($conn->query($sql)) {
            echo "<h3 style='color: green;'>✅ Sucesso! Coluna 'permissoes' foi criada.</h3>";
        } else {
            echo "<h3 style='color: red;'>❌ Erro ao criar coluna: " . $conn->error . "</h3>";
        }
    } else {
        // Se já existe, avisa
        echo "<h3 style='color: blue;'>ℹ️ A coluna 'permissoes' já existe neste banco. Nada a fazer.</h3>";
    }

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Erro Crítico: " . $e->getMessage() . "</h3>";
}

echo "<br><hr><br>";
echo "<a href='home.php' style='background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voltar para Home</a>";
?>