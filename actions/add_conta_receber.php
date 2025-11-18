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
        header('Location: ../pages/contas_receber.php');
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
    // OBS: O input hidden no modal contas_receber.php se chama 'pessoa_id'
    $id_pessoa_fornecedor = !empty($_POST['pessoa_id']) ? (int)$_POST['pessoa_id'] : null;
    
    $numero = trim($_POST['numero'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Tratamento do valor (R$ 1.000,00 -> 1000.00)
    $valorStr = $_POST['valor'] ?? '0';
    $valor = str_replace('.', '', $valorStr);
    $valor = str_replace(',', '.', $valor);
    $valor = floatval($valor);

    $data_vencimento = $_POST['data_vencimento'] ?? '';
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;

    // Validação básica
    if ($valor <= 0 || empty($data_vencimento) || empty($id_categoria)) {
        $_SESSION['error_message'] = "Preencha: Valor, Vencimento e Categoria.";
        header('Location: ../pages/contas_receber.php');
        exit;
    }

    // 3. INSERE OS DADOS
    // Status padrão é 'pendente'
    $sql = "INSERT INTO contas_receber (
                id_pessoa_fornecedor, 
                numero,
                descricao,
                valor, 
                data_vencimento, 
                usuario_id, 
                id_categoria, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Tipos: i=int, s=string, s=string, d=double, s=string, i=int, i=int
        $stmt->bind_param("issdsii", 
            $id_pessoa_fornecedor, 
            $numero,
            $descricao, 
            $valor, 
            $data_vencimento, 
            $usuario_id, 
            $id_categoria
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Receita adicionada com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro SQL: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Erro ao preparar query: " . $conn->error;
    }

    header('Location: ../pages/contas_receber.php');
    exit;
}
?>