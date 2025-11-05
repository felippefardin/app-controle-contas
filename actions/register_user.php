<?php
// actions/register_user.php
session_start();
require_once '../database.php'; // Ajuste o caminho

// ... (seu código de validação de nome, email, senha) ...

$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
$nivel_acesso = 'usuario'; // Ou seu padrão

try {
    $pdo = getDbConnection();
    
    // ... (Verifique se o email já existe) ...

    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nome, email, senha, nivel_acesso, status_assinatura) 
         VALUES (?, ?, ?, ?, 'pending')"
    );
    $stmt->execute([$nome, $email, $senha, $nivel_acesso]);

    // SUCESSO! Agora, não faça login.
    // Salve o email na sessão para a próxima etapa
    $_SESSION['registration_email'] = $email;

    // Redireciona para a página de pagamento/trial
    header("Location: ../pages/assinar_trial.php");
    exit;

} catch (PDOException $e) {
    // ... (seu tratamento de erro) ...
    header("Location: ../pages/register_.php?msg=erro_db");
    exit;
}
?>