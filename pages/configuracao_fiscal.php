<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) { // ❗️ Verificação atualizada
header('Location: login.php');
exit;
}

// ✅ CORRIGIDO: Usa a função getTenantConnection() consistentemente
$conn = getTenantConnection(); 
if ($conn === null) {
die("Falha ao obter a conexão com o banco de dados do cliente.");
}

// ✅ 2. PEGA OS DADOS DO USUÁRIO DA SESSÃO
// ❗️❗️ INÍCIO DA CORREÇÃO ❗️❗️
// As variáveis de sessão agora são lidas diretamente
$usuarioId = $_SESSION['usuario_id']; // Linha 19 corrigida
$perfil = $_SESSION['nivel_acesso']; // Linha 20 corrigida
// ❗️❗️ FIM DA CORREÇÃO ❗️❗️

include('../includes/header.php');

// ✅ 3. BUSCA A CONFIGURAÇÃO FISCAL
// ❗️❗️ CORREÇÃO SQL ❗️❗️
// A tabela correta no seu schema.sql é 'configuracoes_tenant'.
// A lógica de busca é por 'chave', não por 'id' de usuário.
$sql = "SELECT chave, valor FROM configuracoes_tenant WHERE chave IN ('regime_tributario', 'ambiente', 'csc_id', 'csc')";
$result = $conn->query($sql);
$config = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $config[$row['chave']] = $row['valor'];
    }
}
// Se $config estiver vazio, os valores padrão no HTML serão usados.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações Fiscais</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* (O seu CSS dark mode vai aqui) */
        body { background-color: #121212; color: #eee; }
        .container { background-color: #222; padding: 25px; border-radius: 8px; margin-top: 30px; max-width: 900px; }
        h2 { color: #00bfff; border-bottom: 2px solid #0af; padding-bottom: 10px; margin-bottom: 1rem; }
        p { color: #ccc; }
        .form-control, .custom-select { background-color: #333; color: #eee; border: 1px solid #444; }
        .form-control:focus, .custom-select:focus { background-color: #333; color: #eee; border-color: #0af; box-shadow: none; }
        .card { background-color: #1e1e1e; border: 1px solid #333; }
        .card-header { background-color: #00bfff22; border-bottom: 1px solid #00bfff55; color: #00bfff; font-weight: 600; }
        .card-body { background-color: #2a2a2a; }
        label { color: #ccc; font-weight: bold; }
        .btn-primary { 
            background-color: #00bfff; 
            border: none; 
            padding: 10px 20px; 
            font-weight: bold; 
            color: #121212;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary:hover { background-color: #0099cc; color: #fff; }
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

        <div style="text-align:center; margin-top: 20px;">
            <button type="submit" class="btn-primary">Salvar Configurações</button>
        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>