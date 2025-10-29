<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega a conexão correta para o cliente
    $conn = getTenantConnection();
    if ($conn === null) {
        header("Location: ../pages/cadastrar_pessoa_fornecedor.php?error=db_connection");
        exit;
    }

    // Pega os dados do formulário e da sessão
    $id_registro = $_POST['id'];
    $id_usuario = $_SESSION['usuario_logado']['id'];
    
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $endereco = $_POST['endereco'];
    $contato = $_POST['contato'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];

    // 3. PREPARA E EXECUTA A ATUALIZAÇÃO NO BANCO
    // A cláusula `WHERE id = ? AND id_usuario = ?` garante que um usuário não pode editar o registro de outro
    $sql = "UPDATE pessoas_fornecedores SET nome = ?, cpf_cnpj = ?, endereco = ?, contato = ?, email = ?, tipo = ? WHERE id = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssssssii", $nome, $cpf_cnpj, $endereco, $contato, $email, $tipo, $id_registro, $id_usuario);
    
    if ($stmt->execute()) {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?sucesso_edicao=1');
    } else {
        header('Location: ../pages/cadastrar_pessoa_fornecedor.php?erro_edicao=1');
    }
    $stmt->close();
    exit;
} else {
    // Redireciona se não for POST
    header('Location: ../pages/cadastrar_pessoa_fornecedor.php');
    exit;
}
?>