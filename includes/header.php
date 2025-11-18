<?php
// Garante que a sessão seja iniciada ANTES de tentar ler as variáveis $_SESSION.
require_once __DIR__ . '/session_init.php';
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

<?php if (isset($_SESSION['super_admin_original'])): ?>
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
   /* Estilos para o corpo da página e o header, garantindo consistência */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-color: #121212; /* Fundo escuro padrão */
        }
        main {
            flex: 1;
            padding-top: 70px; /* Espaço para o header fixo */
            padding-bottom: 50px; /* Espaço para o footer fixo */
        }
        .header-controls {
            background-color: #1f1f1f;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1001;
            box-sizing: border-box;
        }

        .header-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-controls .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #007bff;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: opacity 0.3s ease;
        }
        
        .header-controls .btn i {
            margin-right: 6px;
        }

        .header-controls .btn-home { background-color: #28a745; }
        .header-controls .btn-exit { background-color: #dc3545; }
        .header-controls .btn:hover { opacity: 0.8; }

    @media (max-width: 768px) {
      .header-controls {
          justify-content: center;
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