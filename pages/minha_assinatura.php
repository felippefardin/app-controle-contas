<?php
// pages/minha_assinatura.php
require_once '../includes/session_init.php';
require_once '../database.php';

/*
  Reestruturação completa do arquivo:
  - Coleta de dados no topo (defensiva)
  - Tratamento de variáveis inexistentes (evita warnings)
  - Cálculos de datas e trial
  - Saída HTML preservando estilo original
*/

/* -------------------- 1) Verificações iniciais -------------------- */
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

/* Usuário e tenant vindo da sessão */
$tenant_id = $_SESSION['tenant_id'] ?? null;
$usuario_nome_sess = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Cliente';

/* -------------------- 2) Conexão com BD master e inicializações -------------------- */
$conn = getMasterConnection();
if ($conn === null) {
    die("Erro ao conectar ao banco de dados principal.");
}

/* Valores padrão */
$dados_assinatura = [];
$plano_db = 'basico';
$nome_plano_atual = 'Plano Básico';
$limite_base = 3;
$extras_comprados = 0;
$limite_total = 3;
$status_assinatura = 'padrao';
$is_trial = false;
$dias_restantes_teste = 0;
$dias_ate_renovacao = 0;
$tipo_cancelamento = null;

/* Mapa de planos (fonte única) */
$mapa_planos = [
    'basico'    => ['nome' => 'Plano Básico', 'base' => 3,  'desc' => 'Ideal para pequenos negócios',       'preco' => 19.00],
    'plus'      => ['nome' => 'Plano Plus',   'base' => 6,  'desc' => 'Para empresas em crescimento',      'preco' => 39.00],
    'essencial' => ['nome' => 'Plano Essencial','base' => 16,'desc' => 'Gestão completa para sua equipe',   'preco' => 59.00]
];

/* -------------------- 3) Buscar dados do tenant (assinatura) -------------------- */
if ($tenant_id) {
    $stmt = $conn->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $dados_assinatura = $result->fetch_assoc();

            // Exibição do nome da empresa / cliente
            if (!empty($dados_assinatura['nome_empresa'])) {
                $usuario_nome_sess = $dados_assinatura['nome_empresa'];
            } elseif (!empty($dados_assinatura['nome'])) {
                $usuario_nome_sess = $dados_assinatura['nome'];
            }

            // Plano salvo no banco (defensivo)
            $plano_db = $dados_assinatura['plano_atual'] ?? 'basico';
            if (!array_key_exists($plano_db, $mapa_planos)) {
                $plano_db = 'basico';
            }

            // extras
            $extras_comprados = (int)($dados_assinatura['usuarios_extras'] ?? 0);

            // info plano
            $info_plano = $mapa_planos[$plano_db];
            $limite_base = (int)($info_plano['base'] ?? 3);
            $nome_plano_atual = $info_plano['nome'] ?? 'Plano Básico';
            $limite_total = $limite_base + $extras_comprados;

            // status
            $status_assinatura = $dados_assinatura['status_assinatura'] ?? 'padrao';
            $tipo_cancelamento = $dados_assinatura['tipo_cancelamento'] ?? null;

            // Cálculo data renovação (defensivo)
            $data_renovacao_str = $dados_assinatura['data_renovacao'] ?? null;
            if (!empty($data_renovacao_str)) {
                try {
                    $dt_hoje = new DateTime('now');
                    $dt_hoje->setTime(0,0,0);

                    $dt_renovacao = new DateTime($data_renovacao_str);
                    $dt_renovacao->setTime(0,0,0);

                    $dias_ate_renovacao = ($dt_renovacao > $dt_hoje) ? (int)$dt_hoje->diff($dt_renovacao)->format('%a') : 0;
                } catch (Exception $e) {
                    $dias_ate_renovacao = 0;
                }
            }

            // Se trial
            if (($dados_assinatura['status_assinatura'] ?? '') === 'trial') {
                $is_trial = true;
                $dias_teste = ($plano_db === 'essencial') ? 30 : 15;

                $data_ref = $dados_assinatura['data_inicio_teste'] ?? $dados_assinatura['data_criacao'] ?? null;
                if ($data_ref) {
                    try {
                        $data_inicio = new DateTime($data_ref);
                        $data_inicio->setTime(0,0,0);
                        $data_fim_teste = clone $data_inicio;
                        $data_fim_teste->modify("+{$dias_teste} days");
                        $hoje = new DateTime('now');
                        $hoje->setTime(0,0,0);

                        $dias_restantes_teste = ($hoje < $data_fim_teste) ? (int)$hoje->diff($data_fim_teste)->format('%a') : 0;
                    } catch (Exception $e) {
                        $dias_restantes_teste = 0;
                    }
                }
            }
        }

        $stmt->close();
    }
}

