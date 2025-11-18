<?php
require_once '../includes/session_init.php';
require_once '../database.php';

// 1. VERIFICA O LOGIN
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: ../pages/login.php?error=not_logged_in');
    exit;
}

// 2. VERIFICA SE O MÉTODO É POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getTenantConnection();
    if ($conn === null) {
        $_SESSION['error_message'] = "Falha na conexão com o banco de dados.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    // Pega o ID do usuário da sessão
    $usuario_id = $_SESSION['usuario_id'] ?? null;

    if (!$usuario_id) {
        $_SESSION['error_message'] = "Sessão expirada. Faça login novamente.";
        header('Location: ../pages/login.php');
        exit;
    }

    // Pega dados do formulário
    $fornecedor_nome = trim($_POST['fornecedor_nome'] ?? '');
    $id_pessoa_fornecedor = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
    $numero = trim($_POST['numero'] ?? '');
    
    // Tratamento do valor
    $valorStr = $_POST['valor'] ?? '0';
    $valor = str_replace('.', '', $valorStr);
    $valor = str_replace(',', '.', $valor);
    $valor = floatval($valor);

    $data_vencimento = $_POST['data_vencimento'] ?? '';
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    $enviar_email = isset($_POST['enviar_email']) ? 'S' : 'N';
    $descricao = trim($_POST['descricao'] ?? '');

    // Validação
    if (empty($fornecedor_nome) || empty($numero) || $valor <= 0 || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = "Preencha: Fornecedor, Número, Valor, Vencimento e Categoria.";
        header('Location: ../pages/contas_pagar.php');
        exit;
    }

    // 3. INSERE OS DADOS
    $sql = "INSERT INTO contas_pagar (
                fornecedor, 
                id_pessoa_fornecedor, 
                numero, 
                valor, 
                data_vencimento, 
                usuario_id, 
                enviar_email, 
                id_categoria, 
                descricao,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sisdsisis", 
            $fornecedor_nome, 
            $id_pessoa_fornecedor, 
            $numero, 
            $valor, 
            $data_vencimento, 
            $usuario_id, 
            $enviar_email, 
            $id_categoria, 
            $descricao
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Conta adicionada com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro SQL: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Erro ao preparar query: " . $conn->error;
    }

    header('Location: ../pages/contas_pagar.php');
    exit;
}
?>