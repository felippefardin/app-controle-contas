<?php
// --- 1. CARREGAR SEUS ARQUIVOS (ESSENCIAL!) ---
// Garanta que os caminhos para seus arquivos de inicialização estejam corretos
// (Saindo da pasta 'pages' para a raiz)

require_once('../includes/session_init.php');
require_once('../includes/config/config.php');
require_once('../database.php'); 

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=assinar');
    exit;
}

// Busca o email do usuário da sessão
$user_email = $_SESSION['user_email'] ?? 'teste@email.com';
$user_id_session = $_SESSION['user_id'] ?? 0;

// --- 2. INCLUIR O HEADER (CORRIGE O QUIRKS MODE) ---
// Seu header.php DEVE começar com <!DOCTYPE html>
include('../includes/header.php'); 
?>

<style>
    /* Customização da página de assinatura */
    body, html {
        height: 100%;
        /* O fundo idealmente viria do seu CSS principal ou do header */
        background-color: #f8f9fa; 
    }

    .payment-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 90vh; 
        padding-top: 60px; /* Espaço para o navbar (se houver) */
    }

    .payment-card {
        background: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        padding: 40px;
        width: 100%;
        max-width: 550px; 
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
        background-color: #0d6efd; 
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
        background-color: #0b5ed7; 
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
            <input type="hidden" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user_id_session, ENT_QUOTES, 'UTF-8'); ?>" />
        
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
                    // Corrige o aviso "entityType" do Brick
                    entityType: "individual" 
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
                    document.getElementById('loading-spinner').style.display = 'block';
                    document.getElementById('form-checkout__submit').disabled = true;

                    document.getElementById('card_token').value = cardFormData.token;
                    document.getElementById('payer_email').value = cardFormData.payer.email;
                    
                    document.getElementById('form-checkout').submit();
                },
                onError: (error) => {
                    console.error(error);
                    alert('Houve um erro com seus dados de pagamento. Verifique e tente novamente.');
                    
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
// --- 3. INCLUIR O FOOTER (ESSENCIAL!) ---
// Seu footer.php deve ter o </body> e </html>
include('../includes/footer.php'); 
?>