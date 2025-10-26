<?php
session_start();
include('../includes/header.php');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Tutorial do Sistema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* ======= Estilo Global ======= */
    * {
        box-sizing: border-box;
    }

    body {
        background-color: #121212;
        color: #eee;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    main {
        padding: 90px 20px 40px; /* espaço para não colar no header */
        min-height: calc(100vh - 160px); /* garante espaço entre header e footer */
    }

    /* ======= Container Central ======= */
    .container {
        max-width: 1000px;
        margin: 0 auto;
        background-color: #1f1f1f;
        padding: 40px 35px;
        border-radius: 12px;
        border: 1px solid rgba(0, 191, 255, 0.15);
        box-shadow: 0 0 25px rgba(0, 191, 255, 0.07);
    }

    /* ======= Títulos ======= */
    h1, h2, h3 {
        color: #00bfff;
        text-align: center;
        margin-bottom: 25px;
        letter-spacing: 0.5px;
    }

    h1 {
        font-size: 1.8em;
        border-bottom: 2px solid #00bfff;
        padding-bottom: 10px;
    }

    h3 {
        color: #27ae60;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
        font-size: 1.2em;
        border: none;
    }

    /* ======= Seções do Tutorial ======= */
    .secao-tutorial {
        margin-bottom: 45px;
    }

    .secao-tutorial p {
        color: #ccc;
        text-align: justify;
        margin-bottom: 12px;
        font-size: 0.95em;
    }

    .secao-tutorial ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .secao-tutorial li {
        background-color: #2a2a2a;
        padding: 12px 14px;
        border-radius: 6px;
        margin-bottom: 10px;
        display: flex;
        align-items: flex-start;
        border-left: 4px solid #00bfff;
        gap: 10px;
    }

    .secao-tutorial li i {
        color: #00bfff;
        font-size: 1.1em;
        margin-top: 3px;
        flex-shrink: 0;
    }

    /* ======= Ícones ======= */
    h1 i,
    h3 i {
        color: #00bfff;
    }

    /* ======= Responsividade ======= */
    @media (max-width: 992px) {
        main {
            padding: 100px 15px 60px;
        }

        .container {
            width: 95%;
            padding: 25px 20px;
        }

        h1 {
            font-size: 1.5em;
        }

        h3 {
            font-size: 1.1em;
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 20px 15px;
            border-radius: 8px;
        }

        h1 {
            font-size: 1.3em;
        }

        .secao-tutorial li {
            font-size: 0.9em;
            padding: 10px 12px;
        }
    }

    /* ======= Footer ======= */
    footer {
        background-color: #1a1a1a;
        color: #aaa;
        text-align: center;
        padding: 15px 0;
        font-size: 0.9em;
        border-top: 1px solid #222;
    }
</style>

</head>
<body>

