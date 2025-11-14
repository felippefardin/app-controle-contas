<?php
// includes/tenant_utils.php
require_once __DIR__ . '/../database.php';

/**
 * Log utilitário
 */
function log_debug($msg) {
    error_log("[TENANT_UTILS] " . $msg);
}

/**
 * Retorna tenant correspondente ao usuário (usuario_id do master)
 */
function getTenantByUserId($userId) {
    $conn = getMasterConnection();

    $sql = "SELECT * FROM tenants WHERE usuario_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $tenant = $stmt->get_result()->fetch_assoc();

    $stmt->close();
    $conn->close();

    if ($tenant) {
        log_debug("Tenant encontrado para usuario_id={$userId}: {$tenant['tenant_id']}");
    } else {
        log_debug("Nenhum tenant encontrado para usuario_id={$userId}");
    }

    return $tenant ?: null;
}

/**
 * Valida status da assinatura (trial/inativo)
 */
function validarStatusAssinatura($tenant) {
    if (!$tenant) return "erro";

    if (isset($tenant['status_assinatura']) && $tenant['status_assinatura'] === 'inativo') {
        return "inativo";
    }

    if (isset($tenant['status_assinatura']) && $tenant['status_assinatura'] === 'trial') {
        // se data_inicio_teste não existir retorna ok (compatibilidade)
        if (empty($tenant['data_inicio_teste'])) return "ok";

        try {
            $inicio = new DateTime($tenant['data_inicio_teste']);
            $hoje = new DateTime();
            $dias = $inicio->diff($hoje)->days;

            // regra: 15 dias de trial
            if ($dias > 15) {
                return "trial_expirado";
            }
        } catch (Exception $e) {
            log_debug("Erro ao validar data_inicio_teste: " . $e->getMessage());
            return "ok";
        }
    }

    return "ok";
}

/**
 * Carrega credenciais do tenant na sessão
 */
function carregarTenantNaSessao($tenant) {
    if (!$tenant) return false;

    $_SESSION['tenant_id'] = $tenant['tenant_id'];

    $_SESSION['tenant_db'] = [
        "db_host"     => $tenant['db_host'],
        "db_user"     => $tenant['db_user'],
        "db_password" => $tenant['db_password'],
        "db_database" => $tenant['db_database']
    ];

    log_debug("Credenciais do tenant salvas na sessão: " . ($tenant['db_database'] ?? 'n/a'));
    return true;
}
