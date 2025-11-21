<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: login.php');
    exit;
}

// Obtém a conexão com o banco do tenant
$conn = getTenantConnection();
if ($conn === null) {
    die("Erro: Falha ao conectar ao banco de dados do cliente. Verifique sua sessão.");
}

// Inicializa arrays para evitar erros
$dadosEmpresa = [];

// 1. Busca dados cadastrais
$stmt = $conn->query("SELECT * FROM empresa_config LIMIT 1");
if ($stmt && $stmt->num_rows > 0) {
    $dadosEmpresa = $stmt->fetch_assoc();
}

// 2. Busca dados fiscais (config tenant)
$stmtKv = $conn->query("SELECT chave, valor FROM configuracoes_tenant");
if ($stmtKv) {
    while ($row = $stmtKv->fetch_assoc()) {
        $dadosEmpresa[$row['chave']] = $row['valor'];
    }
}

include('../includes/header.php');
?>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
/* === ESTILO GERAL === */
:root {
    --bg-body: #121212;
    --bg-card: #1e1e1e;
    --bg-input: #2c2c2c;
    --border-color: #2a2a2a;
    --text-primary: #e0e0e0;
    --text-secondary: #b0b0b0;
    --primary-color: #00bfff;
    --primary-hover: #009ed1;
}

body {
    background-color: var(--bg-body);
    color: var(--text-primary);
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

/* MAIN CENTRALIZADO */
main {
    width: 100%;
    max-width: 1450px;
    margin: auto;
    padding: 25px 35px;
}

/* === TÍTULO === */
.page-title {
    font-size: 1.9rem;
    font-weight: 700;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 25px 0 30px 0;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 12px;
}

.page-title i {
    font-size: 1.8rem;
    color: var(--primary-color);
}

/* === CARDS === */
.card {
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 5px 12px rgba(0,0,0,0.3);
}

.card-header {
    background: rgba(255,255,255,0.03);
    color: var(--primary-color);
    padding: 18px 22px;
    font-size: 1.15rem;
    font-weight: 600;
    border-bottom: 1px solid var(--border-color);
}

.card-body {
    padding: 28px;
}

/* === INPUTS === */
label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 6px;
    font-weight: 500;
}

.form-control {
    background-color: var(--bg-input);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 6px;
    height: 46px;
    padding-left: 12px;
}

.form-control:focus {
    background-color: #303030;
    border-color: var(--primary-color);
    color: #fff;
    box-shadow: 0 0 0 0.15rem rgba(0,191,255,0.25);
}

.form-control::placeholder {
    color: #777;
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 8 8'%3E%3Cpath fill='%23b0b0b0' d='M0 2l4 4 4-4z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 12px;
}

/* BTN */
.btn-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 25px;
    margin-bottom: 80px;
}

.btn-primary {
    background-color: var(--primary-color);
    color: #121212;
    font-weight: 700;
    border: none;
    padding: 12px 32px;
    border-radius: 6px;
    font-size: 0.95rem;
    text-transform: uppercase;
    transition: 0.25s ease;
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 5px 18px rgba(0,191,255,0.35);
}

/* === ALERTAS === */
.alert {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-left: 4px solid var(--primary-color);
    color: var(--text-primary);
    padding: 14px 18px;
    border-radius: 8px;
}

/* === RESPONSIVIDADE === */
@media (max-width: 850px) {
    main {
        padding: 20px;
    }
    .page-title { font-size: 1.6rem; }
    .btn-container { justify-content: center; }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.4rem;
        flex-direction: column;
        text-align: center;
    }
    .btn-primary { width: 100%; }
}
</style>

