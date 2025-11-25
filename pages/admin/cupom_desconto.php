<?php
require_once '../../includes/session_init.php';
include('../../database.php');

// Proteção: Apenas super admin
if (!isset($_SESSION['super_admin'])) {
    header('Location: ../login.php');
    exit;
}

$master_conn = getMasterConnection();
$msg = "";

// --- LÓGICA DE ADICIONAR CUPOM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    $codigo = strtoupper(trim($_POST['codigo']));
    $tipo = $_POST['tipo'];
    $valor = floatval(str_replace(',', '.', $_POST['valor']));
    $data_exp = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : NULL;
    $desc = trim($_POST['descricao']);

    $stmt = $master_conn->prepare("INSERT INTO cupons_desconto (codigo, tipo_desconto, valor, data_expiracao, descricao) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $codigo, $tipo, $valor, $data_exp, $desc);
    
    if ($stmt->execute()) {
        $msg = "<div class='alert success'>Cupom criado com sucesso!</div>";
    } else {
        $msg = "<div class='alert error'>Erro ao criar cupom (Código já existe?).</div>";
    }
}

// --- LÓGICA DE EXCLUIR/TOGGLE ---
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $master_conn->query("DELETE FROM cupons_desconto WHERE id = $id");
    header("Location: cupom_desconto.php");
    exit;
}

// --- LISTAGEM ---
$cupons = $master_conn->query("SELECT * FROM cupons_desconto ORDER BY criado_em DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Cupons</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #0e0e0e; color: #eee; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #121212; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
        h1, h2 { color: #00bfff; text-align: center; }
        
        /* Form */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #aaa; }
        input, select, textarea { width: 100%; padding: 10px; background: #1c1c1c; border: 1px solid #333; color: #fff; border-radius: 4px; box-sizing: border-box; }
        input:focus { border-color: #00bfff; outline: none; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; color: #fff; font-weight: bold; }
        .btn-save { background: #28a745; width: 100%; margin-top: 10px; }
        .btn-save:hover { background: #218838; }
        .btn-back { background: #555; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 30px; background: #1a1a1a; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #252525; color: #00bfff; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background: #222; }
        
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .success { background: rgba(40, 167, 69, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .error { background: rgba(220, 53, 69, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
        
        <h1>Gerenciar Cupons de Desconto</h1>
        <?= $msg ?>

        <form method="POST" style="background: #1a1a1a; padding: 20px; border-radius: 8px; border: 1px solid #333;">
            <input type="hidden" name="acao" value="criar">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 1;">
                    <label>Código do Cupom</label>
                    <input type="text" name="codigo" placeholder="EX: PROMOWEB" required style="text-transform: uppercase;">
                </div>
                <div style="flex: 1;">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="porcentagem">Porcentagem (%)</option>
                        <option value="fixo">Valor Fixo (R$)</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Valor</label>
                    <input type="number" name="valor" step="0.01" placeholder="10.00" required>
                </div>
                <div style="flex: 1;">
                    <label>Validade (Opcional)</label>
                    <input type="date" name="data_expiracao">
                </div>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Descrição</label>
                <textarea name="descricao" rows="2" placeholder="Descrição interna do cupom..."></textarea>
            </div>
            <button type="submit" class="btn btn-save"><i class="fas fa-plus"></i> Criar Cupom</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Desconto</th>
                    <th>Validade</th>
                    <th>Descrição</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php while($c = $cupons->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: bold; color: #fff;"><?= htmlspecialchars($c['codigo']) ?></td>
                    <td>
                        <?= ($c['tipo_desconto'] == 'porcentagem') ? intval($c['valor']) . '%' : 'R$ ' . number_format($c['valor'], 2, ',', '.') ?>
                    </td>
                    <td>
                        <?= $c['data_expiracao'] ? date('d/m/Y', strtotime($c['data_expiracao'])) : '<span style="color:#2ecc71">Indeterminado</span>' ?>
                    </td>
                    <td style="font-size: 0.9rem; color: #aaa;"><?= htmlspecialchars($c['descricao']) ?></td>
                    <td>
                        <a href="?excluir=<?= $c['id'] ?>" onclick="return confirm('Excluir este cupom?')" style="color: #e74c3c;"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>