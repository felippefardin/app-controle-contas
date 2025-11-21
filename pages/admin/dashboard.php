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

// --- LÓGICA DE PESQUISA ---
$busca = trim($_GET['busca'] ?? '');

// Query base com JOIN
$sql = "SELECT t.id, t.nome, t.nome_empresa, t.status_assinatura, t.data_criacao, u.documento 
        FROM tenants t 
        LEFT JOIN usuarios u ON t.usuario_id = u.id";

if (!empty($busca)) {
    $term = $master_conn->real_escape_string($busca);
    $sql .= " WHERE t.nome LIKE '%$term%' 
              OR t.nome_empresa LIKE '%$term%' 
              OR u.documento LIKE '%$term%'";
}

$sql .= " ORDER BY t.id DESC";

$tenants_result = $master_conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Master</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background-color: #0e0e0e;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .topbar {
            width: 100%;
            background: #1a1a1a;
            padding: 15px 25px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.4);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .topbar span {
            font-weight: bold;
            color: #00bfff;
        }

        .topbar a {
            color: #eee;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 4px;
            background-color: #333;
            transition: 0.2s;
            font-size: 14px;
        }

        .topbar a:hover {
            background-color: #444;
        }

        .topbar .logout {
            background-color: #d13c3c;
        }

        .topbar .logout:hover {
            background-color: #ff4a4a;
        }

        .container {
            max-width: 1300px;
            margin: 30px auto;
            background: #121212;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255,255,255,0.05);
        }

        h1 {
            margin-bottom: 20px;
            color: #00bfff;
            text-align: center;
        }

        /* BUSCA */
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            justify-content: center;
        }

        .search-input {
            padding: 12px;
            width: 350px;
            border-radius: 5px;
            border: 1px solid #333;
            background-color: #1c1c1c;
            color: #fff;
            font-size: 15px;
        }

        .btn-search {
            padding: 12px 17px;
            background-color: #28a745;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-search:hover {
            background-color: #23963c;
        }

        .btn-clear {
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            padding-left: 8px;
        }

        .btn-clear:hover {
            color: #fff;
        }

        /* TABELA DESKTOP */
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background-color: #1e1e1e;
            color: #00bfff;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #2a2a2a;
        }

        tr:hover {
            background-color: rgba(255,255,255,0.03);
        }

        .btn-gerenciar {
            background-color: #00bfff;
            color: #fff;
            padding: 7px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }

        .btn-gerenciar:hover {
            background-color: #009acd;
        }

        /* RESPONSIVO - MOBILE */
        @media (max-width: 900px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            .topbar {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                text-align: center;
            }

            .search-container {
                flex-direction: column;
                width: 100%;
            }

            .search-input {
                width: 100%;
            }

            /* TABELA vira CARDS */
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead {
                display: none;
            }

            tr {
                background: #1a1a1a;
                margin-bottom: 15px;
                padding: 15px;
                border-radius: 8px;
            }

            td {
                border: none;
                padding: 7px 0;
            }

            td:before {
                content: attr(data-label);
                font-weight: bold;
                color: #00bfff;
                display: block;
                margin-bottom: 3px;
                font-size: 14px;
            }
        }
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

        <form method="GET" class="search-container">
            <input type="text" name="busca" class="search-input" placeholder="Nome, Empresa, CPF ou CNPJ..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Pesquisar</button>

            <?php if (!empty($busca)): ?>
                <a href="dashboard.php" class="btn-clear">Limpar filtro</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente / Empresa</th>
                    <th>CPF / CNPJ</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($tenants_result && $tenants_result->num_rows > 0): ?>
                <?php while ($tenant = $tenants_result->fetch_assoc()): ?>
                    <?php
                        $nomeExibir = !empty($tenant['nome_empresa']) ? $tenant['nome_empresa'] : $tenant['nome'];
                        $nomeExibir = $nomeExibir ?: 'Cliente Sem Nome';
                        $doc = $tenant['documento'] ?? '-';
                    ?>
                    <tr>
                        <td data-label="ID">#<?= $tenant['id']; ?></td>
                        <td data-label="Cliente / Empresa" style="color:#fff;font-weight:bold;"><?= htmlspecialchars($nomeExibir); ?></td>
                        <td data-label="CPF / CNPJ"><?= htmlspecialchars($doc); ?></td>
                        <td data-label="Status"><?= htmlspecialchars($tenant['status_assinatura'] ?? '-'); ?></td>
                        <td data-label="Data Cadastro">
                            <?= !empty($tenant['data_criacao']) ? date('d/m/Y', strtotime($tenant['data_criacao'])) : '-'; ?>
                        </td>
                        <td data-label="Ação">
                            <a class="btn-gerenciar" href="../../actions/admin_impersonate.php?tenant_id=<?= $tenant['id']; ?>">
                                Gerenciar Conta
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#aaa;">
                        Nenhum cliente encontrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div>

</body>
</html>
