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
        <a href="../pages/home.php" class="btn btn-home btn-header" title="Página Inicial"><i class="fas fa-home"></i>Home</a>
        <a href="../pages/logout.php" class="btn btn-exit btn-header" title="Sair do sistema"><i class="fas fa-sign-out-alt"></i>Sair</a>
    </div>
</header>

<main>