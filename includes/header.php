<?php
// Garante que a sessão seja iniciada ANTES de tentar ler as variáveis $_SESSION.
require_once __DIR__ . '/session_init.php';

// --- CONFIGURAÇÃO DE SEGURANÇA VISUAL ---
$emails_master_permitidos = [
    'contatotech.tecnologia@gmail.com', 
    'contatotech.tecnologia@gmail.com.br'
];

$exibir_banner_master = false;
if (isset($_SESSION['super_admin_original']) && is_array($_SESSION['super_admin_original'])) {
    $email_admin = $_SESSION['super_admin_original']['email'] ?? '';
    if (in_array($email_admin, $emails_master_permitidos)) {
        $exibir_banner_master = true;
    }
}

// RECUPERA PREFERÊNCIA DE TEMA
$temaAtual = $_SESSION['tema_preferencia'] ?? 'dark';
$classeBody = ($temaAtual === 'light') ? 'light-mode' : '';
?>

<?php if (isset($_SESSION['proprietario_id_original'])): ?>
    <div style="background-color: #222; color: #0af; padding: 10px; text-align: center; font-weight: bold; border-bottom: 1px solid #444;">
        <i class="fas fa-user-secret"></i> Você está visualizando como 
        <strong><?= htmlspecialchars($_SESSION['usuario_original_nome'] ?? 'Administrador'); ?></strong>.
        &nbsp;
        <a href="../actions/retornar_admin.php" style="color: #fff; text-decoration: underline; margin-left: 10px;">
            <i class="fas fa-undo"></i> Voltar para o Acesso Proprietário
        </a>
    </div>    
<?php endif; ?>

