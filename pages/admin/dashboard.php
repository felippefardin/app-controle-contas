<?php
require_once '../../includes/session_init.php';
include('../../database.php'); // Inclui a conexão com o banco de dados master

// Apenas o super admin pode acessar esta página
if (!isset($_SESSION['super_admin'])) {
    header('Location: ../login.php');
    exit;
}

// Busca todos os tenants (clientes) do banco de dados master
$master_conn = getMasterConnection();
$tenants_result = $master_conn->query("SELECT id, nome_empresa, status_assinatura, data_criacao FROM tenants");

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard do Administrador</title>
    <link rel="stylesheet" href="../../assets/css/style.css"> </head>
<body>
    <div class="container">
        <h1>Dashboard do Administrador</h1>
        <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['super_admin']['nome']); ?>!</p>

        <h2>Clientes (Tenants)</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome da Empresa</th>
                    <th>Status da Assinatura</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tenant = $tenants_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $tenant['id']; ?></td>
                        <td><?php echo htmlspecialchars($tenant['nome_empresa']); ?></td>
                        <td><?php echo htmlspecialchars($tenant['status_assinatura']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($tenant['data_criacao'])); ?></td>
                        <td>
                            <a href="../actions/incorporar_usuario.php?tenant_id=<?php echo $tenant['id']; ?>">Gerenciar</a>
                            </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="../logout.php">Sair</a>
    </div>
</body>
</html>