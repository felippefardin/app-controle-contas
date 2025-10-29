<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// ✅ 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO E PEGA A CONEXÃO CORRETA
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

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
        /* ===== Estilo Geral ===== */
        * { box-sizing: border-box; }
        body {
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #00bfff;
            text-align: center;
            margin-bottom: 20px;
        }
        p { text-align: center; color: #bbb; margin-bottom: 25px; }

        .container {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 30px;
            margin: 0 auto;
            width: 95%;
            max-width: 1000px;
            box-shadow: 0 0 15px rgba(0,0,0,0.4);
        }

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

        .card-body { padding: 20px; }

        label {
            color: #ccc;
            font-weight: 500;
            display: block;
            margin-bottom: 5px;
        }

        input.form-control, select.form-control {
            background-color: #2a2a2a;
            color: #eee;
            border: 1px solid #444;
            border-radius: 6px;
            padding: 10px;
            width: 100%;
            transition: all 0.3s;
        }

        input.form-control:focus, select.form-control:focus {
            border-color: #00bfff;
            background-color: #333;
            box-shadow: 0 0 5px rgba(0,191,255,0.5);
        }

        .btn-primary {
            background-color: #00bfff;
            border: none;
            border-radius: 6px;
            padding: 10px 25px;
            font-weight: bold;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0095cc;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 6px;
            padding: 12px 20px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .alert-success { background-color: #27ae60; color: white; }
        .alert-danger { background-color: #c0392b; color: white; }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .btn-primary { width: 100%; font-size: 1rem; padding: 12px; }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <h2><i class="fa-solid fa-file-invoice-dollar"></i> Configurações Fiscais</h2>
    <p>Preencha os dados da sua empresa para emissão de notas fiscais.</p>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Configurações salvas com sucesso!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Erro ao salvar: <?= htmlspecialchars($_GET['error'] ?? '') ?></div>
    <?php endif; ?>

    <form action="../actions/salvar_configuracao_fiscal.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($usuarioId ?? '') ?>">

        <div class="card">
            <div class="card-header">Dados da Empresa</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="razao_social">Razão Social</label>
                        <input type="text" class="form-control" id="razao_social" name="razao_social" value="<?= htmlspecialchars($config['razao_social'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="fantasia">Nome Fantasia</label>
                        <input type="text" class="form-control" id="fantasia" name="fantasia" value="<?= htmlspecialchars($config['fantasia'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cnpj">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?= htmlspecialchars($config['cnpj'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ie">Inscrição Estadual</label>
                        <input type="text" class="form-control" id="ie" name="ie" value="<?= htmlspecialchars($config['ie'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Endereço</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="logradouro">Logradouro</label>
                        <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= htmlspecialchars($config['logradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="numero">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($config['numero'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bairro">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($config['bairro'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cep">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($config['cep'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="municipio">Município</label>
                        <input type="text" class="form-control" id="municipio" name="municipio" value="<?= htmlspecialchars($config['municipio'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="uf">UF</label>
                        <input type="text" class="form-control" id="uf" name="uf" maxlength="2" value="<?= htmlspecialchars($config['uf'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cod_municipio">Código IBGE</label>
                        <input type="text" class="form-control" id="cod_municipio" name="cod_municipio" value="<?= htmlspecialchars($config['cod_municipio'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

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
                        <label for="csc_id">ID do CSC (Token)</label>
                        <input type="text" class="form-control" id="csc_id" name="csc_id" value="<?= htmlspecialchars($config['csc_id'] ?? '') ?>" placeholder="Ex: 000001">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="csc">CSC (Código de Segurança)</label>
                        <input type="text" class="form-control" id="csc" name="csc" value="<?= htmlspecialchars($config['csc'] ?? '') ?>" placeholder="Token fornecido pela SEFAZ">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Certificado Digital A1</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="certificado_a1">Arquivo (.pfx)</label>
                    <input class="form-control" type="file" id="certificado_a1" name="certificado_a1" accept=".pfx">
                    <?php if (!empty($config['certificado_a1_path'])): ?>
                        <small>Um certificado já foi enviado. Envie outro para substituir.</small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="certificado_senha">Senha do Certificado</label>
                    <input type="password" class="form-control" id="certificado_senha" name="certificado_senha">
                    <small>Informe apenas se desejar alterar a senha atual.</small>
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
