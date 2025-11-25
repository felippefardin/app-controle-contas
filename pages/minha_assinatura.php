<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verifica permissão
if (!isset($_SESSION['nivel_acesso']) || ($_SESSION['nivel_acesso'] !== 'admin' && $_SESSION['nivel_acesso'] !== 'master' && $_SESSION['nivel_acesso'] !== 'proprietario')) {
    header("Location: home.php");
    exit;
}

$conn = getMasterConnection();
if ($conn === null) {
    die("Erro ao conectar ao banco de dados principal.");
}

$tenant_id = $_SESSION['tenant_id'] ?? null;
$dados_assinatura = [];
$dias_restantes_teste = 0;
$dias_ate_renovacao = 0;
$is_trial = false;
$nome_exibicao = $_SESSION['nome'] ?? 'Cliente'; 

$limite_base = 3;
$extras_comprados = 0;
$nome_plano_atual = 'Básico';
$limite_total = 3;

// Definição dos planos disponíveis
$mapa_planos = [
    'basico'    => ['nome' => 'Plano Básico', 'base' => 3, 'desc' => 'Ideal para pequenos negócios'],
    'plus'      => ['nome' => 'Plano Plus', 'base' => 6, 'desc' => 'Para empresas em crescimento'],
    'essencial' => ['nome' => 'Plano Essencial', 'base' => 16, 'desc' => 'Gestão completa para sua equipe']
];

if ($tenant_id) {
    // Buscamos t.* para garantir que pegamos data_renovacao e tipo_cancelamento
    $stmt = $conn->prepare("SELECT * FROM tenants WHERE tenant_id = ?");
    $stmt->bind_param("s", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dados_assinatura = $result->fetch_assoc();
        
        if (!empty($dados_assinatura['nome_empresa'])) {
            $nome_exibicao = $dados_assinatura['nome_empresa'];
        } elseif (!empty($dados_assinatura['nome'])) {
            $nome_exibicao = $dados_assinatura['nome'];
        }

        $plano_db = $dados_assinatura['plano_atual'] ?? 'basico';
        $extras_comprados = (int)($dados_assinatura['usuarios_extras'] ?? 0);

        $info_plano = $mapa_planos[$plano_db] ?? $mapa_planos['basico'];
        $limite_base = $info_plano['base'];
        $nome_plano_atual = $info_plano['nome'];
        $limite_total = $limite_base + $extras_comprados;

        $status = $dados_assinatura['status_assinatura'] ?? 'padrao';
        
        // --- CÁLCULO DE DIAS ATÉ RENOVAÇÃO ---
        $data_renovacao_str = $dados_assinatura['data_renovacao'] ?? null;
        if ($data_renovacao_str) {
            try {
                $dt_hoje = new DateTime();
                $dt_hoje->setTime(0, 0, 0);
                
                $dt_renovacao = new DateTime($data_renovacao_str);
                $dt_renovacao->setTime(0, 0, 0);
                
                if ($dt_renovacao > $dt_hoje) {
                    $diff = $dt_hoje->diff($dt_renovacao);
                    $dias_ate_renovacao = (int)$diff->format('%a');
                } else {
                    $dias_ate_renovacao = 0; // Vence hoje ou já venceu
                }
            } catch (Exception $e) {
                $dias_ate_renovacao = 0;
            }
        }

        // LÓGICA DE CONTAGEM DO PERÍODO DE TESTE
        if ($status === 'trial') {
            $is_trial = true;
            $dias_teste = ($plano_db === 'essencial') ? 30 : 15;
            $data_ref = $dados_assinatura['data_inicio_teste'] ?? $dados_assinatura['data_criacao'] ?? date('Y-m-d H:i:s');
            
            try {
                $data_inicio = new DateTime($data_ref);
                $data_inicio->setTime(0, 0, 0);
                
                $data_fim_teste = clone $data_inicio;
                $data_fim_teste->modify("+$dias_teste days");
                
                $hoje = new DateTime();
                $hoje->setTime(0, 0, 0);
                
                if ($hoje < $data_fim_teste) {
                    $intervalo = $hoje->diff($data_fim_teste);
                    $dias_restantes_teste = (int)$intervalo->format('%a');
                } else {
                    $dias_restantes_teste = 0;
                }
            } catch (Exception $e) { 
                $dias_restantes_teste = 0; 
            }
        }
    }
    $stmt->close();
}
$conn->close();