/* Fechar conexão master (vamos reabrir se preciso) */
$conn->close();

/* -------------------- 4) Captura mensagens de sessão para SweetAlert -------------------- */
$swal_alert = [];
if (!empty($_SESSION['sucesso_pagamento'])) {
    $swal_alert = ['type' => 'success', 'title' => 'Pagamento Confirmado!', 'text' => $_SESSION['sucesso_pagamento']];
    unset($_SESSION['sucesso_pagamento']);
} elseif (!empty($_SESSION['sucesso'])) {
    $swal_alert = ['type' => 'success', 'title' => 'Sucesso!', 'text' => $_SESSION['sucesso']];
    unset($_SESSION['sucesso']);
} elseif (!empty($_SESSION['erro'])) {
    $swal_alert = ['type' => 'error', 'title' => 'Atenção!', 'text' => $_SESSION['erro']];
    unset($_SESSION['erro']);
}

/* -------------------- 5) Incluir header e começar HTML -------------------- */
include('../includes/header.php');

// Dados disponíveis para JS
$cupom_registro = $dados_assinatura['cupom_registro'] ?? '';
$msg_cupom_visto = (isset($dados_assinatura['msg_cupom_visto']) ? (int)$dados_assinatura['msg_cupom_visto'] : 0);
$msg_indicacao_visto = (isset($dados_assinatura['msg_indicacao_visto']) ? (int)$dados_assinatura['msg_indicacao_visto'] : 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Minha Assinatura</title>

    <!-- Bootstrap e FontAwesome (preservados) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ----------- ESTILO NEON DARK (mantido e levemente limpo) ------------ */
        body { background-color: #121212; color: #eee; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-assinatura { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-header { border-bottom: 1px solid #00bfff; padding-bottom: 15px; margin-bottom: 30px;
                        display: flex; align-items: center; justify-content: space-between; gap: 15px; }
        .page-header h2 { margin: 0; color: #00bfff; font-size: 1.8rem; }
        .btn-top-back { color: #aaa; text-decoration: none; font-size: 1rem; display: flex; align-items: center;
                        gap: 8px; padding: 8px 15px; border: 1px solid #333; border-radius: 6px; background-color: #1e1e1e; transition: all 0.3s; }
        .btn-top-back:hover { color: #fff; border-color: #555; background-color: #2c2c2c; }

        .card-custom { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px;
                       padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: border-color 0.3s; }
        .card-custom:hover { border-color: #444; box-shadow: 0 4px 20px rgba(0, 191, 255, 0.1); }
        .card-title { color: #00bfff; font-size: 1.3rem; border-bottom: 1px solid #333; padding-bottom: 15px;
                      margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        @media(max-width: 768px) { .grid-layout { grid-template-columns: 1fr; } }

        .info-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;
                    padding-bottom: 12px; border-bottom: 1px solid #2c2c2c; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #aaa; font-size: 0.95rem; }
        .value { font-weight: bold; color: #fff; font-size: 1rem; text-align: right; }

        .status-ativo { color: #2ecc71; }
        .status-inativo { color: #e74c3c; }
        .extra-highlight { color: #00bfff; font-weight: 800; }

        .neon-number { font-size: 3.5rem; font-weight: bold; color: #fff; line-height: 1;
                       text-shadow: 0 0 15px rgba(0, 191, 255, 0.6); }

        .btn-custom { padding: 12px; border-radius: 6px; font-weight: bold; text-decoration: none; text-align: center;
                      display: flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer;
                      color: #fff; transition: all 0.2s; font-size: 1rem; width: 100%; }
        .btn-custom:hover { transform: translateY(-2px); color: #fff; }

        .btn-assinar { background: linear-gradient(135deg, #28a745, #218838); box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); }
        .btn-cancel { background: transparent; border: 1px solid #ff003c; color: #ff003c; margin-top: 10px; font-size: 0.95rem; width: auto; display: inline-flex; padding: 8px 20px; }
        .btn-cancel:hover { background: rgba(255, 0, 60, 0.1); color: #ff4d79; border-color: #ff4d79; }

        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 10px; }
        .plan-card { padding: 20px; border-radius: 10px; display: flex; flex-direction: column; justify-content: space-between; text-align: center; transition: transform 0.2s; }
        .plan-card:hover { transform: translateY(-3px); }
        .plan-card h3 { margin-top: 0; margin-bottom: 10px; font-size: 1.4rem; }
        .plan-desc { color: #aaa; font-size: 0.85rem; margin-bottom: 15px; }
        .plan-limit { color: #fff; font-size: 1.1rem; margin-bottom: 20px; }

        .trial-alert { background: rgba(255, 193, 7, 0.15); border: 1px solid #ffc107; color: #ffc107;
                        padding: 15px; border-radius: 8px; font-weight: bold; text-align: center; margin-bottom: 30px;
                        display: flex; align-items: center; justify-content: center; gap: 15px; }

        .btn-group { display: flex; gap: 15px; margin-top: 15px; }
        .btn-history { background-color: #17a2b8; flex: 1; }
        .btn-receipt { background-color: #6c757d; flex: 1; }

        .action-box { background: #252525; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px dashed #444; }
        .action-row { display: flex; gap: 10px; }
        .action-row form { flex: 1; }
        .btn-add-user { background: linear-gradient(135deg, #00bfff, #008cba); }
        .btn-remove-user { background: transparent; border: 1px solid #dc3545; color: #ff6b6b; }
        .btn-remove-user:hover { background: rgba(220, 53, 69, 0.1); }

        .footer-links { text-align: center; margin-top: 30px; display: flex; justify-content: center; gap: 25px; }
        .footer-link { color: #666; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: color 0.3s; }
        .footer-link:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container-assinatura">
    <div class="page-header">
        <h2><i class="fa-solid fa-star"></i> Gestão da Assinatura</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="perfil.php" class="btn-top-back"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>
    </div>

    <!-- Trial alert -->
    <?php if ($is_trial && $dias_restantes_teste > 0): ?>
        <div class="trial-alert">
            <i class="fa-solid fa-clock fa-lg"></i>
            <div>PERÍODO DE TESTE: Restam <strong><?= (int)$dias_restantes_teste ?> dias</strong> gratuitos.</div>
            <button type="button" class="btn-custom" style="background: #ffc107; color: #000; padding: 6px 15px; font-size: 0.9rem; width: auto;"
                    onclick="confirmarTrocaPlano('<?= htmlspecialchars($plano_db) ?>', '<?= htmlspecialchars($nome_plano_atual) ?>')">
                Assinar Agora
            </button>
        </div>
    <?php endif; ?>

    <div class="grid-layout">
        <!-- Detalhes do Plano -->
        <div class="card-custom">
            <div class="card-title"><i class="fa-solid fa-file-contract"></i> Detalhes do Plano</div>

            <div class="info-row">
                <span class="label">Plano Atual:</span>
                <span class="value" style="color: #00bfff; font-size: 1.1rem;"><?= htmlspecialchars($nome_plano_atual) ?></span>
            </div>

            <div class="info-row">
                <span class="label">Limite Base:</span>
                <span class="value"><?= (int)$limite_base ?> Usuários</span>
            </div>

            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value">
                    <?php
                    if ($tipo_cancelamento) {
                        echo '<div style="margin-bottom:5px; color: #ffc107; font-weight:bold;"><i class="fa-solid fa-calendar-xmark"></i> Cancelamento Agendado</div>';
                        echo '<small style="color:#ccc; font-size: 0.85rem;">Encerra em: <strong style="color:#fff">' . (int)$dias_ate_renovacao . ' dias</strong></small>';
                    } else {
                        if ($status_assinatura === 'ativo') {
                            echo '<span class="status-ativo"><i class="fa-solid fa-check-circle"></i> Ativo</span>';
                        } elseif ($status_assinatura === 'trial') {
                            echo ($dias_restantes_teste > 0)
                                ? '<span style="color: #ffc107"><i class="fa-solid fa-flask"></i> Em Teste</span>'
                                : '<span class="status-inativo"><i class="fa-solid fa-times-circle"></i> Teste Expirado</span>';
                        } else {
                            echo '<span class="status-inativo"><i class="fa-solid fa-times-circle"></i> Inativo</span>';
                        }
                    }
                    ?>
                </span>
            </div>

            <?php if (!$is_trial && !empty($dados_assinatura['data_renovacao'])): ?>
                <div class="info-row" style="margin-top: 15px;">
                    <span class="label"><?= $tipo_cancelamento ? 'Encerramento em:' : 'Próxima Cobrança:' ?></span>
                    <span class="value">
                        <?= htmlspecialchars(date('d/m/Y', strtotime($dados_assinatura['data_renovacao']))) ?>
                        <br>
                        <small style="font-size:0.8rem; color:#888; font-weight:normal;">(Faltam <?= (int)$dias_ate_renovacao ?> dias)</small>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (empty($tipo_cancelamento) && $status_assinatura !== 'inativo'): ?>
                <div class="mt-4 text-center">
                    <button type="button" class="btn-custom btn-cancel" onclick="abrirModalCancelamento()">
                        <i class="fa-solid fa-ban"></i> Cancelar Assinatura
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($status_assinatura === 'inativo' || ($is_trial && $dias_restantes_teste <= 0)): ?>
                <div class="mt-4">
                    <form id="formPlano_<?= htmlspecialchars($plano_db) ?>_reativar" action="checkout_plano.php" method="POST">
                        <input type="hidden" name="plano" value="<?= htmlspecialchars($plano_db) ?>">
                        <input type="hidden" name="cupom" value="<?= htmlspecialchars($cupom_registro) ?>">
                        <input type="hidden" name="codigo_indicacao" value="">
                        <button type="button" class="btn-custom btn-assinar" onclick="confirmarTrocaPlano('<?= htmlspecialchars($plano_db) ?>', '<?= htmlspecialchars($nome_plano_atual) ?>')">
                            <i class="fa-solid fa-rotate-right"></i> Reativar Meu Plano
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Capacidade -->
        <div class="card-custom" style="border-color: rgba(0, 191, 255, 0.3);">
            <div class="card-title"><i class="fa-solid fa-users-gear"></i> Capacidade</div>

            <div style="text-align: center; margin-bottom: 20px;">
                <div class="neon-number"><?= (int)$limite_total ?></div>
                <div style="color: #aaa; font-size: 0.9rem;">Usuários Totais Permitidos</div>
            </div>

            <div class="info-row">
                <span class="label">Incluso no Plano:</span>
                <span class="value"><?= (int)$limite_base ?></span>
            </div>
            <div class="info-row">
                <span class="label">Extras Contratados:</span>
                <span class="value extra-highlight">+<?= (int)$extras_comprados ?></span>
            </div>

            <div class="action-box">
                <div style="font-size: 0.85rem; color: #ccc; margin-bottom: 10px; text-align: center;">
                    Gerencie seus slots extras (R$ 4,00/mês cada).
                </div>
                <div class="action-row">
                    <form id="formAddExtra" action="../actions/comprar_extra_action.php" method="POST">
                        <input type="hidden" name="qtd_extra" value="1">
                        <button type="button" class="btn-custom btn-add-user" onclick="confirmarAdicao()">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </form>

                    <?php if ($extras_comprados > 0): ?>
                    <form id="formRemoveExtra" action="../actions/remover_extra_action.php" method="POST">
                        <input type="hidden" name="qtd_remover" value="1">
                        <button type="button" class="btn-custom btn-remove-user" onclick="confirmarRemocao()">
                            <i class="fa-solid fa-minus"></i> Remover
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Planos Disponíveis -->
    <div class="card-custom" id="planos-disponiveis">
        <div class="card-title"><i class="fa-solid fa-layer-group"></i> Planos Disponíveis</div>
        <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 20px;">Escolha o plano ideal para o seu negócio. A alteração é imediata.</p>

        <div class="plans-grid">
            <?php foreach ($mapa_planos as $slug => $plano):
                $e_plano_atual = ($slug === $plano_db);
                $ativo = ($e_plano_atual && $status_assinatura === 'ativo');

                $border = $e_plano_atual ? '2px solid #00bfff' : '1px solid #333';
                $bg = $e_plano_atual ? 'rgba(0, 191, 255, 0.08)' : '#252525';
                $color_title = $e_plano_atual ? '#00bfff' : '#fff';
            ?>
                <div class="plan-card" style="background: <?= $bg ?>; border: <?= $border ?>;">
                    <div>
                        <h3 style="color: <?= $color_title ?>"><?= htmlspecialchars($plano['nome']) ?></h3>
                        <p class="plan-desc"><?= htmlspecialchars($plano['desc']) ?></p>
                        <div class="plan-limit"><i class="fa-solid fa-user-group"></i> <?= (int)$plano['base'] ?> Usuários</div>
                        <div style="font-size: 1.2rem; font-weight: bold; color: #fff; margin-bottom: 15px;">
                            R$ <?= number_format($plano['preco'], 2, ',', '.') ?> <small style="font-size: 0.8rem; font-weight: normal;">/mês</small>
                        </div>
                    </div>

                    <?php if ($ativo): ?>
                        <button disabled class="btn-custom" style="background: transparent; border: 1px solid #00bfff; color: #00bfff; cursor: default; opacity: 0.8;">
                            <i class="fa-solid fa-check"></i> Plano Atual
                        </button>
                    <?php else: ?>
                        <form id="formPlano_<?= htmlspecialchars($slug) ?>" action="checkout_plano.php" method="POST">
                            <input type="hidden" name="plano" value="<?= htmlspecialchars($slug) ?>">
                            <input type="hidden" name="cupom" value="<?= htmlspecialchars($cupom_registro) ?>">
                            <input type="hidden" name="codigo_indicacao" value="">
                            <button type="button" class="btn-custom" style="background: #00bfff;" onclick="confirmarTrocaPlano('<?= htmlspecialchars($slug) ?>', '<?= htmlspecialchars($plano['nome']) ?>')">
                                <?= $e_plano_atual ? 'Reativar Plano' : 'Mudar Plano' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Financeiro -->
    <div class="card-custom">
        <div class="card-title"><i class="fa-solid fa-wallet"></i> Financeiro</div>
        <div class="btn-group">
            <a href="historico_pagamento.php" class="btn-custom btn-history"><i class="fa-solid fa-list-ul"></i> Histórico</a>
            <a href="recibo_pagamentos.php" class="btn-custom btn-receipt"><i class="fa-solid fa-file-invoice-dollar"></i> Comprovantes</a>
        </div>
    </div>

    <div class="footer-links">
        <a href="perfil.php" class="btn-top-back">
            <i class="fa-solid fa-user"></i> Voltar para Perfil
        </a>
    </div>
</div>

<!-- Modal Cancelamento -->
<div class="modal fade" id="modalCancelarAssinatura" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #2c2c2c; color: #eee; border: 1px solid #444;">
            <div class="modal-header" style="border-bottom: 1px solid #444;">
                <h5 class="modal-title" style="color: #ff003c;">Cancelar Assinatura</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sentimos muito que você queira partir. Escolha como deseja proceder:</p>
                <form id="formCancelarAssinatura" action="../actions/solicitar_cancelamento.php" method="POST">
                    <div class="form-check mb-3 p-3" style="background: rgba(255,255,255,0.05); border-radius: 5px;">
                        <input class="form-check-input" type="radio" name="opcao_cancelamento" id="opcao1" value="desativar" required>
                        <label class="form-check-label" for="opcao1">
                            <strong>Opção 1 - Desativar conta:</strong><br>
                            <small style="color: #aaa;">Acesso mantido até o fim do ciclo. Depois a conta é suspensa.</small>
                        </label>
                    </div>
                    <div class="form-check p-3" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 5px;">
                        <input class="form-check-input" type="radio" name="opcao_cancelamento" id="opcao2" value="excluir" required>
                        <label class="form-check-label text-danger" for="opcao2">
                            <strong>Opção 2 - Excluir tudo:</strong><br>
                            <small style="color: #ffb3b3;">Dados apagados permanentemente após o fim do ciclo.</small>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #444;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" form="formCancelarAssinatura" class="btn btn-danger">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: inserir cupom / indicação (quando não houver cupom_registro salvo) -->
<div class="modal fade" id="modalCupomIndicacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color:#1e1e1e; color:#eee; border:1px solid #333;">
            <div class="modal-header">
                <h5 class="modal-title text-info"><i class="fa-solid fa-ticket"></i> Aplicar Cupom / Indicação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="color:#aaa;">Você não tem cupom salvo. Se possuir, digite abaixo para aplicar no checkout. Opcional: código de indicação.</p>

                <div class="mb-3">
                    <label class="form-label">Código do Cupom (opcional)</label>
                    <input type="text" id="modal_cupom" class="form-control bg-dark text-white" placeholder="Ex: PROMO10" style="text-transform:uppercase;">
                </div>

                <div class="mb-3">
                    <label class="form-label">Código de Indicação (opcional)</label>
                    <input type="text" id="modal_indicacao" class="form-control bg-dark text-white" placeholder="Ex: A1B2C3" style="text-transform:uppercase;">
                </div>

                <input type="hidden" id="modal_target_form_id" value="">
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="aplicarCupomEAvancar()">Ir para checkout</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($swal_alert)): ?>
        Swal.fire({
            icon: '<?= $swal_alert['type'] ?>',
            title: '<?= addslashes($swal_alert['title']) ?>',
            text: '<?= addslashes($swal_alert['text']) ?>',
            background: '#1e1e1e',
            color: '#eee',
            confirmButtonColor: '#00bfff'
        });
    <?php endif; ?>
});

/* Modal cancelar */
function abrirModalCancelamento() {
    var myModal = new bootstrap.Modal(document.getElementById('modalCancelarAssinatura'));
    myModal.show();
}

/* Confirm dialogs */
function confirmarAdicao() {
    Swal.fire({
        title: 'Adicionar Usuário?',
        text: "Adicional de R$ 4,00/mês na fatura.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#00bfff',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, adicionar',
        background: '#1e1e1e', color: '#eee'
    }).then((result) => {
        if (result.isConfirmed) document.getElementById('formAddExtra').submit();
    });
}

function confirmarRemocao() {
    Swal.fire({
        title: 'Remover Vaga?',
        text: "Verifique se seus usuários ativos cabem no novo limite.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, remover',
        background: '#1e1e1e', color: '#eee'
    }).then((result) => {
        if (result.isConfirmed && document.getElementById('formRemoveExtra')) document.getElementById('formRemoveExtra').submit();
    });
}

/* Troca de plano / fluxo de checkout
   - Cada plano tem um form id="formPlano_{slug}"
   - Se existir cupom_registro salvo (variável PHP), ele é enviado automaticamente
   - Se não existir, abre modal para digitar cupom/indicacao e então envia
*/
const EXISTE_CUPOM_SALVO = <?= json_encode(!empty($cupom_registro)) ?>;
const CUPOM_SALVO_VALOR = <?= json_encode($cupom_registro) ?>;

function confirmarTrocaPlano(slug, nomePlano) {
    // form id
    const formId = 'formPlano_' + slug;
    const form = document.getElementById(formId);
    if (!form) {
        // fallback: redirect via generic form for plano atual
        const fallback = document.getElementById('formPlano_' + slug + '_reativar');
        if (fallback) fallback.submit();
        return;
    }

    // se já existe cupom salvo, submete diretamente (mantendo o cupom no hidden)
    if (EXISTE_CUPOM_SALVO && CUPOM_SALVO_VALOR) {
        // garante que o hidden cupom do form receba o valor salvo
        const cupomInput = form.querySelector('input[name="cupom"]');
        if (cupomInput) cupomInput.value = CUPOM_SALVO_VALOR;
        form.submit();
        return;
    }

    // senão: abrir modal para digitar cupom/indicacao
    const modalEl = new bootstrap.Modal(document.getElementById('modalCupomIndicacao'));
    document.getElementById('modal_target_form_id').value = formId;
    modalEl.show();
}

/* Pegar dados do modal e submeter o form alvo */
function aplicarCupomEAvancar() {
    const cupom = document.getElementById('modal_cupom').value.trim();
    const indic = document.getElementById('modal_indicacao').value.trim();
    const targetId = document.getElementById('modal_target_form_id').value;
    const form = document.getElementById(targetId);
    if (!form) {
        alert('Formulário alvo não encontrado.');
        return;
    }

    const cupomInput = form.querySelector('input[name="cupom"]');
    const indicInput = form.querySelector('input[name="codigo_indicacao"]');

    if (cupomInput) cupomInput.value = cupom.toUpperCase();
    if (indicInput) indicInput.value = indic.toUpperCase();

    // fechar modal antes do submit
    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalCupomIndicacao'));
    if (modalInstance) modalInstance.hide();

    // submeter
    form.submit();
}
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>
