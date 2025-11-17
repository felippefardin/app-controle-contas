<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST (ENVIO DO FORMULÁRIO)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn = getTenantConnection();
    if ($conn === null) {
        $_SESSION['mensagem_erro'] = "Falha ao conectar ao banco de dados.";
        header("Location: ../pages/cadastrar_pessoa_fornecedor.php");
        exit;
    }

    $id_usuario = $_SESSION['usuario_id']; // ID correto do usuário logado

    // Dados do formulário
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $endereco = $_POST['endereco'];
    $contato = $_POST['contato'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];

    // Insere no banco
    $stmt = $conn->prepare(
        "INSERT INTO pessoas_fornecedores 
        (id_usuario, nome, cpf_cnpj, endereco, contato, email, tipo) 
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssss", $id_usuario, $nome, $cpf_cnpj, $endereco, $contato, $email, $tipo);

    if ($stmt->execute()) {
        $_SESSION['mensagem_sucesso'] = "Cadastro realizado com sucesso!";
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao cadastrar: " . $conn->error;
    }

    $stmt->close();
    header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
    exit;

} else {
    header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
    exit;
}
?>
