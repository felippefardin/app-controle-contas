<?php
// pages/tutorial.php

// Inicia a sessão e inclui funções essenciais.
include_once '../includes/session_init.php';
require_once '../database.php'; 

// 1. Verifica se tem sessão global (login master feito)
if (!isset($_SESSION['usuario_logado']) && !isset($_SESSION['super_admin'])) {
    header("Location: ../pages/login.php");
    exit();
}

// 2. Se for usuário comum (não super admin) e ainda não escolheu o perfil (sem usuario_id), manda selecionar
if (isset($_SESSION['usuario_logado']) && !isset($_SESSION['usuario_id']) && !isset($_SESSION['super_admin'])) {
    header("Location: ../pages/selecionar_usuario.php");
    exit();
}

// --- LÓGICA DE RECUPERAÇÃO DE DADOS ---

// Valores padrão iniciais
$nome_usuario = 'Usuário';
$perfil = 'padrao';

if (isset($_SESSION['super_admin'])) {
    // Caso 1: É Super Admin
    $nome_usuario = $_SESSION['super_admin']['nome'] ?? 'Administrador Master';
    $perfil = 'admin_master';

} elseif (isset($_SESSION['usuario_id'])) {
    // Caso 2: É Usuário Tenant Logado e Selecionado
    
    // Tenta buscar dados frescos do banco do tenant
    try {
        $conn = getTenantConnection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT nome, nivel_acesso FROM usuarios WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['usuario_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($dados = $result->fetch_assoc()) {
                    $nome_usuario = $dados['nome'];
                    $perfil = $dados['nivel_acesso'];
                }
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        // Se der erro no banco, tenta usar dados da sessão se existirem (fallback)
        if (isset($_SESSION['usuario_nome'])) $nome_usuario = $_SESSION['usuario_nome'];
        if (isset($_SESSION['nivel_acesso'])) $perfil = $_SESSION['nivel_acesso'];
    }
}

// Formata o perfil para exibição amigável
$perfil_exibicao = ucfirst(str_replace('_', ' ', $perfil));

