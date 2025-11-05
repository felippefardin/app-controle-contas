<?php

// pages/minha_assinatura.php

// 1. Inclua seu arquivo de configuração principal PRIMEIRO
// Este arquivo já carrega o autoload, o database.php e o SDK do Mercado Pago
require_once __DIR__ . '/../includes/config/config.php';

// 2. Inicie sua sessão (este arquivo já deve fazer o session_start())
require_once '../includes/session_init.php';
// O restante do seu código de login.php...
$email = $_POST['email'];
$senha = $_POST['senha'];

try {
    $pdo = getDbConnection(); //
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verificação de Senha
    if ($user && password_verify($senha, $user['senha'])) {
        
        // --- INÍCIO DA CORREÇÃO MASTER LOGIN ---
        // Permite o login do usuário 'contatotech' sem verificar a assinatura
        // Usei o email 'contatotech' conforme sua solicitação. 
        // Se o email for outro (ex: contatotech@dominio.com), ajuste abaixo.
        if ($user['email'] === 'contatotech') {
            
            // Define os dados da sessão (mantendo compatibilidade anterior)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_nivel'] = $user['nivel_acesso'];
            
            // --- CORREÇÃO DA SESSÃO PARA home.php ---
            // home.php espera um array 'usuario_logado'
            $_SESSION['usuario_logado'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'nome' => $user['nome'],
                'nivel_acesso' => $user['nivel_acesso']
            ];
            // --- FIM DA CORREÇÃO DA SESSÃO ---

            header("Location: ../pages/home.php");
            exit;
        }
        // --- FIM DA CORREÇÃO MASTER LOGIN ---


        // **** VERIFICAÇÃO DE ASSINATURA (Para outros usuários) ****
        $status = $user['status_assinatura'];

        if ($status == 'trial' || $status == 'active') {
            // LOGIN AUTORIZADO!
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_nivel'] = $user['nivel_acesso'];

            // --- CORREÇÃO DA SESSÃO PARA home.php ---
            // home.php espera um array 'usuario_logado'
            $_SESSION['usuario_logado'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'nome' => $user['nome'],
                'nivel_acesso' => $user['nivel_acesso']
            ];
            // --- FIM DA CORREÇÃO DA SESSÃO ---

            header("Location: ../pages/home.php");
            exit;
            
        } elseif ($status == 'pending') {
            // Usuário se cadastrou mas não completou o pagamento
            // Vamos mandá-lo de volta para a tela de trial
            $_SESSION['registration_email'] = $user['email']; // Recria a sessão de registro
            header("Location: ../pages/assinar_trial.php?msg=complete_seu_cadastro");
            exit;
            
        } elseif ($status == 'paused' || $status == 'cancelled') {
             // Assinatura expirou ou foi cancelada
            header("Location: ../pages/login.php?msg=assinatura_inativa");
            exit;
        }
        // *******************************************

    } else {
        // Erro de email ou senha
        header("Location: ../pages/login.php?msg=login_invalido");
        exit;
    }

} catch (PDOException $e) {
    header("Location: ../pages/login.php?msg=erro_db");
    exit;
}
?>