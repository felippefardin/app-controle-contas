<?php
require_once __DIR__ . '/session_init.php';

// ===============================
// TEMA GLOBAL (PERSISTENTE)
// ===============================
$temaAtual = $_SESSION['tema_preferencia'] ?? 'dark';
$classeBody = ($temaAtual === 'light') ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>App Controle de Contas</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/theme.css">
</head>

<body class="<?= $classeBody ?>">

<header class="header-controls">
   <div class="header-group">
    <button class="btn btn-font-access" onclick="adjustFontSize(-1)">A-</button>
    <button class="btn btn-font-access" onclick="adjustFontSize(1)">A+</button>
    <button class="btn btn-font-access" onclick="resetFontSize()">
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
// ===============================
// TOGGLE DE TEMA (SEM BUG)
// ===============================
function toggleTheme() {
    const body = document.body;
    const icon = document.querySelector('#themeToggle i');

    body.classList.toggle('light-mode');
    body.classList.toggle('dark-mode');

    const isLight = body.classList.contains('light-mode');

    if (icon) {
        icon.className = 'fas ' + (isLight ? 'fa-moon' : 'fa-sun');
    }

    const formData = new FormData();
    formData.append('tema', isLight ? 'light' : 'dark');

    fetch('../actions/salvar_tema.php', {
        method: 'POST',
        body: formData
    });
}

// ===============================
// ACESSIBILIDADE – FONTE
// ===============================
function adjustFontSize(amount) {
    const els = [document.documentElement, document.body];
    els.forEach(el => {
        let size = parseFloat(getComputedStyle(el).fontSize) || 16;
        el.style.fontSize = (size + amount) + 'px';
    });
}

function resetFontSize() {
    document.documentElement.style.fontSize = '';
    document.body.style.fontSize = '';
}
</script>

<main>