<div class="container">
    <h1><i class="fas fa-book-open"></i> Tutorial do Sistema de Controle de Contas</h1>

    <div class="secao-tutorial">
        <h3><i class="fas fa-users-cog"></i> Tipos de Conta e Acessos</h3>
        <p>O sistema possui diferentes níveis de acesso para organizar e gerenciar as informações de forma segura e eficiente.</p>
        <ul>
            <li><i class="fas fa-user-tie"></i> <strong>Conta Principal:</strong> É a conta que gerencia um conjunto de operações. O usuário de uma conta principal pode cadastrar outros usuários (sub-usuários) que estarão vinculados a ela. Todas as informações de vendas, compras e finanças são restritas à sua conta principal e aos seus usuários.</li>
            <li><i class="fas fa-user"></i> <strong>Conta de Usuário:</strong> São os usuários cadastrados por uma conta principal. Eles podem realizar operações no sistema, como vendas e registros financeiros, mas todo o histórico fica atrelado à conta principal que os criou.</li>
            <li><i class="fas fa-user-shield"></i> <strong>Acesso Proprietário:</strong> Um nível de acesso especial que permite a um administrador (proprietário) visualizar e gerenciar as contas de outros usuários principais do sistema. Ao acessar como proprietário, você pode "incorporar" uma conta principal para ver todos os seus dados e de seus sub-usuários.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-tachometer-alt"></i> Dashboard (Página Inicial)</h3>
        <p>A página inicial oferece uma visão geral e rápida da sua situação financeira, com acesso rápido a todas as funcionalidades e alertas importantes.</p>
        <ul>
            <li><i class="fas fa-exclamation-triangle"></i> Receba alertas de produtos com estoque baixo diretamente na home.</li>
            <li><i class="fas fa-bars"></i> Navegue facilmente por todas as seções do sistema através dos menus.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-cash-register"></i> Caixa de Vendas (PDV)</h3>
        <p>Realize vendas de forma rápida e integrada. O PDV foi projetado para ser o ponto central de suas operações comerciais.</p>
        <ul>
            <li><i class="fas fa-user-check"></i> <strong>Selecione o Cliente:</strong> Busque e selecione o cliente para registrar a venda em seu nome.</li>
            <li><i class="fas fa-plus-circle"></i> <strong>Adicione Produtos:</strong> Pesquise produtos pelo nome e adicione-os à venda com um clique. O sistema já informa o estoque disponível.</li>
            <li><i class="fas fa-tags"></i> <strong>Aplique Descontos:</strong> Informe um valor de desconto que será abatido do total da venda.</li>
            <li><i class="fas fa-credit-card"></i> <strong>Formas de Pagamento:</strong> Escolha entre Dinheiro, PIX, Débito, Crédito ou Fiado (A Prazo).</li>
            <li><i class="fas fa-receipt"></i> <strong>Finalize a Venda:</strong> Ao finalizar, você pode gerar um recibo simples para impressão ou, se configurado, emitir uma Nota Fiscal Eletrônica (NF-e).</li>
            <li><i class="fas fa-sync-alt"></i> <strong>Integração Automática:</strong> Cada venda atualiza o estoque dos produtos e, no caso de "Fiado", cria automaticamente uma conta a receber para o cliente.</li>
        </ul>
    </div>
    
     <div class="secao-tutorial">
        <h3><i class="fas fa-dolly"></i> Registro de Compras</h3>
        <p>Gerencie a entrada de novos produtos no seu estoque de forma integrada com o financeiro.</p>
        <ul>
            <li><i class="fas fa-truck"></i> <strong>Selecione o Fornecedor:</strong> Busque e selecione o fornecedor da compra.</li>
            <li><i class="fas fa-plus"></i> <strong>Adicione os Produtos:</strong> Pesquise os produtos e adicione-os, informando a quantidade e o custo unitário.</li>
            <li><i class="fas fa-check-double"></i> <strong>Integração Total:</strong> Ao finalizar a compra, o sistema automaticamente aumenta a quantidade dos produtos no estoque e gera uma conta a pagar para o fornecedor selecionado.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-file-invoice-dollar"></i> Contas a Pagar</h3>
        <p>Nesta seção, você gerencia todas as suas despesas e contas que precisam ser pagas.</p>
        <ul>
            <li><i class="fas fa-plus-circle"></i> <strong>Adicionar Nova Conta:</strong> Clique no botão para abrir um formulário onde você insere os dados da conta (fornecedor, valor, vencimento, categoria).</li>
            <li><i class="fas fa-search"></i> <strong>Buscar:</strong> Utilize os filtros para encontrar contas específicas por fornecedor, número ou data.</li>
            <li><i class="fas fa-check-circle"></i> <strong>Baixar:</strong> Ao pagar uma conta, clique em "Baixar" para registrar o pagamento, informar a forma de pagamento, juros (se houver) e anexar um comprovante.</li>
            <li><i class="fas fa-clone"></i> <strong>Repetir:</strong> Se for uma conta recorrente, use o botão "Repetir" para criar as próximas parcelas automaticamente.</li>
            <li><i class="fas fa-edit"></i> <strong>Editar e Excluir:</strong> Altere ou remova contas a qualquer momento.</li>
        </ul>
    </div>
    
   <div class="secao-tutorial">
        <h3><i class="fas fa-hand-holding-usd"></i> Contas a Receber</h3>
        <p>Aqui você administra tudo o que precisa receber de seus clientes ou outras fontes.</p>
        <ul>
            <li><i class="fas fa-plus-circle"></i> <strong>Adicionar Nova Conta:</strong> Adicione novas receitas com seus detalhes e categoria.</li>
            <li><i class="fas fa-search"></i> <strong>Pesquisa Inteligente:</strong> Ao adicionar uma conta ou gerar uma cobrança, digite o nome do responsável para encontrar o cadastro rapidamente.</li>
            <li><i class="fas fa-check-double"></i> <strong>Baixar:</strong> Quando receber um pagamento, marque a conta como "baixada", informando os detalhes do recebimento e anexando o comprovante.</li>
            <li><i class="fas fa-envelope"></i> <strong>Enviar Cobrança:</strong> Gere e envie um e-mail de cobrança diretamente do sistema, com dados para pagamento e opção de anexo.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-users"></i> Clientes e Fornecedores</h3>
        <p>Centralize o cadastro de todas as pessoas e empresas com as quais você se relaciona financeiramente.</p>
        <ul>
            <li><i class="fas fa-user-plus"></i> <strong>Cadastrar:</strong> Adicione novos clientes ou fornecedores com suas informações de contato.</li>
            <li><i class="fas fa-history"></i> <strong>Histórico Completo:</strong> Clique no botão "Histórico" para visualizar todas as transações (contas a pagar, a receber, compras e vendas) vinculadas a um cadastro.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-boxes"></i> Controle de Estoque</h3>
        <p>Gerencie seus produtos, controle entradas e saídas e mantenha o inventário sempre atualizado.</p>
        <ul>
            <li><i class="fas fa-box-open"></i> <strong>Cadastrar Produto:</strong> Adicione produtos informando nome, quantidade inicial, quantidade mínima, preços e dados fiscais (NCM/CFOP).</li>
            <li><i class="fas fa-bell"></i> <strong>Alerta de Estoque Mínimo:</strong> O sistema exibirá um alerta na página inicial sempre que o estoque de um produto atingir a quantidade mínima definida.</li>
        </ul>
    </div>
    
     <div class="secao-tutorial">
        <h3><i class="fas fa-tags"></i> Categorias</h3>
        <p>Organize suas finanças criando categorias para suas despesas e receitas.</p>
        <ul>
            <li><i class="fas fa-plus"></i> Crie categorias como "Aluguel", "Salários", "Venda de Produtos" para classificar seus lançamentos.</li>
            <li><i class="fas fa-edit"></i> Edite ou exclua categorias existentes a qualquer momento.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-file-invoice"></i> Configurações Fiscais</h3>
        <p>Preencha os dados da sua empresa para habilitar a emissão de Notas Fiscais Eletrônicas (NF-e/NFC-e).</p>
        <ul>
            <li><i class="fas fa-building"></i> Informe os dados da empresa, endereço e regime tributário.</li>
            <li><i class="fas fa-id-card"></i> Insira o CSC (Token) fornecido pela SEFAZ.</li>
            <li><i class="fas fa-certificate"></i> Faça o upload do seu Certificado Digital A1 e informe a senha para assinar os documentos fiscais.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-chart-pie"></i> Relatórios</h3>
        <p>Acesse um dashboard completo com a visão geral da saúde financeira do seu negócio.</p>
        <ul>
            <li><i class="fas fa-balance-scale"></i> Visualize balanços de valores previstos (contas em aberto) e realizados (contas baixadas).</li>
            <li><i class="fas fa-chart-bar"></i> Analise um gráfico de fluxo de caixa dos últimos 12 meses.</li>
             <li><i class="fas fa-file-pdf"></i> Exporte um resumo completo do dashboard em formato PDF.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-id-card"></i> Perfil</h3>
        <p>Gerencie suas informações pessoais e configurações de conta.</p>
        <ul>
            <li><i class="fas fa-camera"></i> Altere sua foto de perfil para personalizar sua conta.</li>
            <li><i class="fas fa-key"></i> Modifique sua senha de acesso para manter sua conta segura.</li>
            <li><i class="fas fa-user-slash"></i> Solicite a exclusão da sua conta. Um e-mail será enviado para confirmar a ação.</li>
        </ul>
    </div>

</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>