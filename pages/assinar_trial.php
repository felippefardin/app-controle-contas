<?php
session_start();
// Verifique se o usuário acabou de se registrar
if (!isset($_SESSION['registration_email'])) {
    // Se não, mande-o para o registro
    header("Location: register_.php");
    exit;
}
// Inclua seu header (sem menu de navegação, pois não está logado)
?>

<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h2>Quase lá, <?php echo htmlspecialchars($_SESSION['nome_usuario_temp'] ?? 'Usuário'); ?>!</h2>
    <p>Complete seu cadastro para iniciar seu <strong style="color: green;">teste grátis de 30 dias</strong>.</p>
    <p>Nenhuma cobrança será feita hoje. Você será cobrado R$ 30,00/mês apenas após o período de teste.</p>
    
    <form id="form-checkout" action="../actions/processar_trial.php" method="POST">
        
        <div id="paymentBrick_container"></div>

        <button type="submit" id="form-checkout__submit">Iniciar Teste Grátis</button>
        <div id="loading-spinner" style="display: none;">Processando...</div>

        <input type="hidden" id="card_token" name="card_token" />
        <input type="hidden" id="payer_email" name="payer_email" />
    </form>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>

<script>
    
    const mp = new MercadoPago('APP_USR-b32aa1af-8eb4-4c72-9fec-8242aba7b4ca', {
        locale: 'pt-BR'
    });
    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: 30.00,
                payer: {
                    email: "<?php echo $_SESSION['registration_email']; ?>",
                },
            },
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    maxInstallments: 1
                },
                visual: {
                    buttonText: 'Confirmar Dados e Iniciar Teste'
                }
            },
            callbacks: {
                onReady: () => {
                    console.log('✅ Brick carregado com sucesso');
                },
                onSubmit: (cardFormData) => {
                    document.getElementById('loading-spinner').style.display = 'block';
                    document.getElementById('form-checkout__submit').disabled = true;

                    document.getElementById('card_token').value = cardFormData.token;
                    document.getElementById('payer_email').value = cardFormData.payer.email;

                    document.getElementById('form-checkout').submit();
                },
                onError: (error) => {
                    console.error('❌ Erro no Brick:', error);
                    alert('Houve um erro com seus dados de pagamento. Verifique e tente novamente.');
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
// Incluir seu footer
?>