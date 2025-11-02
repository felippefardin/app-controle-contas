<?php
// // Ficheiro: includes/session_init.php
// IMPORTANTE: NÃO PODE HAVER NENHUM ESPAÇO OU LINHA EM BRANCO ANTES DE "<?php"

// --- INÍCIO DA SESSÃO ---
// Garante que uma sessão seja iniciada se ainda não houver uma ativa.

if (session_status() === PHP_SESSION_NONE) {
    // --- INÍCIO DA CORREÇÃO ---
    // Define o cookie da sessão para ser válido em todo o site ('/')
    // Isso garante que a sessão seja compartilhada entre /pages/ e /actions/
    session_set_cookie_params([
        'lifetime' => 0, // 0 = até o navegador fechar
        'path' => '/',   // Caminho raiz
        'domain' => '',  // Domínio atual (deixe em branco)
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Recomendado
        'httponly' => true // Recomendado
    ]);
    // --- FIM DA CORREÇÃO ---

    session_start();
}

// Função para verificar se o usuário é proprietário
function verificar_acesso_proprietario() {
    if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado']['nivel_acesso'] !== 'proprietario') {
        session_write_close(); 
        header('Location: ../pages/vendas.php');
        exit;
    }
}


// --- GERAÇÃO DO TOKEN CSRF ---
// Gera um token para proteção contra ataques CSRF, se não existir.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>