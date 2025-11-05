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
    
    <!-- 
      O formulário agora é enviado APENAS pelo JavaScript
      O 'action' e 'method' ainda são úteis para o JS
    -->
    <form id="form-checkout" action="../actions/processar_trial.php" method="POST">
        
        <!-- O Brick será renderizado aqui. Ele contém seu próprio botão de submit -->
        <div id="paymentBrick_container"></div>

        <!-- 
          ✅ CORREÇÃO: O botão de submit externo foi removido.
          O usuário DEVE clicar no botão gerado pelo Brick acima.
          O botão abaixo (id="form-checkout__submit") foi removido
          para evitar o envio do formulário com o token vazio.
        -->
        
        <!-- Spinner de carregamento -->
        <div id="loading-spinner" style="display: none; margin-top: 15px; text-align: center;">Processando...</div>

        <!-- Campos ocultos que o JS irá preencher -->
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
    const formCheckout = document.getElementById('form-checkout');
    const loadingSpinner = document.getElementById('loading-spinner');

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: 30.00, // O valor aqui é apenas para exibição no Brick
                payer: {
                    email: "<?php echo $_SESSION['registration_email']; ?>",
                    entityType: 'individual', // Adicionado para remover o warning
                },
            },
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    maxInstallments: 1
                },
                visual: {
                    // Este é o botão que o usuário deve clicar
                    buttonText: 'Confirmar Dados e Iniciar Teste' 
                }
            },
            callbacks: {
                onReady: () => {
                    console.log('✅ Brick carregado com sucesso');
                },
                onSubmit: (cardFormData) => {
                    // Este callback é disparado quando o usuário clica
                    // no botão DENTRO do Brick
                    
                    // Mostra o spinner
                    loadingSpinner.style.display = 'block';

                    // Preenche os dados no formulário
                    document.getElementById('card_token').value = cardFormData.token;
                    document.getElementById('payer_email').value = cardFormData.payer.email;

                    // Envia o formulário
                    formCheckout.submit();
                },
                onError: (error) => {
                    console.error('❌ Erro no Brick:', error);
                    loadingSpinner.style.display = 'none'; // Esconde o spinner
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