// Define se tem privilégios administrativos para mostrar/ocultar seções do tutorial
$is_admin = in_array($perfil, ['proprietario', 'admin', 'admin_master']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual do Usuário - App Controle de Contas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        .tutorial-wrapper {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .tutorial-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        .tutorial-header h1 {
            color: #00bfff;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .tutorial-header p {
            font-size: 1.1rem;
            color: #aaa;
        }
        
        /* Cards de Módulos */
        .module-section {
            background: #1e1e1e;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid #007bff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        .module-section:hover {
            transform: translateY(-2px);
        }
        
        /* Destaque para Admin */
        .module-section.admin-feature {
            border-left-color: #ffc107;
        }
        
        .module-title {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .module-content {
            margin-left: 10px;
        }
        
        /* Listas */
        .step-list {
            list-style: none;
            padding: 0;
        }
        .step-list li {
            margin-bottom: 15px;
            position: relative;
            padding-left: 25px;
        }
        .step-list li::before {
            content: "\f058"; /* Check icon */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #28a745;
            position: absolute;
            left: 0;
            top: 3px;
        }
        .step-list li strong {
            color: #8fd3fe;
        }
        
        /* Botões de Ação */
        .btn-goto {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 12px;
            font-size: 0.85rem;
            background-color: #333;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid #444;
            transition: all 0.3s;
        }
        .btn-goto:hover {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }

        /* Badge de perfil */
        .badge {
            background-color: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            vertical-align: middle;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-admin {
            background-color: #ffc107;
            color: #000;
        }
        
        .perm-note {
            font-size: 0.8rem;
            color: #888;
            margin-left: 8px;
            font-style: italic;
        }

        .back-btn {
            position: fixed;
            top: 90px;
            right: 30px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.6);
            z-index: 999;
            transition: background 0.3s;
        }
        .back-btn:hover { background-color: #218838; }

        @media (max-width: 768px) {
            .back-btn { display: none; }
            .tutorial-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i> Voltar para Home</a>

    <div class="tutorial-wrapper">
        
        <div class="tutorial-header">
            <h1><i class="fas fa-book-reader"></i> Manual do Sistema</h1>
            <p>Olá, <strong><?= htmlspecialchars($nome_usuario) ?></strong>. Bem-vindo(a) ao guia de uso.</p>
            <p style="margin-top: 10px;">Seu nível de acesso atual: 
                <span class="badge <?= $is_admin ? 'badge-admin' : '' ?>">
                    <?= htmlspecialchars($perfil_exibicao) ?>
                </span>
            </p>
        </div>

        <!-- MÓDULO 1 -->
        <div class="module-section admin-feature">
            <h2 class="module-title"><i class="fas fa-cogs"></i> 1. Configurações Iniciais</h2>
            <div class="module-content">
                <p>A base do seu controle financeiro.</p>
                <ul class="step-list">
                    <li>
                        <strong>Categorias:</strong> Organize suas receitas e despesas (ex: Alimentação, Transporte).
                        <br><a href="categorias.php" class="btn-goto">Configurar Categorias</a>
                    </li>
                    <li>
                        <strong>Contas Bancárias:</strong> Cadastre onde seu dinheiro está (Caixa Físico, Banco X).
                        <br><a href="banco_cadastro.php" class="btn-goto">Gerenciar Contas</a>
                    </li>
                    <li>
                        <strong>Parceiros:</strong> Cadastre Clientes e Fornecedores.
                        <br><a href="cadastrar_pessoa_fornecedor.php" class="btn-goto">Cadastrar Parceiros</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- MÓDULO 2 -->
        <div class="module-section">
            <h2 class="module-title"><i class="fas fa-shopping-cart"></i> 2. Vendas</h2>
            <div class="module-content">
                <p>Registre suas entradas operacionais.</p>
                <ul class="step-list">
                    <li>
                        <strong>PDV (Ponto de Venda):</strong> Lançamento rápido de vendas.
                        <ul>
                            <li>Venda à Vista: Entra direto no saldo da conta selecionada.</li>
                            <li>Venda a Prazo: Gera uma conta a receber futura.</li>
                        </ul>
                        <a href="vendas.php" class="btn-goto">Ir para Vendas</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- MÓDULO 3 -->
        <div class="module-section admin-feature">
            <h2 class="module-title"><i class="fas fa-wallet"></i> 3. Financeiro</h2>
            <div class="module-content">
                <p>Controle total do fluxo de caixa.</p>
                <ul class="step-list">
                    <li>
                        <strong>A Pagar & A Receber:</strong> Lance contas futuras. Lembre-se de dar <strong>Baixa</strong> quando o pagamento for efetivado.
                        <br>
                        <a href="contas_pagar.php" class="btn-goto">Contas a Pagar</a>
                        <a href="contas_receber.php" class="btn-goto" style="margin-left: 5px;">Contas a Receber</a>
                    </li>
                    <li>
                        <strong>Movimento de Caixa:</strong> Lançamentos diretos (sangrias, despesas miúdas) que afetam o saldo hoje.
                        <br><a href="lancamento_caixa.php" class="btn-goto">Lançar no Caixa</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- MÓDULO 4 -->
        <div class="module-section admin-feature">
            <h2 class="module-title"><i class="fas fa-cubes"></i> 4. Estoque e Compras</h2>
            <div class="module-content">
                <ul class="step-list">
                    <li>
                        <strong>Produtos:</strong> Defina preços e estoque mínimo.
                        <br><a href="controle_estoque.php" class="btn-goto">Ver Estoque</a>
                    </li>
                    <li>
                        <strong>Compras:</strong> Ao registrar uma compra de fornecedor, o estoque aumenta e o financeiro é atualizado.
                        <br><a href="compras.php" class="btn-goto">Nova Compra</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- MÓDULO 5 -->
        <div class="module-section">
            <h2 class="module-title"><i class="fas fa-chart-pie"></i> 5. Relatórios</h2>
            <div class="module-content">
                <ul class="step-list">
                    <li>
                        <strong>Análise Completa:</strong> DRE, Fluxo de Caixa Mensal e Curva ABC de produtos.
                        <br><a href="relatorios.php" class="btn-goto">Acessar Dashboards</a>
                    </li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin: 50px 0;">
            <a href="home.php" class="action-link" style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 1.1rem; font-weight: bold; box-shadow: 0 4px 10px rgba(40, 167, 69, 0.4);">
                <i class="fas fa-home"></i> Voltar para o Início
            </a>
        </div>
        
    </div>
</body>
</html>