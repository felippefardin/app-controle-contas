<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST (ENVIO DO FORMULÁRIO)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega a conexão correta para o cliente (tenant)
    $conn = getTenantConnection();
    if ($conn === null) {
        header("Location: ../pages/cadastrar_pessoa_fornecedor.php?error=db_connection");
        exit;
    }

    // Pega o ID do usuário da sessão correta
    $id_usuario = $_SESSION['usuario_logado']['id'];
    
    // Pega os dados do formulário
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $endereco = $_POST['endereco'];
    $contato = $_POST['contato'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];

    // 3. PREPARA E EXECUTA A INSERÇÃO NO BANCO DE DADOS
    $stmt = $conn->prepare("INSERT INTO pessoas_fornecedores (id_usuario, nome, cpf_cnpj, endereco, contato, email, tipo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $id_usuario, $nome, $cpf_cnpj, $endereco, $contato, $email, $tipo);
    
    if ($stmt->execute()) {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?sucesso=1');
    } else {
        // Corrigido o erro de digitação no caminho
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?erro=1');
    }
    $stmt->close();
    exit;
} else {
    // Redireciona se não for POST
    header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
    exit;
}
?>