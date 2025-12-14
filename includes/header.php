<?php
require_once __DIR__ . '/session_init.php';

/* ===============================
   CONFIGURAÇÕES
================================ */
$emails_master_permitidos = [
    'contatotech.tecnologia@gmail.com', 
    'contatotech.tecnologia@gmail.com.br'
];

$exibir_banner_master = false;
if (isset($_SESSION['super_admin_original'])) {
    $email_admin = $_SESSION['super_admin_original']['email'] ?? '';
    if (in_array($email_admin, $emails_master_permitidos)) {
        $exibir_banner_master = true;
    }
}

// Define o tema com base na sessão (padrão 'dark')
$temaAtual  = $_SESSION['tema_preferencia'] ?? 'dark';
$classeBody = ($temaAtual === 'light') ? 'light-mode' : '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>App Controle de Contas</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/theme.css">

<style>
/* ===============================
   BODY
================================ */
body {
    margin: 0;
    min-height: 100vh;
    background-color: var(--bg-body);
    color: var(--text-primary);
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}

/* ===============================
   HEADER FIXO
================================ */
.header-controls {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1001;
    background-color: var(--bg-header);
    box-shadow: var(--shadow);
    padding: 14px 28px;

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
}

.header-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* ===============================
   BOTÕES
================================ */
.header-controls .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: bold;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    color: #fff;
    background: #007bff;
}

.btn-home { background: #28a745; }
.btn-exit { background: #dc3545; }

.btn-theme {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-theme:hover {
    background: rgba(128,128,128,0.15);
}

/* BOTÃO DE TEMA */
#themeToggle {
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

body.light-mode #themeToggle {
    background: var(--highlight-color);
    color: #fff;
}

/* ===============================
   MAIN
================================ */
main {
    padding-top: 90px;
    padding-bottom: 60px;
    max-width: 1320px;
    margin: auto;
}

/* ===============================
   RESPONSIVO
================================ */
@media (max-width: 1024px) {
    main { padding-top: 120px; }
    .header-controls { flex-wrap: wrap; }
}

@media (max-width: 768px) {
    .header-controls {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .header-controls .btn {
        font-size: 14px;
        padding: 10px 14px;
    }
    main {
        padding-top: 150px;
        padding-bottom: 80px;
    }
}
</style>
</head>

<body class="<?= $classeBody ?>">

<header class="header-controls">
    <div class="header-group">
        <button class="btn btn-theme" onclick="adjustFontSize(-1)">A-</button>
        <button class="btn btn-theme" onclick="adjustFontSize(1)">A+</button>
        <button class="btn btn-theme" onclick="resetFontSize()">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <div class="header-group">
        <?php if (basename($_SERVER['PHP_SELF']) === 'home.php'): ?>
            <button id="themeToggle" class="btn" onclick="toggleTheme()">
                <i class="fas <?= $temaAtual === 'light' ? 'fa-moon' : 'fa-sun' ?>"></i>
            </button>
        <?php endif; ?>

        <a href="../pages/home.php" class="btn btn-home">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="../pages/logout.php" class="btn btn-exit">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</header>

<script>
function toggleTheme() {
    // 1. Alterna a classe visualmente para feedback instantâneo
    document.body.classList.toggle('light-mode');

    // 2. Atualiza o ícone
    const isLight = document.body.classList.contains('light-mode');
    const icon = document.querySelector('#themeToggle i');

    if (icon) {
        icon.className = 'fas ' + (isLight ? 'fa-moon' : 'fa-sun');
    }

    // 3. Prepara dados para salvar
    const formData = new FormData();
    formData.append('tema', isLight ? 'light' : 'dark');

    // 4. Salva no banco e SÓ DEPOIS recarrega a página
    // Isso evita o reload antes da sessão ser atualizada (Race Condition)
    fetch('../actions/salvar_tema.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            console.log('Tema salvo:', data);
            // Pequeno delay opcional para garantir a propagação da sessão, mas o .then já ajuda muito
            setTimeout(() => location.reload(), 50); 
        })
        .catch(error => {
            console.error('Erro ao salvar tema:', error);
        });
}
</script>

<main>