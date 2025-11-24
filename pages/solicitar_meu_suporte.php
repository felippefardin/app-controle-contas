<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. Verifica Login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$tenant_id = $_SESSION['tenant_id'] ?? '';
$mes_atual = date('Y-m');

// Conecta ao Banco do Tenant para pegar o e-mail do usuário
$connTenant = getTenantConnection();
$email_usuario = "";
if ($connTenant) {
    $stmt = $connTenant->prepare("SELECT email FROM usuarios WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($email_usuario);
        $stmt->fetch();
        $stmt->close();
    }
    // Não fechamos a conexão aqui propositalmente: getTenantConnection() gerencia
}

// Valores Padrão
$plano = 'basico';
$uso_chat_online = 0;
$uso_chat_aovivo = 0;

// Conecta ao Banco Master para checar PLANO REAL e USO
$connMaster = getMasterConnection();
if ($connMaster) {
    // 1. Busca o plano atual (CORREÇÃO: usar plano_atual)
    $stmt_plano = $connMaster->prepare("SELECT plano_atual FROM tenants WHERE tenant_id = ? LIMIT 1");
    if ($stmt_plano) {
        $stmt_plano->bind_param("s", $tenant_id);
        $stmt_plano->execute();
        $stmt_plano->bind_result($plano_db);
        if ($stmt_plano->fetch()) {
            $plano_db = strtolower(trim((string)$plano_db));
            if (!empty($plano_db)) {
                $plano = $plano_db;
            }
        }
        $stmt_plano->close();
    }

    // 2. Busca o uso do suporte (se existir)
    $stmt_sup = $connMaster->prepare("SELECT uso_chat_online, uso_chat_aovivo FROM suporte_usage WHERE tenant_id = ? AND mes_ano = ? LIMIT 1");
    if ($stmt_sup) {
        $stmt_sup->bind_param("ss", $tenant_id, $mes_atual);
        $stmt_sup->execute();
        $stmt_sup->bind_result($uso_online_db, $uso_aovivo_db);
        if ($stmt_sup->fetch()) {
            $uso_chat_online = (int)$uso_online_db;
            $uso_chat_aovivo = (int)$uso_aovivo_db;
        }
        $stmt_sup->close();
    }

    $connMaster->close();
}

// Fallbacks
if (empty($plano)) $plano = 'basico';
if (!is_int($uso_chat_online)) $uso_chat_online = (int)$uso_chat_online;
if (!is_int($uso_chat_aovivo)) $uso_chat_aovivo = (int)$uso_chat_aovivo;

// Definição das Regras de Negócio (padronizadas conforme solicitado)
// NOTE: ESSENCIAL tem 3 atendimentos online gratuitos/mês; PLUS tem 1.
$regras = [
    'basico'    => ['cota_online' => 0, 'cota_aovivo' => 0, 'preco_online' => 5.99, 'preco_aovivo' => 15.99],
    'plus'      => ['cota_online' => 1, 'cota_aovivo' => 1, 'preco_online' => 8.99, 'preco_aovivo' => 15.99],
    'essencial' => ['cota_online' => 3, 'cota_aovivo' => 1, 'preco_online' => 8.99, 'preco_aovivo' => 15.99]
];

$regraAtual = $regras[$plano] ?? $regras['basico'];

// Cálculos de Restante
$restante_online = max(0, $regraAtual['cota_online'] - $uso_chat_online);
$restante_aovivo = max(0, $regraAtual['cota_aovivo'] - $uso_chat_aovivo);

