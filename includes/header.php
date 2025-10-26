<?php if (isset($_SESSION['proprietario_id_original'])): ?>
    <div class="admin-view-banner">
        Você está visualizando como <strong><?= htmlspecialchars($_SESSION['usuario_principal']['nome']); ?></strong>.
        <a href="../actions/retornar_admin.php">Voltar para o Acesso Proprietário</a>
    </div>
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
            flex: 1; /* Faz o conteúdo principal crescer e ocupar o espaço */
        }
        .header-controls {
            background-color: #1f1f1f;
            padding: 10px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 1001;
        }
        .header-controls .btn {
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
        .header-controls .btn-home { background-color: #28a745; }
        .header-controls .btn-exit { background-color: #dc3545; }
        .header-controls .btn:hover { opacity: 0.8; }

    @media (max-width: 768px) {
      .font-controls {
        flex-direction: column;
        align-items: center;
      }
      .font-controls button,
      .font-controls a {
        width: 90%;
        font-size: 15px;
        padding: 10px;
      }
    }

    @media (max-width: 480px) {
      body { padding: 15px; }
      .font-controls button,
      .font-controls a {
        width: 95%;
        font-size: 14px;
        padding: 8px;
      }
    }
  </style>
</head>

<body>
  <header class="header-controls">
    <button type="button" class="btn btn-header" onclick="adjustFontSize(-1)" title="Diminuir fonte">A-</button>
    <button type="button" class="btn btn-header" onclick="adjustFontSize(1)" title="Aumentar fonte">A+</button>
    <button type="button" class="btn btn-header" onclick="resetFontSize()" title="Restaurar fonte">Resetar</button>
    <a href="../pages/home.php" class="btn btn-home btn-header" title="Página Inicial"><i class="fas fa-home"></i> Home</a>
    <a href="../pages/logout.php" class="btn btn-exit btn-header" title="Sair do sistema"><i class="fas fa-sign-out-alt"></i> Sair</a>
</header>

  <main>