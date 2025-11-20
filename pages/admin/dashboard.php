<?php
require_once '../../includes/session_init.php';
include('../../database.php');

// Proteção: Apenas super admin
if (!isset($_SESSION['super_admin'])) {
    header('Location: ../login.php');
    exit;
}

$admin = $_SESSION['super_admin'];
$master_conn = getMasterConnection();

// Busca os tenants
$query = "SELECT id, nome, nome_empresa, status_assinatura, data_criacao FROM tenants";
$tenants_result = $master_conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Master</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background-color: #121212; color: #eee; font-family: Arial, sans-serif; }
        .container { padding: 30px; }
        /* Ajuste da Barra Superior */
        .topbar { 
            background: #1f1f1f; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: flex-end; 
            align-items: center; 
            gap: 15px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .topbar span { font-weight: bold; color: #00bfff; }
        .topbar a { 
            color: #eee; 
            text-decoration: none; 
            padding: 8px 12px; 
            border-radius: 4px; 
            background-color: #333; 
            transition: 0.2s;
        }
        .topbar a:hover { background-color: #444; }
        .topbar a.logout { background-color: #cc4444; color: white; }
        .topbar a.logout:hover { background-color: #ff5555; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 10px; text-align: left; }
        th { background-color: #222; color: #00bfff; }
        a.btn-gerenciar { 
            background-color: #00bfff; color: #fff; padding: 5px 10px; 
            text-decoration: none; border-radius: 4px; font-size: 0.9em; 
        }
        a.btn-gerenciar:hover { background-color: #009acd; }
    </style>
</head>
<body>

    <div class="topbar">
        <span>Master: <?= htmlspecialchars($admin['email'] ?? 'Admin') ?></span>
        <a href="redefinir_senha.php">Alterar Senha</a>
        <a href="../logout.php" class="logout">Sair</a>
    </div>

    <div class="container">
        <h1>Painel de Controle Master</h1>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente / Empresa</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tenants_result): ?>
                    <?php while ($tenant = $tenants_result->fetch_assoc()): ?>
                        <?php 
                            $nomeExibir = !empty($tenant['nome_empresa']) ? $tenant['nome_empresa'] : $tenant['nome'];
                            $nomeExibir = $nomeExibir ?: 'Cliente Sem Nome';
                        ?>
                        <tr>
                            <td>#<?php echo $tenant['id']; ?></td>
                            <td style="color: #fff; font-weight: bold;">
                                <?php echo htmlspecialchars($nomeExibir); ?>
                            </td>
                            <td><?php echo htmlspecialchars($tenant['status_assinatura'] ?? '-'); ?></td>
                            <td>
                                <?php echo !empty($tenant['data_criacao']) ? date('d/m/Y', strtotime($tenant['data_criacao'])) : '-'; ?>
                            </td>
                            <td>
                                <a class="btn-gerenciar" href="../../actions/admin_impersonate.php?tenant_id=<?php echo $tenant['id']; ?>">
                                    Gerenciar Conta
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Nenhum cliente encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>