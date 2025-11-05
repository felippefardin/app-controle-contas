<?php
session_start();
// Verifique se o usuário acabou de se registrar
if (!isset($_SESSION['registration_email'])) {
    // Se não, mande-o para o registro
    header("Location: register_.php");
    exit;
}

// 1. Pegar o ID do Plano da sua URL
$plan_id = "41056ed7a72c4f39ac45cb600cb855b3";

// 2. Pegar o email do usuário da sessão
$payer_email = urlencode($_SESSION['registration_email']);

// 3. Montar a URL de checkout completa com o email
$checkout_url = "https://www.mercadopago.com.br/subscriptions/checkout?preapproval_plan_id={$plan_id}&payer_email={$payer_email}";

// 4. (IMPORTANTE) URL de retorno
// Você DEVE configurar no seu painel do Mercado Pago para onde o usuário
// volta após o sucesso (ex: 'https://seusite.com/actions/retorno_trial.php')
// Esse script 'retorno_trial.php' receberá os dados e finalizará o cadastro.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Cadastro - Teste Gratuito</title>
    <!-- Seus links de CSS podem vir aqui -->
    <style>
        .container { max-width: 500px; margin-top: 50px; font-family: Arial, sans-serif; }
        .mp-button {
            display: inline-block;
            padding: 15px 25px;
            font-size: 1.2em;
            font-weight: bold;
            color: #fff;
            background-color: #009ee3;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            margin-top: 20px;
        }
        .mp-button:hover { background-color: #007bb2; }
    </style>
</head>
<body>

<?php
// Inclua seu header
// Ex: include '../includes/header_home.php';
?>

<div class="container">
    <h2>Quase lá, <?php echo htmlspecialchars($_SESSION['nome_usuario_temp'] ?? 'Usuário'); ?>!</h2>
    <p>Complete seu cadastro para iniciar seu <strong style="color: green;">teste grátis de 30 dias</strong>.</p>
    <p>Nenhuma cobrança será feita hoje. Você será cobrado R$ 30,00/mês apenas após o período de teste.</p>
    <p>Clique no botão abaixo para concluir seu cadastro de pagamento de forma segura no site do Mercado Pago.</p>
    
    <!-- 
      Botão que redireciona o usuário para o link de checkout que você criou.
    -->
    <a href="<?php echo $checkout_url; ?>" class="mp-button">
        Iniciar Teste Gratuito (Pagar com Mercado Pago)
    </a>

</div>

<?php
include '../includes/footer.php';
?>
</body>
</html>