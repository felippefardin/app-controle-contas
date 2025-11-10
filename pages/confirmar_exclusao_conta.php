<?php
// pages/confirmar_exclusao_conta.php

// 1. GARANTE A SESSÃO E OUTRAS FUNÇÕES DE UTILIDADE
require_once '../includes/session_init.php'; 

// 2. INCLUI AS FUNÇÕES DE CONEXÃO (como getMasterConnection)
include('../database.php');

// 3. ESTABELECE A CONEXÃO CORRETA
// A tabela 'solicitacoes_exclusao' está no banco de dados master,
// portanto, precisamos da conexão master.
$conn = getMasterConnection(); 

// 4. VERIFICA SE A CONEXÃO FOI ESTABELECIDA
if ($conn === null) {
    // Exibe uma mensagem de erro fatal se não conseguir conectar ao DB Master.
    die("Erro ao conectar com o banco de dados. Tente novamente."); 
}


include('../includes/header.php'); // Inclui o cabeçalho para uma aparência consistente

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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão de Conta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #222;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        p {
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .btn {
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            margin: 5px;
        }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        .erro {
            background-color: #cc4444;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        /* Novo estilo para a caixa de aviso de backup */
        .aviso-backup {
            background-color: #ffc107;
            color: #333;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-weight: bold;
            border: 2px solid #e0a800;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($token_valido): ?>
            <h1><i class="fa-solid fa-triangle-exclamation"></i> Confirmar Exclusão</h1>
            
            <div class="aviso-backup">
                ⚠️ **AVISO IMPORTANTE:** Antes de prosseguir, certifique-se de ter feito o **backup de todos os seus dados** (Contas a Pagar, Receber, Estoque, etc.). Você pode exportá-los em formatos **CSV, Excel ou PDF** através da página de Relatórios.
            </div>
            
            <p>Você tem <strong>certeza absoluta</strong> que deseja excluir sua conta? Todos os seus dados, incluindo contas e usuários associados, serão permanentemente removidos. <strong>Esta ação não pode ser desfeita.</strong></p>
            
            <form action="../actions/executar_exclusao.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <button type="submit" class="btn btn-danger">Sim, Excluir Minha Conta Permanentemente</button>
                <button type="button" class="btn btn-secondary" onclick="window.close();">Não, Cancelar</button>
            </form>
        <?php else: ?>
            <h1><i class="fa-solid fa-circle-xmark"></i> Erro na Operação</h1>
            <p class="erro"><?= htmlspecialchars($mensagem_erro) ?></p>
            <p>Esta janela será fechada em alguns segundos.</p>
            <script>
                setTimeout(function() { 
                    // Tenta fechar a janela, mas pode não funcionar em todos os navegadores
                    // por motivos de segurança. Redirecionar é uma alternativa.
                    window.open('', '_self', ''); // Necessário para alguns navegadores
                    window.close(); 
                }, 6000); // Aumentado para 6 segundos para dar tempo de ler
            </script>
        <?php endif; ?>
    </div>
</body>
</html>