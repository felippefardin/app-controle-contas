<?php
// pages/assinar.php
require_once '../includes/session_init.php';
include('../includes/header.php');
?>

<style>
    /* CSS estilo Registro para os cards */
    .planos-wrapper { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; padding: 20px 0; }
    .plano-card {
        background: #1f1f1f;
        border: 1px solid #333;
        border-radius: 12px;
        padding: 30px;
        width: 300px;
        text-align: center;
        transition: transform 0.3s, border-color 0.3s;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .plano-card:hover { transform: translateY(-5px); border-color: #00bfff; box-shadow: 0 5px 20px rgba(0, 191, 255, 0.15); }
    .plano-card.destaque { border: 2px solid #00bfff; background: #222; transform: scale(1.05); z-index: 10; }
    .plano-header { margin-bottom: 20px; border-bottom: 1px solid #444; padding-bottom: 20px; }
    .plano-title { font-size: 1.5rem; font-weight: bold; color: #fff; }
    .plano-price { font-size: 2rem; color: #28a745; font-weight: 800; margin: 10px 0; }
    .plano-features { list-style: none; padding: 0; margin: 0 0 20px 0; text-align: left; flex-grow: 1; }
    .plano-features li { margin-bottom: 10px; color: #ccc; font-size: 0.95rem; display: flex; align-items: center; gap: 10px; }
    .plano-features li i { color: #00bfff; }
    .btn-plano { width: 100%; padding: 12px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; transition: background 0.3s; }
    .btn-outline { background: transparent; border: 2px solid #00bfff; color: #00bfff; }
    .btn-outline:hover { background: #00bfff; color: #fff; }
    .btn-primary-custom { background: linear-gradient(135deg, #00bfff, #008cba); color: white; }
    .btn-primary-custom:hover { filter: brightness(1.1); }
    .badge-pop { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #ffc107; color: #000; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }
    
    /* Area Cupom */
    .cupom-area { max-width: 400px; margin: 0 auto 30px auto; display: flex; gap: 10px; justify-content: center; }
    .cupom-input { padding: 10px; border-radius: 5px; border: 1px solid #444; background: #1c1c1c; color: #fff; width: 100%; text-transform: uppercase; }
    .btn-cupom { background: #ff9f43; border: none; border-radius: 5px; padding: 0 20px; font-weight: bold; cursor: pointer; color: #000; }
    .msg-cupom { text-align: center; font-size: 0.9rem; margin-top: -20px; margin-bottom: 20px; min-height: 20px; }
    .old-price { text-decoration: line-through; font-size: 1rem; color: #777; display: block; }
</style>

<div class="container">
    <div class="text-center mt-5 mb-4">
        <h2 style="color: #fff;">Escolha o Plano Ideal</h2>
        <p style="color: #aaa;">Desbloqueie o potencial máximo do seu negócio.</p>
    </div>

    <div class="cupom-area">
        <input type="text" id="inputCupom" class="cupom-input" placeholder="Tem um cupom de desconto?">
        <button onclick="aplicarCupom()" class="btn-cupom">Aplicar</button>
    </div>
    <div class="msg-cupom" id="msgCupom"></div>

    <div class="planos-wrapper">
        
        <div class="plano-card">
            <div class="plano-header">
                <div class="plano-title">Básico</div>
                <div class="plano-price" data-original="19.90">
                    R$ 19,90<small style="font-size: 1rem; color: #aaa">/mês</small>
                </div>
                <div style="color: #888; font-size: 0.9rem;">Ideal para começar</div>
            </div>
            <ul class="plano-features">
                <li><i class="fa-solid fa-check"></i> 1 Usuário Admin</li>
                <li><i class="fa-solid fa-check"></i> 2 Usuários Padrão</li>
                <li><i class="fa-solid fa-check"></i> Gestão Financeira</li>
                <li><i class="fa-solid fa-check"></i> Relatórios Básicos</li>
            </ul>
            <form action="../actions/checkout_plano.php" method="POST">
                <input type="hidden" name="plano" value="basico">
                <input type="hidden" name="cupom" class="hidden-cupom-field">
                <button type="submit" class="btn-plano btn-outline">Assinar Básico</button>
            </form>
        </div>

        <div class="plano-card destaque">
            <span class="badge-pop">Mais Popular</span>
            <div class="plano-header">
                <div class="plano-title" style="color: #00bfff;">Plus</div>
                <div class="plano-price" data-original="39.90">
                    R$ 39,90<small style="font-size: 1rem; color: #aaa">/mês</small>
                </div>
                <div style="color: #888; font-size: 0.9rem;">Para crescer rápido</div>
            </div>
            <ul class="plano-features">
                <li><i class="fa-solid fa-check"></i> 1 Usuário Admin</li>
                <li><i class="fa-solid fa-check"></i> 5 Usuários Padrão</li>
                <li><i class="fa-solid fa-check"></i> Tudo do Básico</li>
                <li><i class="fa-solid fa-check"></i> Suporte Prioritário</li>
                <li><i class="fa-solid fa-check"></i> +15 Dias Grátis</li>
            </ul>
            <form action="../actions/checkout_plano.php" method="POST">
                <input type="hidden" name="plano" value="plus">
                <input type="hidden" name="cupom" class="hidden-cupom-field">
                <button type="submit" class="btn-plano btn-primary-custom">Assinar Plus</button>
            </form>
        </div>

        <div class="plano-card">
            <div class="plano-header">
                <div class="plano-title">Essencial</div>
                <div class="plano-price" data-original="59.90">
                    R$ 59,90<small style="font-size: 1rem; color: #aaa">/mês</small>
                </div>
                <div style="color: #888; font-size: 0.9rem;">Controle total</div>
            </div>
            <ul class="plano-features">
                <li><i class="fa-solid fa-check"></i> 1 Usuário Admin</li>
                <li><i class="fa-solid fa-check"></i> 15 Usuários Padrão</li>
                <li><i class="fa-solid fa-check"></i> Controle de Estoque</li>
                <li><i class="fa-solid fa-check"></i> Gestão de Equipe</li>
                <li><i class="fa-solid fa-check"></i> +30 Dias Grátis</li>
            </ul>
            <form action="../actions/checkout_plano.php" method="POST">
                <input type="hidden" name="plano" value="essencial">
                <input type="hidden" name="cupom" class="hidden-cupom-field">
                <button type="submit" class="btn-plano btn-outline">Assinar Essencial</button>
            </form>
        </div>

    </div>
    
    <div class="text-center mt-4">
        <p style="color: #666;">
            <i class="fa-solid fa-lock"></i> Pagamento seguro via Mercado Pago. Cancele quando quiser.<br>
            Precisa de mais usuários? Adicione avulso por R$ 1,50/mês na gestão da assinatura.
        </p>
    </div>
</div>

<script>
function aplicarCupom() {
    const codigo = document.getElementById('inputCupom').value;
    const msgDiv = document.getElementById('msgCupom');
    
    if (!codigo) {
        msgDiv.innerHTML = '<span style="color: #e74c3c;">Digite um código.</span>';
        return;
    }

    msgDiv.innerHTML = '<span style="color: #aaa;">Verificando...</span>';

    const formData = new FormData();
    formData.append('codigo', codigo);

    fetch('../actions/validar_cupom_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            msgDiv.innerHTML = '<span style="color: #2ecc71;">Cupom ' + data.codigo + ' aplicado com sucesso!</span>';
            
            // Atualiza inputs hidden nos formulários
            document.querySelectorAll('.hidden-cupom-field').forEach(input => {
                input.value = data.codigo;
            });

            // Atualiza UI visualmente
            document.querySelectorAll('.plano-price').forEach(el => {
                let original = parseFloat(el.getAttribute('data-original'));
                let novoPreco = original;

                if (data.tipo === 'porcentagem') {
                    novoPreco = original - (original * (data.valor / 100));
                } else if (data.tipo === 'fixo') {
                    novoPreco = Math.max(0, original - data.valor);
                }

                // Formata para PT-BR
                let precoFormatado = novoPreco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                el.innerHTML = `<span class="old-price">R$ ${original.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
                                R$ ${precoFormatado}<small style="font-size: 1rem; color: #aaa">/mês</small>`;
            });

        } else {
            msgDiv.innerHTML = '<span style="color: #e74c3c;">' + data.msg + '</span>';
            // Reseta
            document.querySelectorAll('.hidden-cupom-field').forEach(input => input.value = '');
            document.querySelectorAll('.plano-price').forEach(el => {
                let original = parseFloat(el.getAttribute('data-original'));
                let originalFmt = original.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                el.innerHTML = `R$ ${originalFmt}<small style="font-size: 1rem; color: #aaa">/mês</small>`;
            });
        }
    })
    .catch(err => {
        console.error(err);
        msgDiv.innerHTML = '<span style="color: #e74c3c;">Erro ao validar cupom.</span>';
    });
}
</script>

<?php include('../includes/footer.php'); ?>