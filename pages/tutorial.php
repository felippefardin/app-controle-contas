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
    transition: box-shadow 0.3s ease, transform 0.2s ease;
}

.container:hover {
    box-shadow: 0 0 35px rgba(0, 191, 255, 0.15);
    transform: translateY(-2px);
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
    animation: fadeInUp 0.6s ease;
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
    border: 1px solid rgba(0, 191, 255, 0.1);
    transition: background-color 0.25s ease, transform 0.2s ease, border-color 0.25s ease;
}

.secao-tutorial li:hover {
    background-color: #333;
    border-color: rgba(0, 191, 255, 0.3);
    transform: translateX(5px);
}

.secao-tutorial li i {
    margin-right: 10px;
    color: #00bfff;
    font-size: 1.1em;
}

/* Responsividade */
@media (max-width: 768px) {
    .container {
        padding: 20px;
    }

    h1 {
        font-size: 1.4em;
    }

    .secao-tutorial h3 {
        font-size: 1.1em;
    }

    .secao-tutorial li {
        font-size: 0.95em;
    }
}

/* Animação suave ao carregar */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-book-open"></i> Tutorial do Sistema de Controle de Contas</h1>

    <div class="secao-tutorial">
        <h3><i class="fas fa-tachometer-alt"></i> Dashboard (Página Inicial)</h3>
        <p>A página inicial oferece uma visão geral e rápida da sua situação financeira, exibindo gráficos e resumos importantes.</p>
        <ul>
            <li><i class="fas fa-chart-line"></i> Visualize gráficos de contas a pagar e a receber por período.</li>
            <li><i class="fas fa-wallet"></i> Acompanhe os totais de contas pendentes e baixadas.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-file-invoice-dollar"></i> Contas a Pagar</h3>
        <p>Nesta seção, você gerencia todas as suas despesas e contas que precisam ser pagas.</p>
        <ul>
            <li><i class="fas fa-plus-circle"></i> <strong>Adicionar Nova Conta:</strong> Clique no botão "Adicionar" para abrir um formulário onde você insere os dados da conta (fornecedor, valor, vencimento).</li>
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
            <li><i class="fas fa-plus-circle"></i> <strong>Adicionar Nova Conta:</strong> Semelhante às contas a pagar, adicione novas receitas com seus detalhes.</li>
            <li><i class="fas fa-check-double"></i> <strong>Baixar:</strong> Quando receber um pagamento, marque a conta como "baixada", informando os detalhes do recebimento e anexando o comprovante.</li>
            <li><i class="fas fa-envelope"></i> <strong>Enviar Cobrança:</strong> Gere e envie um e-mail de cobrança diretamente do sistema para o responsável pela conta.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-archive"></i> Contas Baixadas (Pagar e Receber)</h3>
        <p>Essas páginas exibem um histórico de todas as contas que já foram pagas ou recebidas.</p>
        <ul>
            <li><i class="fas fa-history"></i> Consulte o histórico detalhado de cada transação.</li>
            <li><i class="fas fa-file-download"></i> Visualize e baixe os comprovantes anexados a qualquer momento.</li>
            <li><i class="fas fa-trash-alt"></i> Exclua registros de forma permanente se necessário (ação irreversível).</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-users-cog"></i> Usuários</h3>
        <p>Se você for um administrador, esta área permite gerenciar os usuários do sistema.</p>
        <ul>
            <li><i class="fas fa-user-plus"></i> Adicione novos usuários e defina seus perfis (administrador ou usuário padrão).</li>
            <li><i class="fas fa-user-edit"></i> Edite as informações e permissões dos usuários existentes.</li>
            <li><i class="fas fa-user-times"></i> Remova usuários que não precisam mais de acesso.</li>
        </ul>
    </div>
    
     <div class="secao-tutorial">
        <h3><i class="fas fa-id-card"></i> Perfil</h3>
        <p>Gerencie suas informações pessoais e configurações de conta.</p>
        <ul>
            <li><i class="fas fa-camera"></i> Altere sua foto de perfil para personalizar sua conta.</li>
            <li><i class="fas fa-key"></i> Modifique sua senha de acesso para manter sua conta segura.</li>
        </ul>
    </div>
    
    <div class="secao-tutorial">
        <h3><i class="fas fa-chart-pie"></i> Relatórios</h3>
        <p>Exporte relatórios detalhados para análises financeiras e contábeis.</p>
        <ul>
            <li><i class="fas fa-filter"></i> Filtre os dados por período (data de início e fim) e status (pendente ou baixada).</li>
            <li><i class="fas fa-file-export"></i> Exporte os relatórios nos formatos PDF, Excel (XLSX) ou CSV.</li>
        </ul>
    </div>

    <div class="secao-tutorial">
        <h3><i class="fas fa-calculator"></i> Calculadora Flutuante</h3>
        <p>Uma ferramenta rápida para cálculos, disponível em qualquer página do sistema.</p>
        <ul>
            <li><i class="fas fa-mouse-pointer"></i> <strong>Abrir:</strong> Clique no ícone da calculadora (&#128290;) no canto inferior esquerdo para exibi-la.</li>
            <li><i class="fas fa-hand-point-up"></i> <strong>Ativar:</strong> Clique em qualquer parte da calculadora para ativá-la. A borda ficará azul, indicando que ela pode receber comandos do teclado.</li>
            <li><i class="fas fa-arrows-alt"></i> <strong>Mover:</strong> Clique e arraste o cabeçalho da calculadora para movê-la pela tela.</li>
            <li><i class="fas fa-power-off"></i> <strong>Desativar:</strong> Clique em qualquer lugar fora da calculadora para desativá-la. A borda voltará ao normal e ela não responderá mais ao teclado.</li>
            <li><i class="fas fa-times"></i> <strong>Fechar:</strong> Clique no '×' no canto superior direito para fechar a calculadora.</li>
        </ul>
    </div>

</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>