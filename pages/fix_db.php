<?php
require_once '../includes/session_init.php';
require_once '../database.php';

if (!isset($_SESSION['usuario_logado'])) {
    die("Faça login primeiro.");
}

$conn = getTenantConnection();
if (!$conn) die("Erro ao conectar no banco do tenant.");

echo "<h3>Verificando e Corrigindo Banco de Dados...</h3>";

// Lista de colunas necessárias na tabela contas_receber
$colunas = [
    "numero" => "VARCHAR(50) DEFAULT NULL AFTER id_pessoa_fornecedor",
    "descricao" => "VARCHAR(255) DEFAULT NULL AFTER numero",
    "baixado_por" => "INT DEFAULT NULL",
    "data_baixa" => "DATE DEFAULT NULL",
    "forma_pagamento" => "VARCHAR(50) DEFAULT NULL",
    "comprovante" => "VARCHAR(255) DEFAULT NULL"
];

foreach ($colunas as $coluna => $definicao) {
    // Verifica se a coluna existe
    $check = $conn->query("SHOW COLUMNS FROM contas_receber LIKE '$coluna'");
    
    if ($check->num_rows == 0) {
        // Se não existe, cria
        $sql = "ALTER TABLE contas_receber ADD COLUMN $coluna $definicao";
        if ($conn->query($sql)) {
            echo "<p style='color:green'>✅ Coluna <b>$coluna</b> criada com sucesso.</p>";
        } else {
            echo "<p style='color:red'>❌ Erro ao criar <b>$coluna</b>: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:gray'>🆗 Coluna <b>$coluna</b> já existe.</p>";
    }
}

// Ajustar o status para ENUM se ainda não for
$conn->query("ALTER TABLE contas_receber MODIFY COLUMN status ENUM('pendente','baixada') DEFAULT 'pendente'");

echo "<hr><p><b>Processo concluído!</b> Tente adicionar a conta novamente.</p>";
echo "<a href='contas_receber.php'>Voltar para Contas a Receber</a>";
?>