<?php
// ATENÇÃO: Você precisa iniciar a sessão e carregar seus includes
//
// Exemplo (descomente e ajuste os caminhos se necessário):
// require_once('../includes/session_init.php');
// require_once('../includes/config/config.php');
// require_once('../database.php');

// Redireciona se o usuário não estiver logado
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php?redirect=assinar');
//     exit;
// }

// Busca o email do usuário da sessão
// $user_email = $_SESSION['user_email'] ?? 'teste@email.com';

// ** APENAS PARA TESTE ** - Remova isso em produção
$user_email = 'teste@usuario.com'; 
// ** FIM DO TESTE **

// Incluir seu header (que deve ter o Bootstrap 5)
// include('../includes/header.php'); 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Assinar Plano</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
    /* Customização da página de assinatura */
    body, html {
        height: 100%;
        background-color: #f8f9fa; /* Um cinza claro para o fundo */
    }

    .payment-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 90vh; /* Centraliza verticalmente */
        padding-top: 60px; /* Espaço para o navbar */
    }

    .payment-card {
        background: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        padding: 40px;
        width: 100%;
        max-width: 550px; /* Define uma largura máxima */
    }

    .payment-card h2 {
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }
    
    .payment-card .lead {
        font-size: 1.1rem;
        color: #555;
        margin-bottom: 25px;
    }

    /* Estilizando o botão de submit */
    #form-checkout__submit {
        background-color: #0d6efd; /* Azul primário do Bootstrap */
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1.1rem;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-top: 20px;
    }

    #form-checkout__submit:hover {
        background-color: #0b5ed7; /* Um azul mais escuro no hover */
    }
    
    #form-checkout__submit:disabled {
        background-color: #aaa;
        cursor: not-allowed;
    }

    .terms-text {
        font-size: 0.9rem;
        color: #777;
        margin-top: 15px;
    }
    
    /* Spinner de carregamento (opcional, mas bom) */
    #loading-spinner {
        text-align: center;
        margin-top: 15px;
        font-weight: 500;
        color: #0d6efd;
    }
</style>

<div class="payment-container">
    <div class="payment-card">
        
        <h2><i class="bi bi-shield-lock"></i> Pagamento Seguro</h2>
        <p class="lead">Assine o Plano Premium (R$ 30,00/mês) com segurança via Mercado Pago.</p>

        <form id="form-checkout" action="../actions/processar_assinatura.php" method="POST">
            
            <div id="paymentBrick_container"></div>
            
            <div class="terms-text">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">Eu li e concordo com os <a href="termos.php" target="_blank">Termos de Uso</a> e a <a href="protecao_de_dados.php" target="_blank">Política de Privacidade</a>.</label>
            </div>

            <button type="submit" id="form-checkout__submit">Assinar Agora</button>
            
            <div id="loading-spinner" style="display: none;">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                Processando...
            </div>

            <input type="hidden" id="card_token" name="card_token" />
            <input type="hidden" id="payer_email" name="payer_email" />
            <input type="hidden" id="user_id" name="user_id" value="<?php echo $_SESSION['user_id'] ?? 0; ?>" />
        
        </form>
    </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>

<script>
    // 1. Use sua Public Key de TESTE aqui
    const mp = new MercadoPago('APP_USR-b32aa1af-8eb4-4c72-9fec-8242aba7b4ca', {
        locale: 'pt-BR'
    });
    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: 30.00,
                payer: {
                    email: "<?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?>",
                    
                    // --- CORREÇÃO AQUI ---
                    // Adicione esta linha:
                    entityType: "individual"
                    // --- FIM DA CORREÇÃO ---
                },
            },
            customization: {
                visual: {
                    style: {
                        theme: 'bootstrap',
                    }
                },
                paymentMethods: {
                    creditCard: 'all',
                    maxInstallments: 1 
                }
            },
            callbacks: {
                onReady: () => {
                    // Brick pronto
                },
                onSubmit: (cardFormData) => {
                    // Mostra o spinner e desabilita o botão
                    document.getElementById('loading-spinner').style.display = 'block';
                    document.getElementById('form-checkout__submit').disabled = true;

                    // Preenche os campos ocultos do formulário
                    document.getElementById('card_token').value = cardFormData.token;
                    document.getElementById('payer_email').value = cardFormData.payer.email;
                    
                    // Envia o formulário para o seu backend
                    document.getElementById('form-checkout').submit();
                },
                onError: (error) => {
                    // Callback de erro
                    console.error(error);
                    alert('Houve um erro com seus dados de pagamento. Verifique e tente novamente.');
                    
                    // Reabilita o botão
                    document.getElementById('loading-spinner').style.display = 'none';
                    document.getElementById('form-checkout__submit').disabled = false;
                },
            },
        };
        window.paymentBrickController = await bricksBuilder.create(
            'payment',
            'paymentBrick_container',
            settings
        );
    };

    renderPaymentBrick(bricksBuilder);
</script>

<?php
// Incluir seu footer.php
// include('../includes/footer.php'); 
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>