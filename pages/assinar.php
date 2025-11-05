<?php
// Incluir seu header, session_init.php, etc.
// ...
?>

<div class="container">
    <h2>Assinar Plano Premium (R$ 30,00/mês)</h2>
    <p>Você será cobrado mensalmente. Seus dados de pagamento são processados com segurança pelo Mercado Pago.</p>

    <form id="form-checkout" action="actions/processar_assinatura.php" method="POST">
        
        <div id="paymentBrick_container"></div>
        <input type="checkbox" required> Eu li e concordo com os <a href="termos.php" target="_blank">Termos de Uso</a> e a <a href="pages/protecao_de_dados.php" target="_blank">Política de Privacidade</a>.

        <button type="submit" id="form-checkout__submit">Assinar Agora</button>
        <div id="loading-spinner" style="display: none;">Processando...</div>

        <input type="hidden" id="card_token" name="card_token" />
        <input type="hidden" id="payer_email" name="payer_email" />
        </form>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>

<script>
    // Use sua Public Key de TESTE aqui
    const mp = new MercadoPago('APP_USR-b32aa1af-8eb4-4c72-9fec-8242aba7b4ca', {
        locale: 'pt-BR'
    });
    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: 30.00, // Valor da assinatura
                payer: {
                    // Pegue o email do usuário logado (ex: vindo do PHP)
                    email: "<?php echo $_SESSION['user_email']; ?>", 
                },
            },
            customization: {
                visual: {
                    style: {
                        theme: 'default', // ou 'dark', 'bootstrap'
                    }
                },
                paymentMethods: {
                    creditCard: 'all',
                    maxInstallments: 1 // Assinatura geralmente não tem parcelamento
                }
            },
            callbacks: {
                onReady: () => {
                    // Callback quando o Brick está pronto
                },
                onSubmit: (cardFormData) => {
                    // Callback quando o usuário clica em "Assinar Agora"
                    // É aqui que o token do cartão é gerado
                    
                    // Mostra o spinner e desabilita o botão
                    document.getElementById('loading-spinner').style.display = 'block';
                    document.getElementById('form-checkout__submit').disabled = true;

                    // O 'cardFormData' contém o token e outros dados.
                    // Nós não o enviamos para o MP daqui.
                    // Nós o enviamos para o *nosso* backend (processar_assinatura.php).
                    
                    // Este é o fluxo recomendado:
                    // 1. Criar o 'card_token'
                    // 2. Enviar o 'card_token' para o seu backend
                    // 3. Seu backend cria a assinatura
                    
                    // Em vez de retornar uma Promise, vamos criar o token e enviar o form
                    // Esta é uma abordagem mais simples que o 'fetch'
                    
                    // Preenche os campos ocultos
                    document.getElementById('card_token').value = cardFormData.token;
                    document.getElementById('payer_email').value = cardFormData.payer.email;
                    
                    // Envia o formulário para o seu backend
                    document.getElementById('form-checkout').submit();

                    // Se fosse uma SPA, você usaria fetch:
                    /*
                    return new Promise((resolve, reject) => {
                        fetch("actions/processar_assinatura.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify(cardFormData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Sucesso! Redireciona o usuário
                            window.location.href = 'pages/perfil.php?status=success';
                            resolve();
                        })
                        .catch(error => {
                            // Erro! Habilita o botão e mostra erro
                            document.getElementById('loading-spinner').style.display = 'none';
                            document.getElementById('form-checkout__submit').disabled = false;
                            alert('Erro ao processar assinatura.');
                            reject();
                        });
                    });
                    */
                },
                onError: (error) => {
                    // Callback de erro (ex: cartão inválido)
                    console.error(error);
                    alert('Houve um erro com seus dados. Verifique e tente novamente.');
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
// ...
?>