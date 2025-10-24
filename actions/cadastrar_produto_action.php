<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// Verificação de segurança: garante que o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../pages/login.php');
    exit;
}

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Coleta os dados do formulário
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'] ?? ''; // Campo opcional
    
    // CORREÇÃO: Usando 'quantidade_estoque' que é o nome correto do campo no formulário
    $quantidade_estoque = $_POST['quantidade_estoque'];
    
    // Trata os valores de preço para salvar corretamente no banco
    $preco_compra = !empty($_POST['preco_compra']) ? str_replace(',', '.', $_POST['preco_compra']) : 0.00;
    $preco_venda = !empty($_POST['preco_venda']) ? str_replace(',', '.', $_POST['preco_venda']) : 0.00;

    // Novos campos fiscais (opcionais, podem ser nulos)
    $ncm = $_POST['ncm'] ?? null;
    $cfop = $_POST['cfop'] ?? null;
    
    $id_usuario = $_SESSION['usuario']['id'];

    // 2. Prepara a instrução SQL para inserir os dados
    // A query foi atualizada para incluir os novos campos e usar os nomes corretos
    $sql = "INSERT INTO produtos (nome, descricao, quantidade_estoque, preco_compra, preco_venda, ncm, cfop, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        // Trata erro na preparação da query
        die('Erro ao preparar a query: ' . $conn->error);
    }

    // 3. Associa os parâmetros (bind) e executa
    // Os tipos de dados 'ssiddssi' correspondem aos campos da query
    $stmt->bind_param(
        "ssiddssi", 
        $nome, 
        $descricao, 
        $quantidade_estoque, 
        $preco_compra, 
        $preco_venda, 
        $ncm, 
        $cfop, 
        $id_usuario
    );

    if ($stmt->execute()) {
        // Se o cadastro for bem-sucedido, redireciona de volta para a página de estoque
        header('Location: ../pages/controle_estoque.php?success=1');
    } else {
        // Se houver um erro, exibe a mensagem
        echo "Erro ao cadastrar produto: " . $stmt->error;
    }

    // 4. Fecha a declaração
    $stmt->close();
} else {
    // Se o script for acessado sem enviar o formulário, redireciona
    header('Location: ../pages/controle_estoque.php');
}

// Fecha a conexão com o banco
$conn->close();
?>