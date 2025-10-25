<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 2. VERIFICAÇÃO DE LOGIN
if (!isset($_SESSION['usuario_principal']) || !isset($_SESSION['usuario'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Pega o ID do usuário principal da sessão
$mainUserId = $_SESSION['usuario_principal']['id'];

// A consulta agora busca a configuração correspondente ao ID do usuário principal
$stmt = $conn->prepare("SELECT * FROM empresa_config WHERE id = ?");
$stmt->bind_param("i", $mainUserId);
$stmt->execute();
$result = $stmt->get_result();
$config = $result->fetch_assoc();

if (!$config) {
    // Se não houver configuração, inicializa um array vazio para não dar erro no formulário
    $config = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Fiscais - App Contas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* ===== Estilo geral (Dark Mode) ===== */
    * {
        box-sizing: border-box;
    }

    body {
        background-color: #121212;
        color: #eee;
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    /* ===== Container principal ===== */
    .container {
        background-color: #1e1e1e;
        border-radius: 10px;
        padding: 30px;
        margin: 30px auto;
        width: 95%;
        max-width: 1200px; /* full para desktop */
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
    }

    h2 {
        color: #00bfff;
        text-align: center;
        margin-bottom: 15px;
        font-weight: 600;
    }

    p {
        text-align: center;
        color: #bbb;
        margin-bottom: 25px;
    }

    /* ===== Cards ===== */
    .card {
        background-color: #222;
        border: 1px solid #333;
        border-radius: 8px;
        margin-bottom: 25px;
    }

    .card-header {
        background-color: #2a2a2a;
        border-bottom: 1px solid #333;
        color: #00bfff;
        font-weight: bold;
        padding: 12px 18px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .card-body {
        padding: 20px;
    }

    /* ===== Labels e Inputs ===== */
    .form-label {
        color: #ccc;
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
        background-color: #2a2a2a;
        color: #eee;
        border: 1px solid #444;
        border-radius: 6px;
        padding: 10px;
        width: 100%;
        transition: all 0.3s;
    }

    input.form-control:focus,
    select.form-control:focus,
    textarea.form-control:focus {
        background-color: #333;
        color: #fff;
        border-color: #00bfff;
        box-shadow: 0 0 5px rgba(0, 191, 255, 0.5);
    }

    /* ===== Botões ===== */
    .btn-primary {
        background-color: #00bfff;
        border: none;
        border-radius: 6px;
        padding: 10px 25px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0095cc;
        transform: translateY(-1px);
    }

    /* ===== Alertas ===== */
    .alert {
        border-radius: 6px;
        padding: 12px 20px;
        margin-bottom: 20px;
    }

    /* ===== Responsividade ===== */
    @media (max-width: 992px) {
        .container {
            width: 95%;
            padding: 20px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
        }

        .col-md-6,
        .col-md-4,
        .col-md-8 {
            flex: 1 1 100%;
            max-width: 100%;
        }

        h2 {
            font-size: 1.4rem;
        }
    }

    @media (max-width: 576px) {
        body {
            padding: 10px;
        }

        .container {
            padding: 15px;
            margin: 10px;
            box-shadow: none;
        }

        .btn-primary {
            width: 100%;
            font-size: 1rem;
            padding: 12px;
        }

        .card-header {
            font-size: 1rem;
        }
    }
</style>

</head>
<body>

<?php require_once '../includes/header.php'; ?>

<div class="container mt-4">
    <h2><i class="fa-solid fa-file-invoice-dollar"></i> Configurações Fiscais da Empresa</h2>
    <p>Preencha os dados da sua empresa para a emissão de notas fiscais.</p>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Configurações salvas com sucesso!
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            Erro ao salvar as configurações: <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form action="../actions/salvar_configuracao_fiscal.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($mainUserId) ?>">
        <div class="card mb-4">
            <div class="card-header">
                Dados da Empresa
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="razao_social" class="form-label">Razão Social</label>
                        <input type="text" class="form-control" id="razao_social" name="razao_social" value="<?= htmlspecialchars($config['razao_social'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fantasia" class="form-label">Nome Fantasia</label>
                        <input type="text" class="form-control" id="fantasia" name="fantasia" value="<?= htmlspecialchars($config['fantasia'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cnpj" class="form-label">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?= htmlspecialchars($config['cnpj'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ie" class="form-label">Inscrição Estadual (IE)</label>
                        <input type="text" class="form-control" id="ie" name="ie" value="<?= htmlspecialchars($config['ie'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Endereço
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="logradouro" class="form-label">Logradouro</label>
                        <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= htmlspecialchars($config['logradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($config['numero'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($config['bairro'] ?? '') ?>">
                    </div>
                     <div class="col-md-4 mb-3">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($config['cep'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="municipio" class="form-label">Município</label>
                        <input type="text" class="form-control" id="municipio" name="municipio" value="<?= htmlspecialchars($config['municipio'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="uf" class="form-label">UF</label>
                        <input type="text" class="form-control" id="uf" name="uf" maxlength="2" value="<?= htmlspecialchars($config['uf'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cod_municipio" class="form-label">Código IBGE do Município</label>
                        <input type="text" class="form-control" id="cod_municipio" name="cod_municipio" value="<?= htmlspecialchars($config['cod_municipio'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>


        <div class="card mb-4">
            <div class="card-header">
                Parâmetros Fiscais
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="regime_tributario" class="form-label">Regime Tributário</label>
                        <select class="form-control" id="regime_tributario" name="regime_tributario" required>
                            <option value="1" <?= ($config['regime_tributario'] ?? '') == 1 ? 'selected' : '' ?>>Simples Nacional</option>
                            <option value="3" <?= ($config['regime_tributario'] ?? '') == 3 ? 'selected' : '' ?>>Regime Normal</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="csc_id" class="form-label">ID do CSC (Token)</label>
                        <input type="text" class="form-control" id="csc_id" name="csc_id" value="<?= htmlspecialchars($config['csc_id'] ?? '') ?>" placeholder="Ex: 000001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="csc" class="form-label">CSC (Código de Segurança do Contribuinte)</label>
                        <input type="text" class="form-control" id="csc" name="csc" value="<?= htmlspecialchars($config['csc'] ?? '') ?>" placeholder="Token fornecido pela SEFAZ">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Certificado Digital A1
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="certificado_a1" class="form-label">Arquivo do Certificado (.pfx)</label>
                    <input class="form-control" type="file" id="certificado_a1" name="certificado_a1" accept=".pfx">
                    <?php if (!empty($config['certificado_a1_path'])): ?>
                        <small class="form-text text-muted">Um certificado já foi enviado. Envie um novo apenas se desejar substituí-lo.</small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="certificado_senha" class="form-label">Senha do Certificado</label>
                    <input type="password" class="form-control" id="certificado_senha" name="certificado_senha">
                     <small class="form-text text-muted">Preencha este campo para salvar ou atualizar a senha.</small>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>