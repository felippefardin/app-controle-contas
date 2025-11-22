<?php
session_start();
require_once '../database.php';

// 1. Verifica permissão
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['tenant_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$connMaster = getMasterConnection();
$connTenant = getTenantConnection();

if (!$connMaster || !$connTenant) {
    $_SESSION['erro_extra'] = "Erro de conexão com o banco de dados.";
    header("Location: ../pages/minha_assinatura.php");
    exit;
}

$tenant_id = $_SESSION['tenant_id'];
$qtd_remover = (int)($_POST['qtd_remover'] ?? 1);

try {
    // 2. Busca dados atuais do plano no Master
    $stmt = $connMaster->prepare("SELECT plano_atual, usuarios_extras FROM tenants WHERE tenant_id = ?");
    $stmt->bind_param("s", $tenant_id);
    $stmt->execute();
    $dados = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $extras_atuais = (int)($dados['usuarios_extras'] ?? 0);
    $plano_atual = $dados['plano_atual'] ?? 'basico';

    // Se não tem extras para remover
    if ($extras_atuais < $qtd_remover) {
        throw new Exception("Você não possui usuários extras suficientes para remover.");
    }

    // 3. Calcula os limites
    $mapa_planos = [
        'basico'    => 3,
        'plus'      => 6,
        'essencial' => 16
    ];
    
    $limite_base = $mapa_planos[$plano_atual] ?? 3;
    
    // Limite atual = Base + Extras
    // Novo limite se a remoção ocorrer = Base + (Extras - QtdRemover)
    $novo_limite_total = $limite_base + ($extras_atuais - $qtd_remover);

    // 4. Conta usuários ativos no Tenant para evitar inconsistência
    $resultCount = $connTenant->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'ativo'");
    $rowC = $resultCount->fetch_assoc();
    $usuarios_ativos = (int)$rowC['total'];

    // 5. Valida se pode remover
    if ($usuarios_ativos > $novo_limite_total) {
        throw new Exception("Não é possível reduzir o plano. Você tem <b>$usuarios_ativos usuários ativos</b>, mas o novo limite seria <b>$novo_limite_total</b>. Inative usuários na página de gestão antes de cancelar o extra.");
    }

    // 6. Atualiza o banco Master
    $stmtUpdate = $connMaster->prepare("UPDATE tenants SET usuarios_extras = usuarios_extras - ? WHERE tenant_id = ?");
    $stmtUpdate->bind_param("is", $qtd_remover, $tenant_id);
    
    if ($stmtUpdate->execute()) {
        $_SESSION['sucesso_extra'] = "Pacote de usuário extra removido com sucesso. Seu novo limite é $novo_limite_total.";
    } else {
        throw new Exception("Erro ao atualizar o banco de dados.");
    }
    $stmtUpdate->close();

} catch (Exception $e) {
    $_SESSION['erro_extra'] = $e->getMessage();
}

$connMaster->close();
$connTenant->close();

header("Location: ../pages/minha_assinatura.php");
exit;
?>