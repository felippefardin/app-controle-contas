<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado'])) {
    die("<h2>❌ Erro: Você precisa estar logado no sistema para ver este teste.</h2>");
}

$conn = getTenantConnection();

if (!$conn) {
    die("<h2>❌ Erro: Falha ao conectar ao banco do Tenant.</h2>");
}

// 1. Descobre o nome do banco atual
$resultDb = $conn->query("SELECT DATABASE() as nome_banco");
$nomeBanco = $resultDb->fetch_assoc()['nome_banco'];

echo "<h1>🕵️‍♂️ Debug de Notas Fiscais</h1>";
echo "<div style='background: #f4f4f4; padding: 15px; border: 1px solid #ccc; margin-bottom: 20px;'>";
echo "<p><strong>Você está conectado no banco de dados:</strong> <span style='color: blue; font-size: 1.2em; font-weight: bold;'>$nomeBanco</span></p>";
echo "<p>⚠️ <em>Certifique-se de selecionar ESTE banco de dados no seu phpMyAdmin/HeidiSQL.</em></p>";
echo "</div>";

// 2. Verifica se a tabela existe
$checkTable = $conn->query("SHOW TABLES LIKE 'notas_fiscais'");
if ($checkTable->num_rows === 0) {
    die("<h3 style='color: red;'>❌ A tabela 'notas_fiscais' NÃO existe no banco '$nomeBanco'. Rode o script de migração.</h3>");
}

// 3. Lista os últimos registros
$sql = "SELECT * FROM notas_fiscais ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);

echo "<h3>Últimas 10 Notas Registradas:</h3>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #333; color: #fff;'>
            <th>ID</th>
            <th>ID Venda</th>
            <th>Status</th>
            <th>Ambiente</th>
            <th>Chave de Acesso</th>
            <th>Data Emissão</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        $ambiente = ($row['ambiente'] == 1) ? 'Produção' : 'Homologação';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['id_venda']}</td>";
        echo "<td><b>{$row['status']}</b></td>";
        echo "<td>{$ambiente}</td>";
        echo "<td><a href='gerar_danfe.php?chave={$row['chave_acesso']}' target='_blank'>{$row['chave_acesso']} (PDF)</a></td>";
        echo "<td>{$row['data_emissao']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>Nenhum registro encontrado na tabela 'notas_fiscais'.</p>";
}
?>