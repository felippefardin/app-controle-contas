<?php
require_once '../includes/session_init.php';
require_once '../database.php'; 

// --- Início da Correção ---

// 1. Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../pages/login.php');
    exit;
}

// 2. 🔒 ADICIONANDO VERIFICAÇÃO DE PERMISSÃO
// Somente admins (do tenant) ou o master admin podem adicionar usuários
verificar_acesso_admin();

// 3. Obtém a conexão correta com o banco de dados
$conn = getTenantConnection();
if ($conn === null) {
    // Redireciona com um erro específico de banco de dados
    header('Location: ../pages/add_usuario.php?erro=db_error');
    exit;
}

// --- Fim da Correção ---

// 4. Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';
    
    // Pega o tenant_id da sessão (garantido pelo login)
    $tenant_id = $_SESSION['tenant_id'] ?? null;
    $criador_id = $_SESSION['usuario_id'] ?? null; // ID do admin que está criando

    // 5. Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || $tenant_id === null) {
        header('Location: ../pages/add_usuario.php?erro=campos_vazios');
        exit;
    }

    if ($senha !== $senha_confirmar) {
        header('Location: ../pages/add_usuario.php?erro=senha');
        exit;
    }
    
    // Limpa CPF (o schema.sql mostra que você tem uma coluna 'documento' e 'cpf')
    // Vamos usar a coluna 'cpf' conforme o formulário
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);


    // 6. Verifica se e-mail ou CPF já existem NESTE TENANT
    // Seu schema.sql tem uma chave única em (email, tenant_id),
    // mas a consulta em add_usuario.php estava verificando SÓ o email/cpf.
    // Isso impediria o mesmo email de existir em tenants diferentes.
    
    // Consulta corrigida para checar email E tenant_id
    $stmt_check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND tenant_id = ?");
    $stmt_check_email->bind_param("ss", $email, $tenant_id);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();
    if ($stmt_check_email->num_rows > 0) {
        $stmt_check_email->close();
        header('Location: ../pages/add_usuario.php?erro=duplicado_email');
        exit;
    }
    $stmt_check_email->close();

    // Consulta corrigida para checar cpf E tenant_id (se CPF for obrigatório)
    if (!empty($cpf_limpo)) {
        $stmt_check_cpf = $conn->prepare("SELECT id FROM usuarios WHERE cpf = ? AND tenant_id = ?");
        $stmt_check_cpf->bind_param("ss", $cpf_limpo, $tenant_id);
        $stmt_check_cpf->execute();
        $stmt_check_cpf->store_result();
        if ($stmt_check_cpf->num_rows > 0) {
            $stmt_check_cpf->close();
            header('Location: ../pages/add_usuario.php?erro=duplicado_cpf');
            exit;
        }
        $stmt_check_cpf->close();
    }


    // 7. Insere o novo usuário no banco de dados
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $perfil = 'padrao'; // Perfil padrão para novos usuários (CORRETO)
    $tipo_pessoa_default = !empty($cpf_limpo) ? 'pf' : 'pj'; // Assume 'pf' se preencheu cpf

    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nome, email, cpf, telefone, senha, perfil, nivel_acesso, tenant_id, criado_por_usuario_id, tipo_pessoa, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')"
    );
    $stmt->bind_param(
        "ssssssssis", 
        $nome, 
        $email, 
        $cpf_limpo, 
        $telefone, 
        $senha_hash, 
        $perfil,     // Coluna 'perfil' (enum)
        $perfil,     // Coluna 'nivel_acesso' (varchar)
        $tenant_id, 
        $criador_id,
        $tipo_pessoa_default
    );

    if ($stmt->execute()) {
        header('Location: ../pages/usuarios.php?sucesso=1');
    } else {
        error_log("Erro ao inserir usuário no tenant $tenant_id: " . $stmt->error);
        header('Location: ../pages/add_usuario.php?erro=inesperado');
    }

    $stmt->close();
    $conn->close();
    exit;
} else {
    // Redireciona se não for POST
    header('Location: ../pages/add_usuario.php');
    exit;
}
?>