<?php
require_once '../../includes/session_init.php';
include('../../database.php'); // Inclui a conexão com o banco de dados master

// Apenas o super admin pode acessar esta página
if (!isset($_SESSION['super_admin'])) {
    header('Location: ../login.php');
    exit;
}

$admin = $_SESSION['super_admin'];

// Busca todos os tenants (clientes) do banco de dados master
$master_conn = getMasterConnection();
$tenants_result = $master_conn->query("SELECT id, nome_empresa, status_assinatura, data_criacao FROM tenants");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard do Administrador</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
        }
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            background-color: #1f1f1f;
            padding: 15px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .topbar span {
            color: #00bfff;
            font-weight: bold;
            margin-right: 15px;
        }
        .topbar a {
            background-color: #00bfff;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-left: 8px;
            transition: 0.3s;
        }
        .topbar a:hover {
            background-color: #008ccc;
        }
        .topbar a.logout {
            background-color: #cc4444;
        }
        .topbar a.logout:hover {
            background-color: #a83232;
        }
        .container {
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #222;
            color: #00bfff;
        }
        tr:nth-child(even) {
            background-color: #1b1b1b;
        }
        a.gerenciar {
            color: #00bfff;
            text-decoration: none;
            font-weight: bold;
        }
        a.gerenciar:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- 🔹 Barra Superior -->
    <div class="topbar">
        <span><?php echo htmlspecialchars($admin['email']); ?></span>
        <a href="redefinir_senha.php">Alterar Senha</a>
        <a href="../logout.php" class="logout">Sair</a>
    </div>

    <div class="container">
        <h1>Dashboard do Administrador</h1>
        <p>Bem-vindo, <?php echo htmlspecialchars($admin['nome']); ?>!</p>

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
                            <a class="gerenciar" href="../../actions/admin_impersonate.php?tenant_id=<?php echo $tenant['id']; ?>">Gerenciar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