<?php if ($exibir_banner_master): ?>
    <div style="background-color: #ffc; border: 1px solid #e6db55; padding: 10px; text-align: center; font-weight: bold; position: fixed; top: 0; width: 100%; z-index: 1002; color: #000000;">
        Você está visualizando como um cliente. 
        <a href="../actions/retornar_super_admin.php" style="color: #0056b3; text-decoration: underline;">Retornar ao Dashboard de Administrador</a>
    </div>
    <?php echo '<style>body { padding-top: 40px !important; } .header-controls { top: 40px !important; }</style>'; ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" /> 
  <title>App Controle de Contas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* ====== VARIÁVEIS DE TEMA ====== */
    :root {
        --bg-body: #121212;
        --bg-card: #1f1f1f;
        --text-primary: #eee;
        --text-secondary: #ccc;
        --highlight-color: #00bfff;
        --header-bg: #1f1f1f;
    }

    body.light-mode {
        --bg-body: #f4f6f9;
        --bg-card: #ffffff;
        --text-primary: #333333;
        --text-secondary: #555555;
        --highlight-color: #007bff;
        --header-bg: #ffffff;
    }

    /* APLICAÇÃO GERAL */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        margin: 0;
        background-color: var(--bg-body) !important;
        color: var(--text-primary) !important;
        font-family: Arial, sans-serif;
        overflow-x: hidden;
        transition: background-color 0.3s, color 0.3s;
    }

    /* Conteúdo principal FULL DESKTOP */
    main {
        flex: 1;
        padding-top: 90px;  
        padding-bottom: 60px;
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
        box-sizing: border-box;
    }

    /* ====== HEADER FIXO ====== */
    .header-controls {
        background-color: var(--header-bg);
        padding: 15px 25px;
        position: fixed;
        top: 0; left: 0;
        width: 100%;
        z-index: 1001;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 25px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        box-sizing: border-box;
        transition: background-color 0.3s;
    }

    /* Grupo de botões */
    .header-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Botões */
    .header-controls .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #007bff;
        color: white;
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        font-weight: bold;
        font-size: 14px;
        transition: transform 0.2s ease, opacity 0.3s ease;
    }

    .header-controls .btn:hover {
        opacity: 0.85;
        transform: scale(1.04);
    }
    .header-controls .btn i { margin-right: 6px; }

    .btn-home { background-color: #28a745 !important; }
    .btn-exit { background-color: #dc3545 !important; }
    
    /* Botão Theme Toggle */
    .btn-theme {
        background: transparent !important;
        border: 1px solid var(--text-secondary) !important;
        color: var(--text-primary) !important;
    }
    .btn-theme:hover {
        background: rgba(128,128,128,0.2) !important;
    }

    /* Responsividade */
    @media (min-width: 1440px) {
        main { max-width: 1600px; padding-left: 40px; padding-right: 40px; }
    }
    @media (max-width: 1024px) {
        main { max-width: 95%; padding-top: 120px; padding-bottom: 70px; }
        .header-controls { padding: 18px 20px; gap: 20px; }
        .header-group { gap: 10px; flex-wrap: wrap; justify-content: center; }
        .header-controls .btn { font-size: 14px; padding: 12px 18px; }
    }
    @media (max-width: 850px) {
        main { width: 94%; padding-top: 140px; padding-bottom: 80px; }
        .header-controls { flex-direction: column; text-align: center; padding: 18px 14px; gap: 15px; }
        .header-group { width: 100%; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .header-controls .btn { max-width: 260px; font-size: 15px; padding: 12px 18px; }
    }
    @media (max-width: 480px) {
        main { width: 96%; padding-top: 160px; padding-bottom: 90px; }
        .header-controls { padding: 20px 10px; }
        .header-controls .btn { font-size: 16px; padding: 14px 20px; }
    }
  </style>
</head>

<body class="<?= $classeBody ?>">
  <header class="header-controls">
    <div class="header-group">
        <button type="button" class="btn btn-header" onclick="adjustFontSize(-1)" title="Diminuir fonte">A-</button>
        <button type="button" class="btn btn-header" onclick="adjustFontSize(1)" title="Aumentar fonte">A+</button>
        <button type="button" class="btn btn-header" onclick="resetFontSize()" title="Restaurar fonte"><i class="fas fa-sync-alt"></i>Resetar</button>
    </div>

    <div class="header-group">
        <button id="themeToggle" class="btn btn-theme btn-header" onclick="toggleTheme()" title="Alternar Tema">
             <i class="fas <?= ($temaAtual === 'light') ? 'fa-moon' : 'fa-sun' ?>"></i>
        </button>

        <?php if (basename($_SERVER['PHP_SELF']) === 'home.php'): ?>
            <button type="button" class="btn btn-header" data-bs-toggle="modal" data-bs-target="#modalFeedbackHeader" title="Deixe seu feedback" style="background-color: #ffc107; color: #000;">
                <i class="fa-solid fa-comment-dots"></i> Feedback
            </button>
        <?php endif; ?>
        
        <a href="../pages/home.php" class="btn btn-home btn-header" title="Página Inicial"><i class="fas fa-home"></i>Home</a>
        <a href="../pages/logout.php" class="btn btn-exit btn-header" title="Sair do sistema"><i class="fas fa-sign-out-alt"></i>Sair</a>
    </div>
</header>

<?php 
// --- MODAL DE FEEDBACK E SCRIPTS ---
if (basename($_SERVER['PHP_SELF']) === 'home.php'): 
?>
<div class="modal fade" id="modalFeedbackHeader" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1f1f1f; color: #eee; border: 1px solid #333;">
            <div class="modal-header" style="border-bottom: 1px solid #333;">
                <h5 class="modal-title" style="color: #ffc107;"><i class="fa-solid fa-star"></i> Enviar Feedback</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formFeedbackHeader">
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="anonimoFeedHeader" name="anonimo">
                        <label class="form-check-label" for="anonimoFeedHeader" style="color: #aaa;">Enviar Anonimamente</label>
                    </div>

                    <div id="dadosIdentificacaoHeader">
                        <div class="mb-3">
                            <label class="form-label" style="color: #ccc;">Nome</label>
                            <input type="text" name="nome" class="form-control" style="background: #252525; border: 1px solid #444; color: #fff;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: #ccc;">Email</label>
                            <input type="email" name="email" class="form-control" style="background: #252525; border: 1px solid #444; color: #fff;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: #ccc;">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" style="background: #252525; border: 1px solid #444; color: #fff;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #ccc;">Avaliação</label>
                        <select name="pontuacao" class="form-select" style="background: #252525; border: 1px solid #444; color: #fff;">
                            <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                            <option value="4">⭐⭐⭐⭐ Muito Bom</option>
                            <option value="3">⭐⭐⭐ Bom</option>
                            <option value="2">⭐⭐ Regular</option>
                            <option value="1">⭐ Ruim</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #ccc;">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="3" required style="background: #252525; border: 1px solid #444; color: #fff;" placeholder="Conte sua experiência..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #333;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-warning" onclick="enviarFeedbackHeader()" style="color: #000; font-weight: bold;">Enviar Feedback</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica do checkbox anônimo
    document.addEventListener('DOMContentLoaded', () => {
        const checkAnonimo = document.getElementById('anonimoFeedHeader');
        const dadosIdentificacao = document.getElementById('dadosIdentificacaoHeader');

        if(checkAnonimo && dadosIdentificacao) {
            checkAnonimo.addEventListener('change', function() {
                if (this.checked) {
                    dadosIdentificacao.style.display = 'none';
                    dadosIdentificacao.querySelectorAll('input').forEach(input => input.value = '');
                } else {
                    dadosIdentificacao.style.display = 'block';
                }
            });
        }
    });

    // Função de envio
    function enviarFeedbackHeader() {
        const form = document.getElementById('formFeedbackHeader');
        if(!form) return;

        const formData = new FormData(form);
        const btnEnviar = document.querySelector('#modalFeedbackHeader .modal-footer button.btn-warning');
        const textoOriginal = btnEnviar.innerText;
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        fetch('../actions/enviar_feedback_publico.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                alert(data.msg);
                form.reset();
                const modalEl = document.getElementById('modalFeedbackHeader');
                let modal = bootstrap.Modal.getInstance(modalEl);
                if (!modal) modal = new bootstrap.Modal(modalEl);
                modal.hide();
            } else {
                alert(data.msg || 'Erro ao enviar feedback.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão ao enviar feedback.');
        })
        .finally(() => {
            btnEnviar.disabled = false;
            btnEnviar.innerText = textoOriginal;
        });
    }    
</script>
<?php endif; ?>

<script>
function toggleTheme() {
    const body = document.body;
    const isLight = body.classList.toggle('light-mode');
    const icon = document.querySelector('#themeToggle i');
    
    // Troca ícone
    if (isLight) {
        if(icon) {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    } else {
        if(icon) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    // Salva no Backend via AJAX (crie actions/salvar_tema.php se não existir)
    const formData = new FormData();
    formData.append('tema', isLight ? 'light' : 'dark');

    fetch('../actions/salvar_tema.php', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(data => {
        console.log('Tema salvo:', data);
    }).catch(e => console.log('Erro ao salvar tema', e));
}
</script>

<main>