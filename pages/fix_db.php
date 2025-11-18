<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_logado'])) {
    die("Por favor, faça login primeiro.");
}

$conn = getTenantConnection();
if (!$conn) die("Erro ao conectar no banco do usuário.");

echo "<h2>Corrigindo colunas de Venda...</h2>";

// Lista de tabelas e colunas a verificar
$correcoes = [
    'contas_receber' => 'ADD COLUMN id_venda INT DEFAULT NULL AFTER data_vencimento',
    'caixa_diario'   => 'ADD COLUMN id_venda INT DEFAULT NULL AFTER descricao'
];

foreach ($correcoes as $tabela => $comando) {
    // Verifica se a tabela existe
    $res = $conn->query("SHOW TABLES LIKE '$tabela'");
    if ($res->num_rows > 0) {
        // Verifica se a coluna id_venda existe
        $check = $conn->query("SHOW COLUMNS FROM $tabela LIKE 'id_venda'");
        
        if ($check->num_rows == 0) {
            // Se não existe, cria
            $sql = "ALTER TABLE $tabela $comando";
            if ($conn->query($sql)) {
                echo "<p style='color:green'>✅ Coluna <b>id_venda</b> adicionada na tabela <b>$tabela</b>.</p>";
            } else {
                echo "<p style='color:red'>❌ Erro em $tabela: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:gray'>🆗 Tabela <b>$tabela</b> já possui a coluna id_venda.</p>";
        }
        
        // Adiciona índice para deixar as buscas rápidas
        $checkIdx = $conn->query("SHOW INDEX FROM $tabela WHERE Key_name = 'idx_venda'");
        if ($checkIdx->num_rows == 0) {
             $conn->query("ALTER TABLE $tabela ADD INDEX idx_venda (id_venda)");
             echo "<p style='color:green'>&nbsp;&nbsp;↳ Índice criado para $tabela.</p>";
        }
        
    } else {
        echo "<p style='color:orange'>⚠️ Tabela <b>$tabela</b> não encontrada.</p>";
    }
}

echo "<hr><h3>Concluído!</h3>";
echo "<a href='vendas.php'><button>Voltar para o PDV</button></a>";
?>