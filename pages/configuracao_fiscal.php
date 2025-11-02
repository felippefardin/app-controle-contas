<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

// ✅ CORRIGIDO: Usa a função getTenantConnection() consistentemente
$conn = getTenantConnection(); 
if ($conn === null) {
    die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO
$usuario_logado = $_SESSION['usuario_logado'];
$usuarioId = $usuario_logado['id'];
$perfil = $usuario_logado['nivel_acesso'];

include('../includes/header.php');

// ✅ 3. BUSCA A CONFIGURAÇÃO FISCAL
// ✅ CORRIGIDO: Usa $conn consistentemente
$stmt = $conn->prepare("SELECT * FROM empresa_config WHERE id = ?");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();
$config = $result->fetch_assoc() ?: [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações Fiscais</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ... (seu <style> ... ) */
    </style>
</head>
<body>

<div class="container mt-4">
    <h2><i class="fa-solid fa-file-invoice-dollar"></i> Configurações Fiscais</h2>
    <p>Preencha os dados da sua empresa para emissão de notas fiscais.</p>

    <form action="../actions/salvar_configuracao_fiscal.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($usuarioId ?? '') ?>">

        <div class="card">
            <div class="card-header">Parâmetros Fiscais</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="regime_tributario">Regime Tributário</label>
                        <select class="form-control" id="regime_tributario" name="regime_tributario" required>
                            <option value="1" <?= ($config['regime_tributario'] ?? '') == 1 ? 'selected' : '' ?>>Simples Nacional</option>
                            <option value="3" <?= ($config['regime_tributario'] ?? '') == 3 ? 'selected' : '' ?>>Regime Normal</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="ambiente">Ambiente de Emissão</label>
                        <select class="form-control" id="ambiente" name="ambiente" required>
                            <option value="2" <?= ((isset($config['ambiente']) && $config['ambiente'] == 2) || !isset($config['ambiente'])) ? 'selected' : '' ?>>Homologação (Testes)</option>
                            <option value="1" <?= (isset($config['ambiente']) && $config['ambiente'] == 1) ? 'selected' : '' ?>>Produção (Real)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="csc_id">ID do CSC (Token)</label>
                        <input type="text" class="form-control" id="csc_id" name="csc_id" value="<?= htmlspecialchars($config['csc_id'] ?? '') ?>" placeholder="Ex: 000001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="csc">CSC (Código de Segurança)</label>
                        <input type="text" class="form-control" id="csc" name="csc" value="<?= htmlspecialchars($config['csc'] ?? '') ?>" placeholder="Token fornecido pela SEFAZ">
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align:center;">
            <button type="submit" class="btn-primary">Salvar Configurações</button>
        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>