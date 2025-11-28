<?php
// actions/solicitar_suporte_inicial.php
require_once '../includes/session_init.php';
require_once '../database.php';
require_once '../includes/utils.php';

// 1. Verifica login
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Dados do Usuário da Sessão
    $usuario_id = $_SESSION['usuario_id'];
    $email_sessao = $_SESSION['email']; 
    
    // Conexão para pegar dados atualizados do usuário (Nome e Whats)
    $conn = getTenantConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT nome, email, telefone FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->bind_result($nome, $email, $whatsapp);
        $stmt->fetch();
        $stmt->close();
    } else {
        // Fallback se falhar conexão tenant
        $nome = $_SESSION['usuario_nome'] ?? 'Cliente';
        $email = $email_sessao;
        $whatsapp = '';
    }

    // 2. Gravar no Banco Master e Validar Plano
    $connMaster = getMasterConnection();
    
    if ($connMaster && !$connMaster->connect_error) {
        // CORREÇÃO AQUI: Usamos 'plano_atual' para alinhar com minha_assinatura.php
        $sqlT = "SELECT id, plano_atual FROM tenants WHERE admin_email = ? LIMIT 1";
        $stmtT = $connMaster->prepare($sqlT);
        $stmtT->bind_param("s", $email_sessao);
        $stmtT->execute();
        $stmtT->bind_result($id_tenant_master, $plano_db);
        $stmtT->fetch();
        $stmtT->close();

        // Sanitiza o valor do plano
        $plano_real = trim(strtolower($plano_db ?? ''));

        // Verificação de Segurança do Plano
        if ($plano_real !== 'essencial') {
            set_flash_message('danger', "Funcionalidade exclusiva para o plano Essencial. (Seu plano: " . ucfirst($plano_real) . ")");
            header("Location: ../pages/perfil.php");
            exit;
        }

        // Verifica se JÁ existe solicitação pendente para evitar spam
        $checkSql = "SELECT id FROM solicitacoes_suporte_inicial WHERE tenant_id = ? LIMIT 1";
        $checkStmt = $connMaster->prepare($checkSql);
        $checkStmt->bind_param("i", $id_tenant_master);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            set_flash_message('warning', 'Você já possui uma solicitação de suporte inicial em andamento.');
            $checkStmt->close();
            header("Location: ../pages/perfil.php");
            exit;
        }
        $checkStmt->close();

        // Insere na tabela de solicitações
        $stmtInsert = $connMaster->prepare("INSERT INTO solicitacoes_suporte_inicial (tenant_id, nome_usuario, email_usuario, whatsapp_usuario) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("isss", $id_tenant_master, $nome, $email, $whatsapp);
        
        if ($stmtInsert->execute()) {
            
            // 3. Envio de E-mail (Opcional - Notificação interna)
            $destinatario = "suporte@seudominio.com.br"; // SEU EMAIL DE SUPORTE
            $assunto = "Onboarding - Plano Essencial";
            $msg = "Novo cliente Essencial solicitou suporte inicial.\n\n";
            $msg .= "Cliente: $nome\nEmail: $email\nWhats: $whatsapp\n";
            
            // Envio simples (pode ser substituído pelo PHPMailer se preferir)
            // mail($destinatario, $assunto, $msg); 

            // 4. Sucesso
            set_flash_message('success', 'Solicitação recebida! Aguarde nosso contato em breve.');
        } else {
            set_flash_message('danger', 'Erro ao registrar solicitação. Tente novamente.');
        }
        
        $connMaster->close();
    } else {
        set_flash_message('danger', 'Erro de conexão com o servidor.');
    }
    
    header("Location: ../pages/perfil.php");
    exit;
}
?>