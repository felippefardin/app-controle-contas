<?php
require_once '../includes/session_init.php'; // Protege a página
?>
<?php include '../includes/header.php'; ?>

<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h2>Atualizar Meio de Pagamento</h2>
    <p>Insira os dados do seu novo cartão. Seu plano continuará ativo.</p>
    <p>Se sua assinatura estiver "paused" (pausada) por falha no pagamento, a cobrança será retentada no novo cartão.</p>
    
    <form id="form-checkout" action="../actions/atualizar_cartao.php" method="POST">
        
        <div id="paymentBrick_container"></div>

        <button type="submit" id="form-checkout__submit">Atualizar Cartão</button>
        <div id="loading-spinner" style="display: none;">Processando...</div>

        <input type="hidden" id="card_token" name="card_token" />
        <input type="hidden" id="payer_email" name="payer_email" />
    </form>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    // Use sua Public Key de TESTE
    const mp = new MercadoPago('APP_USR-b32aa1af-8eb4-4c72-9fec-8242aba7b4ca', {
        locale: 'pt-BR'
    });
    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: 1, // Para atualização, o MP recomenda 1.00 BRL ou o valor do plano
                           // Vamos usar 1.00 apenas para validar o cartão.
                           // O valor real da assinatura não será alterado.
                amount: 30.00, // Melhor usar o valor do plano.
                payer: {
                    email: "<?php echo $_SESSION['user_email']; ?>", 
                },
            },
            customization: {
                 paymentMethods: { creditCard: 'all', maxInstallments: 1 }
            },
            callbacks: {
                onSubmit: (cardFormData) => {
                    document.getElementById('loading-spinner').style.display = 'block';
                    document.getElementById('form-checkout__submit').disabled = true;

                    // Preenche os campos ocultos
                    document.getElementById('card_token').value = cardFormData.token;
                    document.getElementById('payer_email').value = cardFormData.payer.email;
                    
                    // Envia o formulário para o seu backend
                    document.getElementById('form-checkout').submit();
                },
                onError: (error) => {
                    console.error(error);
                    alert('Houve um erro com seus dados de cartão. Verifique e tente novamente.');
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

<?php include '../includes/footer.php'; ?>