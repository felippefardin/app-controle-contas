<?php
// pages/tutorial.php

// Inicia a sessão e inclui funções essenciais.
include_once '../includes/session_init.php';
require_once '../database.php'; 

// 1. Verifica se tem sessão global
if (!isset($_SESSION['usuario_logado']) && !isset($_SESSION['super_admin'])) {
    header("Location: ../pages/login.php");
    exit();
}

// 2. Se for usuário comum e não escolheu perfil
if (isset($_SESSION['usuario_logado']) && !isset($_SESSION['usuario_id']) && !isset($_SESSION['super_admin'])) {
    header("Location: ../pages/selecionar_usuario.php");
    exit();
}

// --- LÓGICA DE RECUPERAÇÃO DE DADOS ---
$nome_usuario = 'Usuário';
$perfil = 'padrao';

if (isset($_SESSION['super_admin'])) {
    $nome_usuario = $_SESSION['super_admin']['nome'] ?? 'Administrador Master';
    $perfil = 'admin_master';
} elseif (isset($_SESSION['usuario_id'])) {
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
        if (isset($_SESSION['usuario_nome'])) $nome_usuario = $_SESSION['usuario_nome'];
        if (isset($_SESSION['nivel_acesso'])) $perfil = $_SESSION['nivel_acesso'];
    }
}

$perfil_exibicao = ucfirst(str_replace('_', ' ', $perfil));
// Verifica permissões para exibir configurações sensíveis
$is_admin = in_array($perfil, ['proprietario', 'admin', 'admin_master']);

