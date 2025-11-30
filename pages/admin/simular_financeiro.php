<?php
// pages/admin/simular_financeiro.php
require_once '../../includes/session_init.php';
include('../../database.php');

// Proteção: Apenas para teste (remova em produção)
if (!isset($_SESSION['super_admin'])) {
    die("Acesso negado.");
}

$conn = getMasterConnection();
$tenant_id = $_SESSION['tenant_id'] ?? 'teste_tenant_123';
$mes_atual = date('m');
$ano_atual = date('Y');
$data_hoje = date('Y-m-d');

echo "<h2>Simulador de Dados Financeiros</h2>";

// 1. Simular uma Fatura PENDENTE (Vai para 'A Receber')
$sql_pendente = "INSERT INTO faturas_assinatura 
    (tenant_id, valor, data_vencimento, status, forma_pagamento, transacao_id) 
    VALUES 
    ('$tenant_id', 59.90, '$ano_atual-$mes_atual-25', 'pendente', 'ticket', 'simulacao_pend_01')";

if ($conn->query($sql_pendente)) {
    echo "<p style='color:orange'>✅ Fatura PENDENTE de R$ 59.90 inserida! (Deve aparecer em 'A Receber')</p>";
} else {
    echo "<p style='color:red'>Erro ao inserir pendente: " . $conn->error . "</p>";
}

// 2. Simular uma Fatura PAGA (Vai para 'Recebido')
$sql_pago = "INSERT INTO faturas_assinatura 
    (tenant_id, valor, data_vencimento, data_pagamento, status, forma_pagamento, transacao_id) 
    VALUES 
    ('$tenant_id', 39.90, '$ano_atual-$mes_atual-10', '$data_hoje', 'pago', 'credit_card', 'simulacao_pago_01')";

if ($conn->query($sql_pago)) {
    echo "<p style='color:green'>✅ Fatura PAGA de R$ 39.90 inserida! (Deve aparecer em 'Recebido')</p>";
} else {
    echo "<p style='color:red'>Erro ao inserir pago: " . $conn->error . "</p>";
}

echo "<hr><a href='controle_financeiro_sistema.php'>Voltar para o Painel Financeiro</a>";
?>