<?php
// actions/register_user.php
session_start();
require_once '../database.php'; // Ajuste o caminho se necessário

// (Mantenha quaisquer validações que você já tenha aqui)
// ...

$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
$nivel_acesso = 'usuario'; // Ou 'padrao', seu padrão
$status = 'ativo'; // Define o usuário como ativo imediatamente

// Pegar os campos adicionais do formulário de registro.php
$tipo_pessoa = $_POST['tipo_pessoa'];
$documento = $_POST['documento'];
$telefone = $_POST['telefone'];

try {
    $pdo = getDbConnection();
    
    // (Mantenha sua verificação de email existente aqui, se houver)
    // ...

    // Atualize o INSERT para incluir todos os campos do formulário e o status 'ativo'
    // Remova 'status_assinatura'
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nome, email, senha, nivel_acesso, status, tipo_pessoa, documento, telefone) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nome, $email, $senha, $nivel_acesso, $status, $tipo_pessoa, $documento, $telefone]);

    // SUCESSO!
    // Crie uma mensagem de sucesso na sessão para exibir no login
    $_SESSION['registro_sucesso'] = "Cadastro realizado com sucesso! Você já pode fazer o login.";

    // Redireciona para a página de login
    header("Location: ../pages/login.php?msg=cadastro_sucesso");
    exit;

} catch (PDOException $e) {
    // Tratar erro (ex: email duplicado)
    if ($e->errorInfo[1] == 1062) {
        // Você pode usar a sessão de erro para exibir na página de registro
        $_SESSION['erro_registro'] = "Este e-mail já está cadastrado.";
        header("Location: ../pages/registro.php?msg=email_duplicado");
    } else {
        $_SESSION['erro_registro'] = "Erro ao conectar com o banco de dados.";
        header("Location: ../pages/registro.php?msg=erro_db");
    }
    exit;
}
?>