// Inclui o Header padrão do sistema (que já tem o menu)
include_once '../includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        /* Reset básico para garantir que o tutorial não quebre o layout principal */
        .tutorial-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .intro-header {
            background: linear-gradient(135deg, #1e1e1e 0%, #2a2a2a 100%);
            padding: 30px;
            border-radius: 12px;
            border-left: 6px solid #28a745;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .intro-header h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            color: #fff;
        }

        .badge-role {
            background-color: #007bff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* Timeline / Steps Design */
        .step-section {
            position: relative;
            padding-left: 40px;
            margin-bottom: 50px;
        }

        .step-section::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #444;
        }

        .step-section:last-child::before {
            bottom: auto;
            height: 100%;
        }

        .step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 30px;
            height: 30px;
            background: #007bff;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: white;
            z-index: 2;
            box-shadow: 0 0 0 5px #121212;
        }

        .step-content h2 {
            margin-top: 0;
            color: #8fd3fe;
            font-size: 1.6rem;
            margin-bottom: 20px;
        }

        /* Cards de Funcionalidade */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .feature-card {
            background: #1f1f1f;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s, border-color 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            border-color: #007bff;
        }

        .feature-card h3 {
            color: #fff;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-card p {
            color: #aaa;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .instruction-list {
            margin: 15px 0;
            padding-left: 20px;
            color: #ccc;
        }
        .instruction-list li {
            margin-bottom: 8px;
        }

        .btn-action {
            display: inline-block;
            width: 100%;
            text-align: center;
            padding: 10px 0;
            background: #333;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-action:hover {
            background: #007bff;
        }

        /* Dicas */
        .tip-box {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #ffdd57;
        }
        .tip-box i { margin-right: 5px; }

        /* Responsividade */
        @media (max-width: 768px) {
            .step-section { padding-left: 0; }
            .step-section::before { display: none; }
            .step-number { display: inline-block; position: relative; margin-right: 10px; left: auto; top: auto; box-shadow: none; }
            .intro-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="tutorial-container">

    <div class="intro-header">
        <h1><i class="fas fa-graduation-cap"></i> Guia do Sistema</h1>
        <p>Olá, <strong><?= htmlspecialchars($nome_usuario) ?></strong>!</p>
        <p>Preparamos este tutorial para guiar você desde o cadastro básico até a análise dos relatórios. Siga os passos na ordem para aproveitar todo o potencial do sistema.</p>
        <div style="margin-top: 15px;">
            <span class="badge-role"><?= htmlspecialchars($perfil_exibicao) ?></span>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <div class="step-section">
        <div class="step-number">1</div>
        <div class="step-content">
            <h2>Configurações Iniciais (Essencial)</h2>
            <p>Antes de começar a vender, configure a estrutura da sua empresa para que os relatórios funcionem corretamente.</p>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-tags" style="color: #e83e8c;"></i> 1. Categorias</h3>
                    <p>Organize suas finanças definindo de onde vem (Receitas) e para onde vai (Despesas) o dinheiro.</p>
                    <ul class="instruction-list">
                        <li>Crie categorias principais (ex: Custos Fixos).</li>
                        <li>Crie subcategorias (ex: Aluguel, Água, Luz).</li>
                    </ul>
                    <div class="tip-box"><small><i class="fas fa-info-circle"></i> Sem categorias, o DRE não saberá calcular seu lucro real.</small></div>
                    <a href="categorias.php" class="btn-action">Configurar Categorias</a>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-university" style="color: #28a745;"></i> 2. Contas & Bancos</h3>
                    <p>Cadastre os locais onde seu dinheiro está guardado.</p>
                    <ul class="instruction-list">
                        <li><strong>Caixa Físico:</strong> Para dinheiro em espécie na gaveta.</li>
                        <li><strong>Bancos:</strong> Para contas correntes e poupança.</li>
                    </ul>
                    <a href="banco_cadastro.php" class="btn-action">Cadastrar Bancos</a>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-users" style="color: #17a2b8;"></i> 3. Clientes & Fornecedores</h3>
                    <p>Mantenha o cadastro de quem compra e de quem vende para você.</p>
                    <ul class="instruction-list">
                        <li>Essencial para contas a pagar/receber nominais.</li>
                        <li>Necessário para emissão de notas fiscais (NFC-e).</li>
                    </ul>
                    <a href="cadastrar_pessoa_fornecedor.php" class="btn-action">Gerenciar Cadastros</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="step-section">
        <div class="step-number"><?= $is_admin ? '2' : '1' ?></div>
        <div class="step-content">
            <h2>Gestão de Produtos e Estoque</h2>
            <p>Para realizar vendas, seu catálogo precisa estar alimentado.</p>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-box-open" style="color: #ffc107;"></i> Produtos</h3>
                    <p>Cadastre seus itens de venda individualmente.</p>
                    <ul class="instruction-list">
                        <li>Defina preço de custo (para cálculo de lucro).</li>
                        <li>Defina preço de venda.</li>
                        <li>Configure o estoque mínimo para receber alertas.</li>
                    </ul>
                    <a href="controle_estoque.php" class="btn-action">Ver Estoque</a>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-truck-loading" style="color: #fd7e14;"></i> Compras (Entradas)</h3>
                    <p>A melhor forma de alimentar o estoque.</p>
                    <ul class="instruction-list">
                        <li>Ao registrar uma compra, o estoque aumenta automaticamente.</li>
                        <li>Gera automaticamente uma <strong>Conta a Pagar</strong> no financeiro.</li>
                        <li>Suporta importação de XML de nota fiscal.</li>
                    </ul>
                    <a href="compras.php" class="btn-action">Nova Compra</a>
                </div>
            </div>
        </div>
    </div>

    <div class="step-section">
        <div class="step-number"><?= $is_admin ? '3' : '2' ?></div>
        <div class="step-content">
            <h2>Rotina Diária (Operação)</h2>
            <p>Os processos que você ou sua equipe farão todos os dias.</p>

            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-cash-register" style="color: #007bff;"></i> Vendas (PDV)</h3>
                    <p>A tela principal para registrar saídas.</p>
                    <ul class="instruction-list">
                        <li><strong>À Vista:</strong> O valor entra direto no saldo da conta/caixa.</li>
                        <li><strong>A Prazo:</strong> Gera uma previsão no "Contas a Receber".</li>
                        <li>Baixa o estoque do produto automaticamente.</li>
                    </ul>
                    <a href="vendas.php" class="btn-action">Ir para o PDV</a>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-calendar-alt" style="color: #6f42c1;"></i> Financeiro</h3>
                    <p>Gestão de contas futuras e boletos.</p>
                    <div class="tip-box">
                        <i class="fas fa-exclamation-circle"></i> <strong>Atenção:</strong> O saldo da empresa só é atualizado quando você clica no botão <span style="color:#fff; background:green; padding:2px 5px; border-radius:3px; font-size:0.7rem;">BAIXAR</span> (ícone de check) na lista de contas.
                    </div>
                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <a href="contas_pagar.php" class="btn-action" style="background:#dc3545;">A Pagar</a>
                        <a href="contas_receber.php" class="btn-action" style="background:#28a745;">A Receber</a>
                    </div>
                </div>

                <div class="feature-card">
                    <h3><i class="fas fa-hand-holding-usd" style="color: #20c997;"></i> Caixa Diário</h3>
                    <p>Movimentações rápidas e sem vínculo fiscal.</p>
                    <ul class="instruction-list">
                        <li><strong>Sangria:</strong> Retirada de valores do caixa para o cofre/banco.</li>
                        <li><strong>Suprimento:</strong> Adição de troco no início do dia.</li>
                        <li>Despesas rápidas (ex: lanche, material de limpeza).</li>
                    </ul>
                    <a href="lancamento_caixa.php" class="btn-action">Lançar no Caixa</a>
                </div>
            </div>
        </div>
    </div>

    <div class="step-section">
        <div class="step-number"><?= $is_admin ? '4' : '3' ?></div>
        <div class="step-content">
            <h2>Relatórios e Fechamento</h2>
            <p>Analise os dados para tomar decisões estratégicas.</p>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><i class="fas fa-chart-line" style="color: #e83e8c;"></i> Painel de Relatórios</h3>
                    <p>Visualize a saúde do negócio:</p>
                    <ul class="instruction-list">
                        <li><strong>DRE Gerencial:</strong> (Receitas - Despesas) = Lucro Líquido.</li>
                        <li><strong>Fluxo de Caixa:</strong> Entradas e saídas dia a dia.</li>
                        <li><strong>Curva ABC:</strong> Descubra seus produtos campeões de venda.</li>
                    </ul>
                    <a href="relatorios.php" class="btn-action">Ver Relatórios</a>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 60px; border-top: 1px solid #333; padding-top: 20px;">
        <p style="color: #888;">Ainda precisa de ajuda?</p>
        <a href="suporte.php" class="btn-action" style="max-width: 200px; background: #666;"><i class="fas fa-headset"></i> Abrir Chamado</a>
        <br><br>
        <a href="home.php" style="color: #007bff; text-decoration: none;">&larr; Voltar para a Dashboard</a>
    </div>

</div>

</body>
</html>