// Captura mensagens de sessão para exibir no SweetAlert
$swal_alert = [];
if (!empty($_SESSION['sucesso'])) {
    $swal_alert = ['type' => 'success', 'title' => 'Sucesso!', 'text' => $_SESSION['sucesso']];
    unset($_SESSION['sucesso']);
} elseif (!empty($_SESSION['erro'])) {
    $swal_alert = ['type' => 'error', 'title' => 'Atenção!', 'text' => $_SESSION['erro']];
    unset($_SESSION['erro']);
} elseif (!empty($_SESSION['sucesso_extra'])) {
    $swal_alert = ['type' => 'success', 'title' => 'Atualizado!', 'text' => $_SESSION['sucesso_extra']];
    unset($_SESSION['sucesso_extra']);
} elseif (!empty($_SESSION['erro_extra'])) {
    $swal_alert = ['type' => 'error', 'title' => 'Erro!', 'text' => $_SESSION['erro_extra']];
    unset($_SESSION['erro_extra']);
} elseif (!empty($_SESSION['sucesso_pagamento'])) {
    $swal_alert = ['type' => 'success', 'title' => 'Pagamento Confirmado!', 'text' => $_SESSION['sucesso_pagamento']];
    unset($_SESSION['sucesso_pagamento']);
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Minha Assinatura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ----------- ESTILO GERAL (PADRÃO NEON) ------------ */
        body { background-color: #121212; color: #eee; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-assinatura { 
            max-width: 1000px; 
            margin: 40px auto; 
            padding: 0 20px; 
        }
        
        .page-header {
            border-bottom: 1px solid #00bfff;
            padding-bottom: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .page-header h2 { margin: 0; color: #00bfff; font-size: 1.8rem; }

        /* Cards */
        .card-custom { 
            background-color: #1e1e1e; 
            border: 1px solid #333; 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 25px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
            transition: border-color 0.3s;
        }
        .card-custom:hover { 
            border-color: #444; 
            box-shadow: 0 4px 20px rgba(0, 191, 255, 0.1);
        }

        .card-title {
            color: #00bfff;
            font-size: 1.3rem;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Grid Layout */
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        @media(max-width: 768px) { .grid-layout { grid-template-columns: 1fr; } }

        /* Informações */
        .info-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #2c2c2c; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #aaa; font-size: 0.95rem; }
        .value { font-weight: bold; color: #fff; font-size: 1rem; text-align: right; }

        /* Status */
        .status-ativo { color: #2ecc71; }
        .status-inativo { color: #e74c3c; }
        .extra-highlight { color: #00bfff; font-weight: 800; }

        /* Neon Number */
        .neon-number {
            font-size: 3.5rem; 
            font-weight: bold; 
            color: #fff; 
            line-height: 1; 
            text-shadow: 0 0 15px rgba(0, 191, 255, 0.6);
        }

        /* Botões */
        .btn-custom {
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            color: #fff;
            transition: all 0.2s;
            font-size: 1rem;
        }
        .btn-custom:hover { transform: translateY(-2px); }

        .btn-assinar { background: linear-gradient(135deg, #28a745, #218838); margin-top: 10px; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3); }
        
        .btn-add-user { background: linear-gradient(135deg, #00bfff, #008cba); width: 100%; margin-top: 10px; box-shadow: 0 4px 10px rgba(0, 191, 255, 0.3); }
        .btn-add-user:hover { box-shadow: 0 6px 15px rgba(0, 191, 255, 0.5); }

        .btn-remove-user { 
            background: transparent; 
            border: 1px solid #dc3545; 
            color: #ff6b6b; 
            width: 100%; 
            margin-top: 10px; 
            box-shadow: none;
        }
        .btn-remove-user:hover { 
            background: rgba(220, 53, 69, 0.1); 
            color: #ff4d4d; 
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.2);
        }

        /* Botão Cancelar */
        .btn-cancel {
            background: transparent;
            border: 1px solid #ff003c;
            color: #ff003c;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 0 5px rgba(255, 0, 60, 0.2);
            font-size: 0.95rem;
        }
        .btn-cancel:hover {
            background: rgba(255, 0, 60, 0.1);
            color: #ff4d79;
            border-color: #ff4d79;
            box-shadow: 0 0 15px rgba(255, 0, 60, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-history { background-color: #17a2b8; flex: 1; }
        .btn-receipt { background-color: #6c757d; flex: 1; }
        .btn-group { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; }

        /* Action Box in Card */
        .action-box {
            background: #252525; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 15px; 
            border: 1px dashed #444;
        }
        .action-row {
            display: flex;
            gap: 10px;
        }
        .action-row form { flex: 1; }

        /* Trial Alert */
        .trial-alert { 
            background: rgba(255, 193, 7, 0.15); 
            border: 1px solid #ffc107; 
            color: #ffc107; 
            padding: 15px; 
            border-radius: 8px; 
            font-weight: bold; 
            text-align: center; 
            margin-bottom: 30px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 15px; 
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.1);
        }

        /* Footer links */
        .footer-links {
            text-align: center; 
            margin-top: 30px; 
            display: flex; 
            justify-content: center; 
            gap: 25px;
        }
        .footer-link {
            color: #666; 
            text-decoration: none; 
            font-size: 0.95rem; 
            transition: color 0.3s; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .footer-link:hover { color: #fff; }

        /* Planos Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        .plan-card {
            padding: 20px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            transition: transform 0.2s;
        }
        .plan-card:hover {
            transform: translateY(-3px);
        }
        .plan-card h3 { margin-top: 0; margin-bottom: 10px; font-size: 1.4rem; }
        .plan-desc { color: #aaa; font-size: 0.85rem; margin-bottom: 15px; }
        .plan-limit { color: #fff; font-size: 1.1rem; margin-bottom: 20px; }

        #modalCancelarAssinatura { display: none; }
    </style>
</head>
<body>

<div class="container-assinatura">
    
    <div class="page-header">
        <h2><i class="fa-solid fa-star"></i> Gestão da Assinatura</h2>
    </div>

    <?php 
    if ($is_trial && $dias_restantes_teste > 0): 
    ?>
        <div class="trial-alert">
            <i class="fa-solid fa-clock fa-lg"></i>
            <div>PERÍODO DE TESTE: Restam <strong><?= $dias_restantes_teste ?> dias</strong> gratuitos.</div>
            <a href="assinar.php" class="btn-custom" style="background: #ffc107; color: #000; padding: 6px 15px; font-size: 0.9rem;">Assinar Agora</a>
        </div>
    <?php endif; ?>

    <div class="grid-layout">
        
        <div class="card-custom">
            <div class="card-title"><i class="fa-solid fa-file-contract"></i> Detalhes do Plano</div>
            
            <div class="info-row">
                <span class="label">Plano Atual:</span>
                <span class="value" style="color: #00bfff; font-size: 1.1rem;"><?= htmlspecialchars($nome_plano_atual) ?></span>
            </div>

            <div class="info-row">
                <span class="label">Limite Base do Plano:</span>
                <span class="value"><?= $limite_base ?> Usuários</span>
            </div>

            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value">
                    <?php 
                    $tipo_cancelamento = $dados_assinatura['tipo_cancelamento'] ?? null;

                    if ($tipo_cancelamento) {
                        // SE HOUVER CANCELAMENTO AGENDADO
                        echo '<div style="margin-bottom:5px; color: #ffc107; font-weight:bold;">
                                <i class="fa-solid fa-calendar-xmark"></i> Cancelamento Agendado
                              </div>';
                        echo '<small style="color:#ccc; font-size: 0.85rem;">Sistema sai do ar em: <strong style="color:#fff">' . $dias_ate_renovacao . ' dias</strong></small>';
                    } else {
                        // LÓGICA PADRÃO
                        $st = $dados_assinatura['status_assinatura'] ?? '-';
                        if ($st === 'ativo') {
                            echo '<span class="status-ativo"><i class="fa-solid fa-check-circle"></i> Ativo</span>';
                        } elseif ($st === 'trial') {
                            if ($dias_restantes_teste > 0) {
                                echo '<span style="color: #ffc107"><i class="fa-solid fa-flask"></i> Em Teste</span>';
                            } else {
                                echo '<span class="status-inativo"><i class="fa-solid fa-times-circle"></i> Teste Expirado</span>';
                            }
                        } else {
                            echo '<span class="status-inativo"><i class="fa-solid fa-times-circle"></i> Inativo</span>';
                        }
                    }
                    ?>
                </span>
            </div>

            <?php if (!$is_trial && isset($dados_assinatura['data_renovacao'])): ?>
                <div class="info-row" style="margin-top: 15px;">
                    <span class="label">
                        <?= $tipo_cancelamento ? 'Encerramento em:' : 'Próxima Cobrança:' ?>
                    </span>
                    <span class="value">
                        <?= date('d/m/Y', strtotime($dados_assinatura['data_renovacao'])) ?>
                        <br>
                        <small style="font-size:0.8rem; color:#888; font-weight:normal;">
                            (Faltam <?= $dias_ate_renovacao ?> dias)
                        </small>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (empty($tipo_cancelamento)): ?>
                <div class="mt-4 text-center">
                    <button type="button" class="btn-custom btn-cancel" onclick="abrirModalCancelamento()">
                        <i class="fa-solid fa-ban"></i> Cancelar Assinatura
                    </button>
                </div>
            <?php endif; ?>

            <div class="modal fade" id="modalCancelarAssinatura" tabindex="-1" aria-labelledby="modalCancelarLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content" style="background-color: #2c2c2c; color: #eee; border: 1px solid #444;">
                        <div class="modal-header" style="border-bottom: 1px solid #444;">
                            <h5 class="modal-title" id="modalCancelarLabel" style="color: #ff003c;">Cancelar Assinatura</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Sentimos muito que você queira partir. Por favor, escolha como deseja proceder com sua conta:</p>
                            
                            <form id="formCancelarAssinatura" action="../actions/solicitar_cancelamento.php" method="POST">
                                <div class="form-check mb-3 p-3" style="background: rgba(255,255,255,0.05); border-radius: 5px;">
                                    <input class="form-check-input" type="radio" name="opcao_cancelamento" id="opcao1" value="desativar" required>
                                    <label class="form-check-label" for="opcao1">
                                        <strong style="color: #fff;">Opção 1 - Apenas desativar a conta:</strong><br>
                                        <small style="color: #aaa;">Você terá acesso até <strong><?= date('d/m/Y', strtotime($dados_assinatura['data_renovacao'] ?? 'now')) ?></strong> (daqui a <?= $dias_ate_renovacao ?> dias). Após isso, a conta será suspensa.</small>
                                    </label>
                                </div>
                                
                                <div class="form-check p-3" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 5px;">
                                    <input class="form-check-input" type="radio" name="opcao_cancelamento" id="opcao2" value="excluir" required>
                                    <label class="form-check-label text-danger" for="opcao2">
                                        <strong>Opção 2 - Cancelar e excluir tudo:</strong><br>
                                        <small style="color: #ffb3b3;">Você terá acesso até <strong><?= date('d/m/Y', strtotime($dados_assinatura['data_renovacao'] ?? 'now')) ?></strong>. Após isso, sua conta e <strong>TODOS os dados serão excluídos permanentemente</strong>.</small>
                                    </label>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #444;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" form="formCancelarAssinatura" class="btn btn-danger">Confirmar Cancelamento</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($is_trial || ($dados_assinatura['status_assinatura'] ?? '') !== 'ativo'): ?>
                <a href="assinar.php" class="btn-custom btn-assinar">
                    <i class="fa-solid fa-crown"></i> <?= ($is_trial && $dias_restantes_teste > 0) ? 'Efetivar Assinatura' : 'Reativar Plano' ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="card-custom" style="border-color: rgba(0, 191, 255, 0.3);">
            <div class="card-title"><i class="fa-solid fa-users-gear"></i> Capacidade</div>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <div class="neon-number"><?= $limite_total ?></div>
                <div style="color: #aaa; font-size: 0.9rem;">Usuários Totais Permitidos</div>
            </div>

            <div class="info-row">
                <span class="label">Incluso no Plano:</span>
                <span class="value"><?= $limite_base ?></span>
            </div>
            <div class="info-row">
                <span class="label">Extras Contratados:</span>
                <span class="value extra-highlight">+<?= $extras_comprados ?></span>
            </div>

            <div class="action-box">
                <div style="font-size: 0.85rem; color: #ccc; margin-bottom: 10px; text-align: center;">
                    Gerencie seus slots extras (R$ 4,50/mês cada).
                </div>
                
                <div class="action-row">
                    <form id="formAddExtra" action="../actions/comprar_extra_action.php" method="POST">
                        <input type="hidden" name="qtd_extra" value="1">
                        <button type="button" class="btn-custom btn-add-user" title="Adicionar vaga" onclick="confirmarAdicao()">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </form>

                    <?php if ($extras_comprados > 0): ?>
                    <form id="formRemoveExtra" action="../actions/remover_extra_action.php" method="POST">
                        <input type="hidden" name="qtd_remover" value="1">
                        <button type="button" class="btn-custom btn-remove-user" title="Remover vaga" onclick="confirmarRemocao()">
                            <i class="fa-solid fa-minus"></i> Remover
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="card-custom">
        <div class="card-title"><i class="fa-solid fa-layer-group"></i> Planos Disponíveis</div>
        <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 20px;">Você pode alterar seu plano a qualquer momento. O novo plano entrará em vigor imediatamente.</p>
        
        <div class="plans-grid">
            <?php foreach ($mapa_planos as $slug => $plano): ?>
                <?php 
                    $ativo = ($slug === $plano_db);
                    $border = $ativo ? '2px solid #00bfff' : '1px solid #333';
                    $bg = $ativo ? 'rgba(0, 191, 255, 0.08)' : '#252525';
                    $color_title = $ativo ? '#00bfff' : '#fff';
                ?>
                <div class="plan-card" style="background: <?= $bg ?>; border: <?= $border ?>;">
                    <div>
                        <h3 style="color: <?= $color_title ?>"><?= $plano['nome'] ?></h3>
                        <p class="plan-desc"><?= $plano['desc'] ?></p>
                        <div class="plan-limit"><i class="fa-solid fa-user-group"></i> <?= $plano['base'] ?> Usuários</div>
                    </div>
                    
                    <?php if ($ativo): ?>
                        <button disabled class="btn-custom" style="background: transparent; border: 1px solid #00bfff; color: #00bfff; width: 100%; cursor: default; opacity: 0.8;">
                            <i class="fa-solid fa-check"></i> Plano Atual
                        </button>
                    <?php else: ?>
                        <form id="formPlano_<?= $slug ?>" action="checkout_plano.php" method="POST">
                            <input type="hidden" name="plano" value="<?= $slug ?>">
                            <button type="button" class="btn-custom" style="background: #00bfff; width: 100%;" onclick="confirmarTrocaPlano('<?= $slug ?>', '<?= $plano['nome'] ?>')">
                                Mudar Plano
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-title"><i class="fa-solid fa-wallet"></i> Financeiro</div>
        <p style="color: #aaa; font-size: 0.9rem;">Acesse seu histórico completo de pagamentos e notas fiscais.</p>
        
        <div class="btn-group">
            <a href="historico_pagamento.php" class="btn-custom btn-history">
                <i class="fa-solid fa-list-ul"></i> Histórico
            </a>
            <a href="recibo_pagamentos.php" class="btn-custom btn-receipt">
                <i class="fa-solid fa-file-invoice-dollar"></i> Comprovantes
            </a>
        </div>
    </div>

    <div class="footer-links">
        <a href="perfil.php" class="footer-link">
            <i class="fa-solid fa-user"></i> Voltar para Perfil
        </a>
        <a href="home.php" class="footer-link">
            <i class="fa-solid fa-house"></i> Voltar ao Início
        </a>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Exibe o alerta do SweetAlert se houver mensagem na sessão
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($swal_alert)): ?>
            Swal.fire({
                icon: '<?= $swal_alert['type'] ?>',
                title: '<?= $swal_alert['title'] ?>',
                text: '<?= $swal_alert['text'] ?>',
                background: '#1e1e1e',
                color: '#eee',
                confirmButtonColor: '#00bfff'
            });
        <?php endif; ?>
    });

    // --- SCRIPT PARA ABRIR O MODAL DE CANCELAMENTO ---
    function abrirModalCancelamento() {
        // Instancia o modal usando o ID e chama o método show()
        var myModal = new bootstrap.Modal(document.getElementById('modalCancelarAssinatura'));
        myModal.show();
    }

    // Modal de Confirmação de Adição
    function confirmarAdicao() {
        Swal.fire({
            title: 'Adicionar Usuário Extra?',
            text: "Isso adicionará R$ 4,50 mensais à sua fatura.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#00bfff',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, adicionar',
            cancelButtonText: 'Cancelar',
            background: '#1e1e1e',
            color: '#eee'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formAddExtra').submit();
            }
        });
    }

    // Modal de Confirmação de Remoção
    function confirmarRemocao() {
        Swal.fire({
            title: 'Remover Usuário Extra?',
            html: "Tem certeza que deseja remover 1 vaga extra?<br><small style='color:#ff6b6b'>Certifique-se de que o número de usuários ativos cabe no novo limite.</small>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Cancelar',
            background: '#1e1e1e',
            color: '#eee'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formRemoveExtra').submit();
            }
        });
    }

    // Modal de Confirmação de Troca de Plano
    function confirmarTrocaPlano(slug, nomePlano) {
        Swal.fire({
            title: 'Alterar para ' + nomePlano + '?',
            html: "Você está prestes a migrar para o <strong>" + nomePlano + "</strong>.<br>Seu limite de usuários será atualizado e a nova cobrança entrará em vigor.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00bfff',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, alterar plano',
            cancelButtonText: 'Cancelar',
            background: '#1e1e1e',
            color: '#eee'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formPlano_' + slug).submit();
            }
        });
    }
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>