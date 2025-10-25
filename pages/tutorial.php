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
        body {
            background-color: #121212;
            color: #eee;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background-color: #1f1f1f;
            padding: 35px;
            border-radius: 12px;
            border: 1px solid rgba(0, 191, 255, 0.2);
            box-shadow: 0 0 25px rgba(0, 191, 255, 0.08);
        }

        h1, h2, h3 {
            color: #00bfff;
            text-align: center;
            border-bottom: 2px solid #00bfff;
            padding-bottom: 10px;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }

        .secao-tutorial {
            margin-bottom: 45px;
        }

        .secao-tutorial h3 {
            color: #27ae60;
            border-bottom: none;
            text-align: left;
            margin-bottom: 15px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .secao-tutorial p {
            line-height: 1.7;
            text-align: justify;
            margin-bottom: 12px;
            color: #ccc;
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
            align-items: center;
            border-left: 4px solid #00bfff;
        }

        .secao-tutorial li i {
            margin-right: 10px;
            color: #00bfff;
            font-size: 1.1em;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-book-open"></i> Tutorial do Sistema de Controle de Contas</h1>

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
        <h3><i class="fas fa-user-shield"></i> Acesso Proprietário</h3>
        <p>Um nível de acesso especial que permite a um administrador (proprietário) visualizar e gerenciar as contas de outros usuários principais do sistema.</p>
        <ul>
            <li><i class="fas fa-eye"></i> Selecione a conta de um usuário principal para acessar seu ambiente como se fosse ele.</li>
            <li><i class="fas fa-arrow-left"></i> Um banner no topo da tela indicará que você está em modo de visualização e permitirá "Voltar para o Acesso Proprietário" a qualquer momento.</li>
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