// Detecta se todas as cotas gratuitas foram esgotadas (apenas relevante se houver cotas)
$todos_esgotados = false;
if (($regraAtual['cota_online'] > 0 || $regraAtual['cota_aovivo'] > 0)) {
    if ($restante_online === 0 && $restante_aovivo === 0) {
        $todos_esgotados = true;
    }
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Suporte</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-suporte {
            max-width: 900px;
            margin: 40px auto;
            background: #1e1e1e;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            border: 1px solid #333;
        }
        h2 {
            color: #00bfff;
            border-bottom: 1px solid #00bfff;
            padding-bottom: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        .plan-badge {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        .plan-name {
            color: #2ecc71;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        /* Tabela de Preços */
        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #252525;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background-color: #00bfff;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        tr:hover {
            background-color: #2a2a2a;
        }
        .price-cell {
            font-weight: bold;
            color: #ffc107;
        }
        .free-cell {
            color: #2ecc71;
            font-weight: bold;
        }
        .desc-text {
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Botão Ação */
        .btn-custom {
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: bold;
            font-size: 1.1rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            background: #00bfff;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 191, 255, 0.3);
        }
        .btn-custom:hover {
            background-color: #0099cc;
            transform: translateY(-2px);
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            color: #fff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        /* MODAL STYLES (Reutilizado) */
        #modalSuporte {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: #1e1e1e;
            margin: 5% auto; 
            padding: 30px;
            border: 1px solid #00bfff;
            width: 90%; 
            max-width: 500px; 
            border-radius: 10px; 
            position: relative; 
            color: #fff;
            box-shadow: 0 0 20px rgba(0, 191, 255, 0.3);
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }
        .close-modal:hover { color: #fff; }

        /* Inputs Modal */
        .form-group { margin-bottom: 20px; }
        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #444;
            background: #252525;
            color: #fff;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #00bfff;
        }
        .btn-danger-custom {
            background: transparent;
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-submit-modal {
            background: linear-gradient(135deg, #00bfff, #0099cc);
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .modal-alert {
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            display: none;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        @media (max-width: 768px) {
            th, td { padding: 10px; font-size: 0.9rem; }
            .container-suporte { padding: 15px; margin: 20px auto; }
        }
    </style>
</head>
<body>

<div class="container-suporte">
    <h2><i class="fas fa-headset"></i> Solicitar Suporte</h2>
    
    <div class="plan-badge">
        Seu plano atual: <span class="plan-name"><?= htmlspecialchars(strtoupper($plano)) ?></span>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th>Descrição</th>
                    <th>Preço / Disponibilidade</th>
                </tr>
            </thead>
            <tbody>
                <!-- Linha Chat Online -->
                <tr>
                    <td><i class="fas fa-comments"></i> Chat Online</td>
                    <td class="desc-text">Suporte via chat de texto (conversação orientada, duração ~1 hora).</td>
                    <td>
                        <?php if ($restante_online > 0): ?>
                            <span class="free-cell">Gratuito (Restam <?= $restante_online ?>)</span>
                        <?php else: ?>
                            <span class="price-cell">R$ <?= number_format($regraAtual['preco_online'], 2, ',', '.') ?></span>
                            <?php if($plano != 'basico') echo "<br><small style='color:#f55'>(Cota excedida)</small>"; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <!-- Linha Chat Ao Vivo -->
                <tr>
                    <td><i class="fas fa-video"></i> Chat Ao Vivo</td>
                    <td class="desc-text">Suporte via vídeo/voz (realizado por aplicativo, duração ~1 hora).</td>
                    <td>
                        <?php if ($restante_aovivo > 0): ?>
                            <span class="free-cell">Gratuito (Restam <?= $restante_aovivo ?>)</span>
                        <?php else: ?>
                            <span class="price-cell">R$ <?= number_format($regraAtual['preco_aovivo'], 2, ',', '.') ?></span>
                            <?php if($plano != 'basico') echo "<br><small style='color:#f55'>(Cota excedida)</small>"; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if ($todos_esgotados): ?>
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Você não possui mais chamados gratuitos para este mês. Solicite suporte com pagamento se desejar.
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px;">
        <button class="btn-custom" onclick="abrirModalSuporte()">
            <i class="fas fa-ticket-alt"></i> Solicitar Agora
        </button>
        <a href="perfil.php" style="display: block; margin-top: 15px; color: #aaa; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Voltar ao Perfil
        </a>
    </div>
</div>

<!-- MODAL DE SOLICITAÇÃO -->
<div id="modalSuporte">
    <div class="modal-content">
        <span onclick="fecharModalSuporte()" class="close-modal">&times;</span>
        
        <h3 style="color: #00bfff; margin-top: 0;"><i class="fas fa-ticket-alt"></i> Novo Chamado</h3>
        <hr style="border-color: #444; margin-bottom: 20px;">
        
        <form id="formChamado" action="../actions/salvar_chamado.php" method="POST">
            <div class="form-group">
                <label>E-mail de Contato:</label>
                <input type="text" name="email_fixo" class="form-control" value="<?= htmlspecialchars($email_usuario) ?>" readonly style="background: #333; color: #aaa; border: 1px solid #555;">
            </div>

            <div class="form-group">
                <label>Tipo de Suporte:</label>
                <select name="tipo_suporte" id="tipo_suporte" class="form-control" onchange="atualizarPreco()" required>
                    <option value="">Selecione uma opção...</option>
                    <option value="chat_online">Chat Online (Texto)</option>
                    <option value="chat_aovivo">Chat Ao Vivo (Vídeo/Voz)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descrição do Problema:</label>
                <textarea name="descricao" class="form-control" rows="4" placeholder="Descreva detalhadamente o que está acontecendo..." required></textarea>
            </div>

            <!-- Campo oculto para informar se será cobrado (será avaliado em salvar_chamado.php) -->
            <input type="hidden" name="plano_atual" value="<?= htmlspecialchars($plano) ?>">
            <input type="hidden" id="custo_estimado_input" name="custo_estimado" value="0">

            <div id="avisoPreco" class="modal-alert">
                Custo estimado: <strong>R$ <span id="valorEstimado">0,00</span></strong>
            </div>

            <div style="text-align: right; margin-top: 25px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-danger-custom" onclick="fecharModalSuporte()">Cancelar</button>
                <button type="submit" class="btn-submit-modal">Confirmar e Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Dados vindos do PHP (seguros)
    const planoUsuario = "<?= addslashes($plano) ?>";
    const usoAtual = {
        online: <?= (int)$uso_chat_online ?>,
        aovivo: <?= (int)$uso_chat_aovivo ?>
    };

    // Regras vindas do PHP (Json Encode para segurança)
    const regras = <?= json_encode($regras) ?>;

    function abrirModalSuporte() {
        document.getElementById('modalSuporte').style.display = "block";
        document.getElementById('tipo_suporte').value = "";
        document.getElementById('avisoPreco').style.display = "none";
        document.getElementById('valorEstimado').innerText = '0,00';
        document.getElementById('custo_estimado_input').value = '0';
    }

    function fecharModalSuporte() {
        document.getElementById('modalSuporte').style.display = "none";
    }

    function atualizarPreco() {
        const tipo = document.getElementById('tipo_suporte').value;
        const aviso = document.getElementById('avisoPreco');
        const spanValor = document.getElementById('valorEstimado');
        const custoInput = document.getElementById('custo_estimado_input');
        
        if (!tipo) {
            aviso.style.display = "none";
            custoInput.value = '0';
            return;
        }

        const regra = regras[planoUsuario] || regras['basico'];
        
        let custo = 0;
        let ehGratis = false;
        let msgRestante = "";

        if (tipo === 'chat_online') {
            if (usoAtual.online < regra.cota_online) {
                custo = 0;
                ehGratis = true;
                let restantes = regra.cota_online - usoAtual.online;
                msgRestante = `(Restam ${restantes} gratuitos)`;
            } else {
                custo = regra.preco_online;
            }
        } else if (tipo === 'chat_aovivo') {
            if (usoAtual.aovivo < regra.cota_aovivo) {
                custo = 0;
                ehGratis = true;
                let restantes = regra.cota_aovivo - usoAtual.aovivo;
                msgRestante = `(Restam ${restantes} gratuitos)`;
            } else {
                custo = regra.preco_aovivo;
            }
        }

        spanValor.innerText = custo.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        custoInput.value = custo.toFixed(2);
        
        if (ehGratis) {
            aviso.style.background = "rgba(40, 167, 69, 0.2)";
            aviso.style.border = "1px solid #28a745";
            aviso.style.color = "#2ecc71";
            aviso.innerHTML = `<i class="fas fa-gift"></i> Custo: <strong>Grátis</strong> ${msgRestante}`;
        } else {
            aviso.style.background = "rgba(255, 193, 7, 0.1)";
            aviso.style.border = "1px solid #ffc107";
            aviso.style.color = "#fff";
            aviso.innerHTML = `<i class="fas fa-coins"></i> Custo: <strong>R$ ${custo.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`;
        }
        aviso.style.display = "block";
    }

    // Fecha modal ao clicar fora
    window.onclick = function(event) {
        const modal = document.getElementById('modalSuporte');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
<?php include('../includes/footer.php'); ?>
