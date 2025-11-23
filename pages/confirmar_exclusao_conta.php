<?php
// pages/confirmar_exclusao_conta.php

// 1. GARANTE A SESSÃO E OUTRAS FUNÇÕES DE UTILIDADE
require_once '../includes/session_init.php'; 

// 2. INCLUI AS FUNÇÕES DE CONEXÃO
include('../database.php');

// 3. ESTABELECE A CONEXÃO CORRETA (MASTER)
$conn = getMasterConnection(); 

// 4. VERIFICA SE A CONEXÃO FOI ESTABELECIDA
if ($conn === null) {
    die("Erro ao conectar com o banco de dados. Tente novamente."); 
}

// Inclui o cabeçalho para manter o menu e estilos base
include('../includes/header.php'); 

$token = $_GET['token'] ?? '';
$mensagem_erro = '';
$token_valido = false;
$id_usuario = null;

if (!empty($token)) {
    // Prepara a query para buscar o token que ainda não expirou
    $stmt = $conn->prepare("SELECT id_usuario, expira_em FROM solicitacoes_exclusao WHERE token = ? AND expira_em > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $solicitacao = $result->fetch_assoc();
        $id_usuario = $solicitacao['id_usuario'];
        $token_valido = true;
    } else {
        // Verifica se o token existiu mas já expirou
        $stmt_check_expired = $conn->prepare("SELECT id FROM solicitacoes_exclusao WHERE token = ?");
        $stmt_check_expired->bind_param("s", $token);
        $stmt_check_expired->execute();
        if ($stmt_check_expired->get_result()->num_rows > 0) {
            $mensagem_erro = "Seu link de exclusão expirou. Por favor, solicite um novo a partir da sua página de perfil.";
        } else {
            $mensagem_erro = "O link de exclusão é inválido ou já foi utilizado.";
        }
        $stmt_check_expired->close();
    }
    $stmt->close();
} else {
    $mensagem_erro = "Nenhum token de confirmação foi fornecido. Acesso negado.";
}
?>

<!-- Não é necessário abrir HTML/BODY novamente pois o header.php já faz isso geralmente, 
     mas garantimos o estilo local para esta página específica -->

<style>
    /* Garante que o fundo seja o padrão do sistema */
    body {
        background-color: #121212;
        color: #eee;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Wrapper para centralizar o conteúdo descontando a altura do header */
    .exclusion-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 150px); /* Ajuste para descontar o header */
        padding: 20px;
    }

    /* O Cartão Central */
    .card-confirm {
        background-color: #1e1e1e;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        text-align: center;
        max-width: 550px;
        width: 100%;
        border: 1px solid #333;
        position: relative;
        animation: fadeIn 0.5s ease-in-out;
    }

    /* Títulos e Ícones */
    .card-confirm h1 {
        color: #dc3545; /* Vermelho perigo */
        margin-bottom: 20px;
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .card-confirm p {
        margin-bottom: 25px;
        line-height: 1.6;
        color: #ccc;
        font-size: 1rem;
    }

    .icon-big {
        font-size: 3.5rem;
        color: #dc3545;
        margin-bottom: 20px;
        display: block;
    }

    /* Caixa de Aviso Amarela */
    .aviso-backup {
        background-color: rgba(255, 193, 7, 0.1); /* Amarelo transparente */
        color: #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        font-size: 0.95rem;
        border: 1px solid #ffc107;
        text-align: left;
    }
    .aviso-backup strong {
        display: block;
        margin-bottom: 5px;
        font-size: 1.1rem;
    }

    /* Botões */
    .btn-area {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }

    .btn-action {
        padding: 12px 24px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-danger-confirm {
        background-color: #dc3545;
        color: white;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    .btn-danger-confirm:hover {
        background-color: #c82333;
        transform: translateY(-2px);
    }

    .btn-cancel {
        background-color: #2c3e50;
        color: white;
        border: 1px solid #444;
    }
    .btn-cancel:hover {
        background-color: #34495e;
        border-color: #555;
    }

    /* Estilo para Erro */
    .erro-container {
        border: 1px solid #dc3545;
        background-color: rgba(220, 53, 69, 0.1);
        padding: 20px;
        border-radius: 8px;
    }
    .erro-text {
        color: #ff6b6b;
        font-weight: bold;
        margin-top: 10px;
    }

    /* --- RESPONSIVIDADE (MOBILE) --- */
    @media (max-width: 600px) {
        .exclusion-wrapper {
            padding: 15px;
            min-height: calc(100vh - 120px);
        }

        .card-confirm {
            padding: 25px 20px;
        }

        .card-confirm h1 {
            font-size: 1.5rem;
            flex-direction: column;
        }

        .btn-area {
            flex-direction: column; /* Botões um embaixo do outro */
            gap: 10px;
        }

        .btn-action {
            width: 100%; /* Botão ocupa toda a largura */
            padding: 14px;
        }
        
        .aviso-backup {
            font-size: 0.9rem;
        }
    }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<!-- Conteúdo Principal -->
<div class="exclusion-wrapper">
    <div class="card-confirm">
        
        <?php if ($token_valido): ?>
            <i class="fa-solid fa-triangle-exclamation icon-big"></i>
            <h1>Confirmar Exclusão</h1>
            
            <div class="aviso-backup">
                <strong><i class="fas fa-save"></i> ATENÇÃO AO BACKUP</strong>
                Antes de prosseguir, certifique-se de ter exportado seus dados (Relatórios, Contas, etc.). 
                Esta ação apagará permanentemente suas contas a pagar, receber, clientes e histórico.
            </div>
            
            <p>Você tem certeza absoluta? <strong>Esta ação não pode ser desfeita.</strong></p>
            
            <form action="../actions/executar_exclusao.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="btn-area">
                    <button type="button" class="btn-action btn-cancel" onclick="window.location.href='../pages/perfil.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-action btn-danger-confirm">
                        <i class="fas fa-trash-alt"></i> Sim, Excluir Conta
                    </button>
                </div>
            </form>

        <?php else: ?>
            <div class="erro-container">
                <i class="fa-solid fa-circle-xmark icon-big" style="margin-bottom: 10px;"></i>
                <h1>Link Inválido</h1>
                <p class="erro-text"><?= htmlspecialchars($mensagem_erro) ?></p>
                <p style="font-size: 0.9rem; color: #aaa;">Redirecionando em alguns segundos...</p>
            </div>
            
            <script>
                setTimeout(function() { 
                    window.location.href = '../pages/perfil.php';
                }, 5000);
            </script>
            
            <div class="btn-area">
                <a href="../pages/perfil.php" class="btn-action btn-cancel">Voltar ao Perfil</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include('../includes/footer.php'); ?>