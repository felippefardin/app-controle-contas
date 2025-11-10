<?php
// pages/tutorial.php

// Inicia a sessão e inclui funções essenciais de utilidade e segurança.
include_once '../includes/session_init.php';

// Proteção básica: redireciona para o login se não houver sessão ativa (usuario_logado ou super_admin)
if (!isset($_SESSION['usuario_logado']) && !isset($_SESSION['super_admin'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Determina o nome do usuário e o perfil para personalizar o guia.
$nome_usuario = $_SESSION['usuario_logado']['nome'] ?? $_SESSION['super_admin']['nome'] ?? 'Usuário';
$perfil = $_SESSION['usuario_logado']['nivel_acesso'] ?? 'admin_master';
$is_proprietario_ou_admin = ($perfil === 'proprietario' || $perfil === 'admin');

// Se o Admin Master estiver logado, mas não "impersonando" um tenant, ele verá o tutorial, 
// mas o foco continua sendo nas operações do tenant.
$is_master = ($perfil === 'admin_master');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial Completo - App Controle de Contas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .tutorial-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: #2a2a2a;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        h1 {
            color: #00bfff;
            text-align: center;
            border-bottom: 2px solid #00bfff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        h2 {
            color: #fff;
            margin-top: 25px;
            border-left: 5px solid #28a745;
            padding-left: 10px;
            background-color: #333;
            padding: 10px;
            border-radius: 4px;
        }
        h3 {
            color: #ccc;
            margin-top: 15px;
            font-size: 1.1rem;
            border-bottom: 1px solid #444;
            padding-bottom: 5px;
        }
        p, li {
            color: #bbb;
            line-height: 1.6;
        }
        ul {
            list-style: disc inside;
            padding-left: 20px;
        }
        li strong {
            color: #fff;
        }
        .section-note {
            background-color: #444;
            padding: 10px;
            margin: 15px 0;
            border-left: 5px solid #ffc107;
            color: #fff;
        }
        .action-link {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 10px;
            transition: background-color 0.3s;
        }
        .action-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="tutorial-container">
        <h1>Guia de Utilização do Sistema (Para Clientes/Tenants)</h1>
        
        <p>Bem-vindo(a), <?= htmlspecialchars($nome_usuario) ?>. Este guia rápido detalha as principais funcionalidades do seu sistema de controle financeiro e estoque. O seu perfil é: **<?= htmlspecialchars(ucfirst($perfil)) ?>**.</p>
        
        <p style="text-align: right;"><a href="home.php" class="action-link" style="margin: 0; background-color: #28a745;">&#x2B05; Voltar para a Home</a></p>
        
        
        <h2>Módulo 1: Acesso e Configuração Inicial</h2>
        
        <h3>1.1. Acesso ao Sistema</h3>
        <ul>
            <li>O login é feito com e-mail e senha cadastrados.</li>
            <li>Utilize a opção <a href="logout.php" class="action-link">Sair</a> para encerrar sua sessão de forma segura.</li>
            <li>Se necessário, use <a href="esqueci_senha_login.php">Esqueci minha senha</a> para redefinir.</li>
        </ul>
        
        <h3>1.2. Perfil e Informações Pessoais</h3>
        <ul>
            <li>Em <a href="perfil.php" class="action-link">Perfil</a>, você pode visualizar e atualizar suas informações (nome, telefone e foto) e trocar sua senha.</li>
        </ul>
        
        <h3>1.3. Gerência de Usuários (Acesso Nível Proprietário/Admin)</h3>
        <?php if ($is_proprietario_ou_admin): ?>
        <ul>
            <li>Acesse <a href="usuarios.php" class="action-link">Usuários</a> para **cadastrar e gerenciar sub-usuários** (perfis Padrão ou Admin) que usarão a conta da sua empresa.</li>
            <li>Utilize <a href="selecionar_usuario.php" class="action-link">Trocar Usuário</a> para alternar rapidamente entre usuários logados na sua conta de cliente.</li>
        </ul>
        <?php else: ?>
        <div class="section-note">
            **Atenção:** Seu perfil é **Padrão**. A gerência de usuários e outras configurações administrativas estão restritas ao usuário Proprietário/Admin.
        </div>
        <?php endif; ?>

        <hr>
        
        
        <h2>Módulo 2: Cadastros Base e Estrutura</h2>
        
        <div class="section-note">
            Estes cadastros (acessíveis apenas pelo Proprietário/Admin) são a fundação do seu sistema e devem ser definidos antes de lançar operações.
        </div>

        <h3>2.1. Clientes e Fornecedores</h3>
        <ul>
            <li>Cadastre e gerencie todos os parceiros comerciais em <a href="cadastrar_pessoa_fornecedor.php" class="action-link">Clientes/Fornecedores</a>.</li>
            <li>É possível ver o histórico de transações de cada um.</li>
        </ul>

        <h3>2.2. Categorias Financeiras</h3>
        <ul>
            <li>Defina classificações para suas receitas e despesas em <a href="categorias.php" class="action-link">Categorias</a>. Uma boa categorização é essencial para os relatórios.</li>
        </ul>

        <h3>2.3. Contas Bancárias</h3>
        <ul>
            <li>Registre e edite as contas bancárias da sua empresa (para uso em Fluxo de Caixa, Contas a Pagar e Receber) em <a href="banco_cadastro.php" class="action-link">Contas Bancárias</a>.</li>
        </ul>
        
        <h3>2.4. Produtos e Configuração Fiscal</h3>
        <?php if ($is_proprietario_ou_admin): ?>
        <ul>
            <li>Gerencie seu inventário e a quantidade mínima de estoque em <a href="controle_estoque.php" class="action-link">Estoque</a>.</li>
            <li>Configure os dados fiscais e certificados da empresa em <a href="configuracao_fiscal.php" class="action-link">Config. Fiscal</a> para emissão de NFC-e (se disponível).</li>
        </ul>
        <?php else: ?>
        <div class="section-note">
            **Seu acesso:** Você pode apenas usar os produtos já cadastrados nas vendas. O controle de estoque e a configuração fiscal estão restritos ao perfil Proprietário/Admin.
        </div>
        <?php endif; ?>

        <hr>

        
        <h2>Módulo 3: Fluxo de Transações (Financeiro e Operacional)</h2>

        <h3>3.1. Caixa de Vendas (Acesso Básico para todos os perfis)</h3>
        <ul>
            <li>Use <a href="vendas.php" class="action-link">Caixa de Vendas</a> para registrar operações de venda.</li>
            <li>O registro de vendas a prazo gera lançamentos automáticos em Contas a Receber.</li>
            <li>Permite emitir **Recibo de Venda** e **NFC-e** (se configurado).</li>
        </ul>

        <h3>3.2. Contas a Pagar e Receber (Acesso Nível Proprietário/Admin)</h3>
        <?php if ($is_proprietario_ou_admin): ?>
        <ul>
            <li>**Contas a Pagar:** Gerencie suas despesas em <a href="contas_pagar.php" class="action-link">Contas a Pagar</a> e dê baixa nos pagamentos. Acesse <a href="contas_pagar_baixadas.php">Pagas</a> para consultar o histórico.</li>
            <li>**Contas a Receber:** Monitore as entradas futuras em <a href="contas_receber.php" class="action-link">Contas a Receber</a>. Acesse <a href="contas_receber_baixadas.php">Recebidas</a> para o histórico.</li>
            <li>É possível **enviar lembretes de cobrança** por e-mail para as Contas a Receber.</li>
        </ul>
        <?php endif; ?>
        
        <h3>3.3. Compras e Caixa (Acesso Nível Proprietário/Admin)</h3>
        <?php if ($is_proprietario_ou_admin): ?>
        <ul>
            <li>**Registro de Compras:** Use <a href="compras.php" class="action-link">Registro de Compras</a> para registrar aquisições e aumentar o estoque.</li>
            <li>**Fluxo de Caixa:** Gerencie as movimentações diretas (entradas e saídas) que não passam pelo Pagar/Receber em <a href="lancamento_caixa.php" class="action-link">Fluxo de Caixa</a> e confira o fechamento diário.</li>
        </ul>
        <?php endif; ?>
        
        <hr>

        
        <h2>Módulo 4: Relatórios e Utilitários</h2>

        <h3>4.1. Relatórios (Acesso Nível Proprietário/Admin)</h3>
        <?php if ($is_proprietario_ou_admin): ?>
        <ul>
            <li>Visualize gráficos e dados consolidados (Financeiro, Estoque, Vendas) em <a href="relatorios.php" class="action-link">Relatórios</a>.</li>
            <li>Permite **exportar dados** para Excel.</li>
        </ul>
        <?php else: ?>
        <div class="section-note">
            A seção de Relatórios e Análises não está disponível para o seu perfil.
        </div>
        <?php endif; ?>
        
        <h3>4.2. Outros Utilitários</h3>
        <ul>
            <li>Use a <a href="calculadora.php" class="action-link">Calculadora</a> para cálculos rápidos no sistema.</li>
            <li>Envie sugestões de melhoria em <a href="feedback.php" class="action-link">Feedback</a> ou dúvidas em <a href="suporte.php" class="action-link">Suporte</a>.</li>
        </ul>

        <p style="text-align: center; margin-top: 40px; font-style: italic;">
            Este guia fornece uma visão geral. Explore cada módulo para dominar todas as ferramentas!
        </p>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="home.php" class="action-link" style="background-color: #28a745;">&#x2B05; Voltar para a Home</a>
            <a href="suporte.php" class="action-link" style="background-color: #00bfff;">&#x2709; Contato e Suporte</a>
        </p>

    </div>
</body>
</html>