<main>

    <!-- FEEDBACK -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success mt-3">
            <i class="fa-solid fa-check-circle text-success mr-2"></i>
            Configurações salvas com sucesso.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger mt-3">
            <i class="fa-solid fa-circle-exclamation text-danger mr-2"></i>
            <?= htmlspecialchars(urldecode($_GET['error'])) ?>
        </div>
    <?php endif; ?>

    <div class="page-title">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>Configurações Fiscais</span>
    </div>

    <form action="../actions/salvar_configuracao_fiscal.php" method="POST">
        <div class="row">

            <!-- Dados da Empresa -->
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="card-header"><i class="fa-regular fa-building mr-2"></i> Dados da Empresa</div>
                    <div class="card-body">

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Razão Social</label>
                                <input type="text" class="form-control" name="razao_social"
                                    value="<?= htmlspecialchars($dadosEmpresa['razao_social'] ?? '') ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Nome Fantasia</label>
                                <input type="text" class="form-control" name="fantasia"
                                    value="<?= htmlspecialchars($dadosEmpresa['fantasia'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>CNPJ</label>
                                <input type="text" class="form-control" name="cnpj"
                                    value="<?= htmlspecialchars($dadosEmpresa['cnpj'] ?? '') ?>"
                                    required placeholder="Apenas números">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Inscrição Estadual</label>
                                <input type="text" class="form-control" name="ie"
                                    value="<?= htmlspecialchars($dadosEmpresa['ie'] ?? '') ?>">
                            </div>
                            <div class="form-group col-md-4">
                                <label>CEP</label>
                                <input type="text" class="form-control" name="cep" id="cep"
                                    value="<?= htmlspecialchars($dadosEmpresa['cep'] ?? '') ?>"
                                    onblur="buscarCep(this.value)">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-10">
                                <label>Logradouro</label>
                                <input type="text" class="form-control" name="logradouro" id="logradouro"
                                    value="<?= htmlspecialchars($dadosEmpresa['logradouro'] ?? '') ?>">
                            </div>
                            <div class="form-group col-md-2">
                                <label>Número</label>
                                <input type="text" class="form-control" name="numero"
                                    value="<?= htmlspecialchars($dadosEmpresa['numero'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label>Bairro</label>
                                <input type="text" class="form-control" name="bairro" id="bairro"
                                    value="<?= htmlspecialchars($dadosEmpresa['bairro'] ?? '') ?>">
                            </div>
                            <div class="form-group col-md-5">
                                <label>Município</label>
                                <input type="text" class="form-control" name="municipio" id="municipio"
                                    value="<?= htmlspecialchars($dadosEmpresa['municipio'] ?? '') ?>">
                            </div>
                            <div class="form-group col-md-2">
                                <label>UF</label>
                                <input type="text" class="form-control" name="uf" id="uf"
                                    value="<?= htmlspecialchars($dadosEmpresa['uf'] ?? '') ?>" maxlength="2">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Cód. IBGE Mun.</label>
                                <input type="text" class="form-control" name="cod_municipio" id="ibge"
                                    value="<?= htmlspecialchars($dadosEmpresa['cod_municipio'] ?? '') ?>">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Parâmetros NFC-e -->
            <div class="col-lg-4 col-md-12">
                <div class="card h-100">
                    <div class="card-header"><i class="fa-solid fa-key mr-2"></i> Parâmetros NFC-e</div>
                    <div class="card-body d-flex flex-column">

                        <div class="form-group">
                            <label>Ambiente</label>
                            <select class="form-control" name="ambiente">
                                <option value="2" <?= ($dadosEmpresa['ambiente'] ?? '') == 2 ? 'selected' : '' ?>>
                                    Homologação (Teste)
                                </option>
                                <option value="1" <?= ($dadosEmpresa['ambiente'] ?? '') == 1 ? 'selected' : '' ?>>
                                    Produção
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Regime Tributário</label>
                            <select class="form-control" name="regime_tributario">
                                <option value="1" <?= ($dadosEmpresa['regime_tributario'] ?? '') == 1 ? 'selected' : '' ?>>
                                    Simples Nacional
                                </option>
                                <option value="3" <?= ($dadosEmpresa['regime_tributario'] ?? '') == 3 ? 'selected' : '' ?>>
                                    Regime Normal
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>ID CSC (Token)</label>
                            <input type="text" class="form-control" name="csc_id"
                                value="<?= htmlspecialchars($dadosEmpresa['csc_id'] ?? '') ?>" placeholder="Ex: 000001">
                            <small class="text-muted">Número sequencial do token.</small>
                        </div>

                        <div class="form-group">
                            <label>CSC (Código Alpha)</label>
                            <input type="text" class="form-control" name="csc"
                                value="<?= htmlspecialchars($dadosEmpresa['csc'] ?? '') ?>" placeholder="Código alfanumérico">
                        </div>

                        <div class="mt-auto pt-3">
                            <div class="alert alert-info p-2" style="font-size: 0.85rem;">
                                <i class="fa-solid fa-circle-info"></i> O certificado A1 deve ser enviado ao suporte.
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="btn-container">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check mr-2"></i> Salvar Configurações
            </button>
        </div>

    </form>

</main>

<script>
function buscarCep(cep) {
    cep = cep.replace(/\D/g, '');
    if (cep.length === 8) {
        document.getElementById('cep').style.borderColor = '#00bfff';

        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(r => r.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('logradouro').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('municipio').value = data.localidade;
                    document.getElementById('uf').value = data.uf;
                    document.getElementById('ibge').value = data.ibge;

                    document.getElementsByName('numero')[0].focus();
                }
                document.getElementById('cep').style.borderColor = '';
            })
            .catch(() => {
                document.getElementById('cep').style.borderColor = 'red';
            });
    }
}
</script>

<?php include('../includes/footer.php'); ?>
