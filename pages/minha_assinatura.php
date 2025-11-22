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
$dias_restantes = 0;
$is_trial = false;
$nome_exibicao = $_SESSION['nome'] ?? 'Cliente'; 

$limite_base = 3;
$extras_comprados = 0;
$nome_plano_atual = 'Básico';
$limite_total = 3;

if ($tenant_id) {
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

        $mapa_planos = [
            'basico'    => ['nome' => 'Plano Básico', 'base' => 3],
            'plus'      => ['nome' => 'Plano Plus', 'base' => 6],
            'essencial' => ['nome' => 'Plano Essencial', 'base' => 16]
        ];

        $info_plano = $mapa_planos[$plano_db] ?? $mapa_planos['basico'];
        $limite_base = $info_plano['base'];
        $nome_plano_atual = $info_plano['nome'];
        $limite_total = $limite_base + $extras_comprados;

        $status = $dados_assinatura['status_assinatura'] ?? 'padrao';
        if ($status === 'trial') {
            $is_trial = true;
            $dias_teste = ($plano_db === 'essencial') ? 30 : 15;
            $data_ref = $dados_assinatura['data_inicio_teste'] ?? $dados_assinatura['data_criacao'] ?? date('Y-m-d H:i:s');
            
            try {
                $data_inicio = new DateTime($data_ref);
                $data_fim_teste = clone $data_inicio;
                $data_fim_teste->modify("+$dias_teste days");
                $hoje = new DateTime();
                $intervalo = $hoje->diff($data_fim_teste);
                $dias_restantes = (int)$intervalo->format('%r%a');
            } catch (Exception $e) { $dias_restantes = 0; }
        }
    }
    $stmt->close();
}
$conn->close();

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Minha Assinatura</title>
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
        .value { font-weight: bold; color: #fff; font-size: 1rem; }

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

        /* Alertas Sucesso/Erro */
        .alert-message { 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-weight: 500;
        }
        .alert-success { background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #2ecc71; }
        .alert-error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; color: #ff6b6b; }

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
    </style>
</head>
<body>

<div class="container-assinatura">
    
    <div class="page-header">
        <h2><i class="fa-solid fa-star"></i> Gestão da Assinatura</h2>
    </div>

    <!-- Mensagens de Feedback -->
    <?php if(!empty($_SESSION['sucesso_extra'])): ?>
        <div class="alert-message alert-success">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['sucesso_extra']) ?>
        </div>
        <?php unset($_SESSION['sucesso_extra']); ?>
    <?php endif; ?>

    <?php if(!empty($_SESSION['erro_extra'])): ?>
        <div class="alert-message alert-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['erro_extra']) ?>
        </div>
        <?php unset($_SESSION['erro_extra']); ?>
    <?php endif; ?>

    <?php if(!empty($_SESSION['sucesso_pagamento'])): ?>
        <div class="alert-message alert-success">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_SESSION['sucesso_pagamento']) ?>
        </div>
        <?php unset($_SESSION['sucesso_pagamento']); ?>
    <?php endif; ?>

    <!-- Alerta Trial -->
    <?php if ($is_trial && $dias_restantes > 0): ?>
        <div class="trial-alert">
            <i class="fa-solid fa-clock fa-lg"></i>
            <div>PERÍODO DE TESTE: Restam <strong><?= $dias_restantes ?> dias</strong> gratuitos.</div>
            <a href="assinar.php" class="btn-custom" style="background: #ffc107; color: #000; padding: 6px 15px; font-size: 0.9rem;">Assinar Agora</a>
        </div>
    <?php elseif ($is_trial && $dias_restantes <= 0): ?>
        <div class="trial-alert" style="border-color: #dc3545; color: #dc3545; background: rgba(220, 53, 69, 0.1);">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>Seu período de teste expirou!</div>
        </div>
    <?php endif; ?>

    <div class="grid-layout">
        
        <!-- CARD 1: Detalhes do Plano -->
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
                    $st = $dados_assinatura['status_assinatura'] ?? '-';
                    if ($st === 'ativo') echo '<span class="status-ativo"><i class="fa-solid fa-check-circle"></i> Ativo</span>';
                    elseif ($st === 'trial') echo '<span style="color: #ffc107"><i class="fa-solid fa-flask"></i> Em Teste</span>';
                    else echo '<span class="status-inativo"><i class="fa-solid fa-times-circle"></i> Inativo</span>';
                    ?>
                </span>
            </div>

            <?php if (!$is_trial && isset($dados_assinatura['data_renovacao'])): ?>
                <div class="info-row">
                    <span class="label">Renovação:</span>
                    <span class="value"><?= date('d/m/Y', strtotime($dados_assinatura['data_renovacao'])) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($is_trial || ($dados_assinatura['status_assinatura'] ?? '') !== 'ativo'): ?>
                <a href="assinar.php" class="btn-custom btn-assinar">
                    <i class="fa-solid fa-crown"></i> <?= $is_trial ? 'Efetivar Assinatura' : 'Reativar Plano' ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- CARD 2: Usuários Extras -->
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
                    Gerencie seus slots extras (R$ 1,50/mês cada).
                </div>
                
                <div class="action-row">
                    <!-- Adicionar -->
                    <form id="formAddExtra" action="../actions/comprar_extra_action.php" method="POST">
                        <input type="hidden" name="qtd_extra" value="1">
                        <button type="button" class="btn-custom btn-add-user" title="Adicionar vaga" onclick="confirmarAdicao()">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </form>

                    <!-- Remover (Só aparece se tiver extras) -->
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

    <!-- CARD 3: Financeiro -->
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
<script>
    // Remove alertas automaticamente
    setTimeout(function() {
        $('.alert-message').fadeOut('slow');
    }, 6000);

    // Modal de Confirmação de Adição
    function confirmarAdicao() {
        Swal.fire({
            title: 'Adicionar Usuário Extra?',
            text: "Isso adicionará R$ 1,50 mensais à sua fatura.",
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
</script>

<?php include('../includes/footer.php'); ?>
</body>
</html>