<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1️⃣ Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

// 2️⃣ Obtém conexão do tenant
$conn = getTenantConnection();
if ($conn === null) {
    die("❌ Falha ao obter a conexão com o banco de dados do cliente.");
}

// 3️⃣ Pega o ID do usuário logado
$id_usuario = $_SESSION['usuario_logado']['id'];

// 4️⃣ Busca planos disponíveis
$stmt_planos = $conn->query("SELECT id, nome, valor, ciclo FROM planos ORDER BY valor ASC");
$planos = $stmt_planos->fetch_all(MYSQLI_ASSOC);

// 5️⃣ Verifica se a coluna 'data_criacao' existe antes de ordenar por ela
$colunaExiste = false;
$checkColumn = $conn->query("SHOW COLUMNS FROM assinaturas LIKE 'data_criacao'");
if ($checkColumn && $checkColumn->num_rows > 0) {
    $colunaExiste = true;
}

// 6️⃣ Monta a query de assinatura conforme a estrutura da tabela
if ($colunaExiste) {
    $sql = "SELECT id, plano, valor, status 
            FROM assinaturas 
            WHERE id_usuario = ? AND status = 'ativa' 
            ORDER BY data_criacao DESC 
            LIMIT 1";
} else {
    $sql = "SELECT id, plano, valor, status 
            FROM assinaturas 
            WHERE id_usuario = ? AND status = 'ativa' 
            ORDER BY id DESC 
            LIMIT 1";
}

$stmt_assinatura = $conn->prepare($sql);
$stmt_assinatura->bind_param("i", $id_usuario);
$stmt_assinatura->execute();
$assinaturaAtual = $stmt_assinatura->get_result()->fetch_assoc();
$stmt_assinatura->close();

// 7️⃣ Inclui cabeçalho
include('../includes/header.php');

$mensagem = $_SESSION['success_message'] ?? '';
$erro = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Minha Assinatura</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 30px auto;
            padding: 20px;
            background-color: #1f1f1f;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }
        h2 {
            color: #00bfff;
            border-bottom: 2px solid #00bfff;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.8rem;
            text-align: center;
        }
        h4 {
            color: #eee;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        p { margin-bottom: 15px; line-height: 1.6; }

        .status-card {
            background-color: #222;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #28a745;
            margin-bottom: 20px;
        }
        .status-card.inativo { border-left-color: #dc3545; }
        .status-card strong { color: #00bfff; }
        .status-card .status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        .status-card .status-ativo { background-color: #28a745; color: white; }
        .status-card .status-pendente { background-color: #ffc107; color: #333; }
        .status-card .status-cancelada { background-color: #dc3545; color: white; }

        form {
            background-color: #222;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #ccc;
        }
        select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #444;
            margin-bottom: 15px;
            box-sizing: border-box;
            font-size: 16px;
            background-color: #333;
            color: #eee;
        }
        button {
            border: none;
            padding: 10px 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
            background-color: #00bfff;
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        button:hover { background-color: #0099cc; }
        .btn-cancelar {
            background-color: #dc3545 !important;
            margin-top: 25px;
        }
        .btn-cancelar:hover { background-color: #a02a2a !important; }

        .mensagem { background-color: #28a745; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .erro { background-color: #cc4444; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-gem"></i> Gerenciar Minha Assinatura</h2>

    <?php if ($mensagem): ?>
        <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($assinaturaAtual): 
        $statusClass = match ($assinaturaAtual['status']) {
            'ativa' => 'status-ativo',
            'pendente' => 'status-pendente',
            default => 'status-cancelada'
        };
    ?>
        <div class="status-card">
            <h4><i class="fa-solid fa-check-circle"></i> Status Atual da Assinatura</h4>
            <p>Plano: <strong><?= htmlspecialchars($assinaturaAtual['plano']) ?></strong></p>
            <p>Valor Mensal: <strong>R$ <?= number_format($assinaturaAtual['valor'], 2, ',', '.') ?></strong></p>
            <p>Status: <span class="status <?= $statusClass ?>"><?= ucfirst(htmlspecialchars($assinaturaAtual['status'])) ?></span></p>
        </div>

        <form action="../actions/alterar_plano_action.php" method="POST">
            <h4>Alterar Plano</h4>
            <label for="novo_plano">Selecione o Novo Plano:</label>
            <select name="novo_plano_id" id="novo_plano" required>
                <option value="">-- Escolha um plano --</option>
                <?php foreach ($planos as $plano): 
                    if ($plano['nome'] !== $assinaturaAtual['plano']): ?>
                        <option value="<?= $plano['id'] ?>">
                            <?= htmlspecialchars($plano['nome']) ?> - R$ <?= number_format($plano['valor'], 2, ',', '.') ?>/<?= ucfirst(htmlspecialchars($plano['ciclo'])) ?>
                        </option>
                <?php endif; endforeach; ?>
            </select>
            <button type="submit"><i class="fa-solid fa-arrow-up"></i> Confirmar Migração de Plano</button>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../actions/cancelar_assinatura.php" class="btn-cancelar" onclick="return confirm('Tem certeza que deseja cancelar sua assinatura? Isso pode afetar o acesso ao sistema.');">
                <i class="fa-solid fa-times-circle"></i> Cancelar Assinatura
            </a>
            <p style="font-size: 0.9rem; color: #999; margin-top: 10px;">O cancelamento entra em vigor no próximo ciclo de pagamento.</p>
        </div>

    <?php else: ?>
        <div class="status-card inativo">
            <h4><i class="fa-solid fa-exclamation-triangle"></i> Sem Assinatura Ativa</h4>
            <p>Você não possui uma assinatura ativa no momento. Para continuar utilizando todos os recursos, por favor, assine um plano.</p>
            <p style="text-align: center; margin-top: 20px;">
                <a href="assinar.php" class="btn-cancelar" style="background-color: #28a745;">
                    <i class="fa-solid fa-check"></i> Assinar um Plano Agora
                </a>
            </p>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
