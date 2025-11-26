<?php
// Garante que a sessão seja iniciada ANTES de tentar ler as variáveis $_SESSION.
require_once __DIR__ . '/session_init.php';

// --- CONFIGURAÇÃO DE SEGURANÇA VISUAL ---
// Lista de e-mails permitidos para ver o banner de "Retornar ao Admin"
$emails_master_permitidos = [
    'contatotech.tecnologia@gmail.com', 
    'contatotech.tecnologia@gmail.com.br'
];

// Verifica se existe a sessão de impersonação E se o e-mail original é o do Master
$exibir_banner_master = false;
if (isset($_SESSION['super_admin_original']) && is_array($_SESSION['super_admin_original'])) {
    $email_admin = $_SESSION['super_admin_original']['email'] ?? '';
    if (in_array($email_admin, $emails_master_permitidos)) {
        $exibir_banner_master = true;
    }
}
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
    <?php
    // Adiciona um espaçamento no topo para o banner não cobrir o header principal
    echo '<style>body { padding-top: 40px !important; } .header-controls { top: 40px !important; }</style>';
    ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>App Controle de Contas</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
   /* ====== CONFIGURAÇÃO GERAL ====== */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    margin: 0;
    background-color: #121212;
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}

main {
    flex: 1;
    padding-top: 90px;   /* Área do header fixo */
    padding-bottom: 60px;
    max-width: 1400px; /* largura máxima centralizada */
    margin: 0 auto;
    width: 100%;
}

/* ====== HEADER FIXO ====== */
.header-controls {
    background-color: #1f1f1f;
    padding: 15px 25px;

    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1001;

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 25px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    box-sizing: border-box;
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

.header-controls .btn i {
    margin-right: 6px;
}

.btn-home { background-color: #28a745 !important; }
.btn-exit { background-color: #dc3545 !important; }

/* ====== MOBILE RESPONSIVO ====== */
@media (max-width: 850px) {

    main {
        padding-top: 140px;  /* aumenta espaço pois o header cresce */
        padding-bottom: 80px;
        width: 95%;
    }

    /* Header organizado em coluna */
    .header-controls {
        flex-direction: column;
        gap: 15px;
        text-align: center;
        padding: 18px;
    }

    .header-group {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .header-controls .btn {
        width: 100%;
        max-width: 250px;
        font-size: 15px;
        padding: 12px 18px;
    }
}

/* ====== EXTRA RESPONSIVO (CELULARES MENORES) ====== */
@media (max-width: 480px) {
    
    main {
        padding-top: 160px;
        padding-bottom: 90px;
    }

    .header-controls {
        padding: 20px 10px;
    }

    .header-controls .btn {
        max-width: 100%;
        font-size: 16px;
        padding: 14px 20px;
    }
}
  </style>
</head>

<body>
  <header class="header-controls">
    <div class="header-group">
        <button type="button" class="btn btn-header" onclick="adjustFontSize(-1)" title="Diminuir fonte">A-</button>
        <button type="button" class="btn btn-header" onclick="adjustFontSize(1)" title="Aumentar fonte">A+</button>
        <button type="button" class="btn btn-header" onclick="resetFontSize()" title="Restaurar fonte"><i class="fas fa-sync-alt"></i>Resetar</button>
    </div>

    <div class="header-group">
        <?php 
        // --- LÓGICA: EXIBIR FEEDBACK APENAS NA HOME ---
        if (basename($_SERVER['PHP_SELF']) === 'home.php'): 
        ?>
            <button type="button" class="btn btn-header" data-bs-toggle="modal" data-bs-target="#modalFeedbackHeader" title="Deixe seu feedback" style="background-color: #ffc107; color: #000;">
                <i class="fa-solid fa-comment-dots"></i> Feedback
            </button>
        <?php endif; ?>

        <a href="../pages/home.php" class="btn btn-home btn-header" title="Página Inicial"><i class="fas fa-home"></i>Home</a>
        <a href="../pages/logout.php" class="btn btn-exit btn-header" title="Sair do sistema"><i class="fas fa-sign-out-alt"></i>Sair</a>
    </div>
</header>

<?php 
// --- MODAL DE FEEDBACK E SCRIPTS (APENAS NA HOME) ---
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
        
        // Botão com loading
        const btnEnviar = document.querySelector('#modalFeedbackHeader .modal-footer button.btn-warning');
        const textoOriginal = btnEnviar.innerText;
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        fetch('../actions/enviar_feedback_publico.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                if(typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.msg, background: '#1f1f1f', color: '#fff' });
                } else {
                    alert(data.msg);
                }
                form.reset();
                
                // Fecha o modal usando a instância do Bootstrap
                const modalEl = document.getElementById('modalFeedbackHeader');
                // Tenta obter a instância existente ou cria uma nova
                let modal = bootstrap.Modal.getInstance(modalEl);
                if (!modal) {
                    modal = new bootstrap.Modal(modalEl);
                }
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

<main>