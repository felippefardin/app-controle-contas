<?php
require_once '../includes/session_init.php';
include '../includes/header.php'; // Inclui o header
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proteção de Dados (LGPD) - App Controle de Contas</title>
    <style>
        body {
            background-color: #121212;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 80px; /* Espaço para o header fixo */
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background: #222;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.5);
        }
        h1, h2 {
            color: #00bfff;
            text-align: center;
        }
        .section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h3 {
            color: #0af;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .section p {
            line-height: 1.7;
            text-align: justify;
        }
        ul {
            line-height: 1.7;
            padding-left: 20px;
        }
        a {
            color: #0af;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Nossa Política de Proteção de Dados</h1>

    <div class="section">
        <h3>Nosso Compromisso com sua Privacidade</h3>
        <p>
            Sua privacidade é nossa prioridade. Esta página explica como tratamos seus dados pessoais de acordo com a <strong>Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018)</strong>, garantindo transparência e segurança.
        </p>
    </div>

    <div class="section">
        <h3>O que é a LGPD?</h3>
        <p>
            A LGPD é a lei brasileira que estabelece regras claras sobre como as empresas devem coletar, armazenar, usar e compartilhar dados pessoais. O principal objetivo é proteger seus direitos de liberdade e privacidade, dando a você mais controle sobre suas informações.
        </p>
    </div>

    <div class="section">
        <h3>Quais dados coletamos e por quê?</h3>
        <p>
            Para o funcionamento do <strong>App Controle de Contas</strong>, coletamos apenas os dados estritamente necessários:
        </p>
        <ul>
            <li><strong>Dados de Cadastro:</strong> Seu nome e e-mail são utilizados para criar sua conta, permitir o acesso seguro ao sistema e para comunicação essencial, como redefinição de senha e lembretes de contas.</li>
            <li><strong>Dados Financeiros:</strong> As informações sobre suas contas a pagar e a receber, valores e datas são o coração do sistema. Elas são usadas exclusivamente para que você possa organizar suas finanças e gerar seus relatórios.</li>
        </ul>
        <p>
            Nós não compartilhamos seus dados com outras empresas para fins de marketing ou qualquer outra finalidade não descrita aqui.
        </p>
    </div>

    <div class="section">
        <h3>Como protegemos seus dados?</h3>
        <p>
            Levamos a segurança a sério. Utilizamos práticas de segurança para proteger suas informações contra acessos não autorizados, alterações ou destruição. Sua senha, por exemplo, é armazenada de forma criptografada, o que significa que nem mesmo nossa equipe tem acesso a ela.
        </p>
    </div>

    <div class="section">
        <h3>Quais são os seus direitos?</h3>
        <p>
            A LGPD garante a você total controle sobre seus dados. A qualquer momento, você pode solicitar:
        </p>
        <ul>
            <li><strong>Acesso aos dados:</strong> Você pode pedir para ver todas as informações que temos sobre você.</li>
            <li><strong>Correção de dados:</strong> Se seus dados estiverem incompletos ou incorretos, você pode atualizá-los.</li>
            <li><strong>Eliminação de dados:</strong> Você tem o direito de solicitar a exclusão da sua conta e de todos os seus dados do nosso sistema.</li>
        </ul>
    </div>
    
    <div class="section">
        <h3>Fale Conosco</h3>
        <p>
            Para exercer seus direitos ou tirar qualquer dúvida sobre como tratamos seus dados, entre em contato conosco pelo e-mail: <strong><a href="mailto:contatotech.tecnologia@gmail.com">contatotech.tecnologia@gmail.com</a></strong>.
        </p>
    </div>
</div>

</body>
</html>