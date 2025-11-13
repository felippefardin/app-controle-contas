<?php
// includes/tenant_utils.php

require_once __DIR__ . '/../database.php'; // Importa getMasterConnection() e getTenantConnection()

/**
 * 🔍 Busca tenant pelo ID do usuário logado
 */
function getTenantByUserId($userId) {
    $conn = getMasterConnection();

    $sql = "SELECT * FROM tenants WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);

    $stmt->execute();
    $tenant = $stmt->get_result()->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $tenant ?: null;
}

/**
 * 🔒 Valida status da assinatura
 */
function validarStatusAssinatura($tenant) {
    if (!$tenant) {
        return "erro";
    }

    // Status inválido
    if ($tenant['status_assinatura'] === 'inativo') {
        return "inativo";
    }

    // Trial expirado
    if ($tenant['status_assinatura'] === 'trial') {
        $inicio = new DateTime($tenant['data_inicio_teste']);
        $hoje = new DateTime();

        $dias = $inicio->diff($hoje)->days;

        if ($dias > 7) {
            return "trial_expirado";
        }
    }

    return "ok";
}

/**
 * 🔌 Carrega credenciais do banco do tenant na sessão
 */
function carregarTenantNaSessao($tenant) {
    if (!$tenant) {
        return false;
    }

    $_SESSION['tenant_id'] = $tenant['tenant_id'];

    $_SESSION['tenant_db'] = [
        "db_host"     => $tenant['db_host'],
        "db_user"     => $tenant['db_user'],
        "db_password" => $tenant['db_password'],
        "db_database" => $tenant['db_database']
    ];

    return true;
}
