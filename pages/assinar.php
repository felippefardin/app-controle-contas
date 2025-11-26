<?php
// pages/assinar.php
require_once '../includes/session_init.php';
include('../includes/header.php');

$msg_erro = $_SESSION['erro_assinatura'] ?? '';
unset($_SESSION['erro_assinatura']);

$plano_selecionado = $_GET['plano_selecionado'] ?? 'basico';
?>

<style>
    .planos-wrapper { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; padding: 20px 0; }
    .plano-card {
        background: #1f1f1f; border: 1px solid #333; border-radius: 12px; padding: 30px; width: 300px;
        text-align: center; transition: transform 0.3s, border-color 0.3s; position: relative; display: flex; flex-direction: column;
    }
    .plano-card:hover { transform: translateY(-5px); border-color: #00bfff; box-shadow: 0 5px 20px rgba(0, 191, 255, 0.15); }
    .plano-card.destaque { border: 2px solid #00bfff; background: #222; transform: scale(1.05); z-index: 10; }
    
    .plano-header { margin-bottom: 20px; border-bottom: 1px solid #444; padding-bottom: 20px; }
    .plano-title { font-size: 1.5rem; font-weight: bold; color: #fff; }
    .plano-price { font-size: 2rem; color: #28a745; font-weight: 800; margin: 10px 0; }
    .old-price { text-decoration: line-through; font-size: 1rem; color: #777; display: block; }
    .plano-features { list-style: none; padding: 0; margin: 0 0 20px 0; text-align: left; flex-grow: 1; }
    .plano-features li { margin-bottom: 10px; color: #ccc; font-size: 0.95rem; display: flex; align-items: center; gap: 10px; }
    .plano-features li i { color: #00bfff; }
    .btn-plano { width: 100%; padding: 12px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; transition: background 0.3s; }
    .btn-outline { background: transparent; border: 2px solid #00bfff; color: #00bfff; }
    .btn-outline:hover { background: #00bfff; color: #fff; }
    .btn-primary-custom { background: linear-gradient(135deg, #00bfff, #008cba); color: white; }
    .extra-options-container { max-width: 500px; margin: 0 auto 30px auto; display: flex; flex-direction: column; gap: 15px; }
    .option-box { background: #252525; padding: 15px; border-radius: 8px; border: 1px solid #333; }
    .option-title { color: #fff; font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; cursor: pointer; }
    .option-content { display: none; margin-top: 10px; }
    .option-content.active { display: block; }
    .input-row { display: flex; gap: 10px; }
    .custom-input { padding: 10px; border-radius: 5px; border: 1px solid #444; background: #1c1c1c; color: #fff; width: 100%; }
    .btn-check { padding: 10px 15px; border-radius: 5px; border: none; background: #6c757d; color: white; font-weight: bold; cursor: pointer; white-space: nowrap; }
    .valid-border { border-color: #2ecc71 !important; box-shadow: 0 0 5px rgba(46, 204, 113, 0.5); }
    .invalid-border { border-color: #e74c3c !important; }
    .msg-feedback { font-size: 0.85rem; margin-top: 8px; font-weight: bold; text-align: center; }
    .text-success { color: #2ecc71; }
    .text-error { color: #e74c3c; }
    .text-promo { color: #f1c40f; display: block; margin-top: 5px; font-size: 0.85rem; }
    .badge-pop { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #ffc107; color: #000; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }
</style>

<div class="container">
    <div class="text-center mt-5 mb-4">
        <h2 style="color: #fff;">Escolha ou Renove seu Plano</h2>
        <?php if($msg_erro): ?>
            <div style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; max-width: 600px; margin: 10px auto;">
                <?= htmlspecialchars($msg_erro) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="extra-options-container">
        <div class="option-box">
            <div class="option-title" onclick="toggleBox('boxCupom')"><i class="fas fa-ticket-alt"></i> Possui um Cupom?</div>
            <div id="boxCupom" class="option-content">
                <div class="input-row">
                    <input type="text" id="inputCupom" class="custom-input" placeholder="Digite o código" style="text-transform:uppercase;">
                    <button type="button" class="btn-check" onclick="validarCupom()">Aplicar</button>
                </div>
                <div id="msgCupom" class="msg-feedback"></div>
            </div>
        </div>

        <div class="option-box">
            <div class="option-title" onclick="toggleBox('boxIndicacao')"><i class="fas fa-user-friends"></i> Foi indicado por alguém?</div>
            <div id="boxIndicacao" class="option-content">
                <input type="email" id="emailIndicador" class="custom-input" placeholder="E-mail de quem indicou" style="margin-bottom: 10px;">
                <div class="input-row">
                    <input type="text" id="cpfIndicador" class="custom-input" placeholder="CPF/CNPJ (números)">
                    <button type="button" id="btnConfInd" class="btn-check" onclick="validarIndicacao()">Conferir</button>
                </div>
                <div id="msgIndicacao" class="msg-feedback"></div>
            </div>
        </div>
    </div>

    <div class="planos-wrapper">
        <div class="plano-card">
            <div class="plano-header">
                <div class="plano-title">Básico</div>
                <div class="plano-price" data-original="19.90">R$ 19,90<small>/mês</small></div>
            </div>
            <ul class="plano-features">
                <li><i class="fa-solid fa-check"></i> 3 Usuários Totais</li>
                <li><i class="fa-solid fa-check"></i> Gestão Financeira</li>
            </ul>
            <form action="../actions/checkout_plano.php" method="POST" onsubmit="prepararEnvio(this)">
                <input type="hidden" name="plano" value="basico">
                <input type="hidden" name="cupom" class="hidden-cupom">
                <input type="hidden" name="ind_email" class="hidden-ind-email">
                <input type="hidden" name="ind_doc" class="hidden-ind-doc">
                <button class="btn-plano btn-outline">Assinar Básico</button>
            </form>
        </div>

        <div class="plano-card destaque">
            <span class="badge-pop">Recomendado</span>
            <div class="plano-header">
                <div class="plano-title" style="color:#00bfff">Plus</div>
                <div class="plano-price" data-original="39.90">R$ 39,90<small>/mês</small></div>
            </div>
            <ul class="plano-features">
                <li><i class="fa-solid fa-check"></i> 6 Usuários Totais</li>
                <li><i class="fa-solid fa-check"></i> Suporte Prioritário</li>
            </ul>
            <form action="../actions/checkout_plano.php" method="POST" onsubmit="prepararEnvio(this)">
                <input type="hidden" name="plano" value="plus">
                <input type="hidden" name="cupom" class="hidden-cupom">
                <input type="hidden" name="ind_email" class="hidden-ind-email">
                <input type="hidden" name="ind_doc" class="hidden-ind-doc">
                <button class="btn-plano btn-primary-custom">Assinar Plus</button>
            </form>
        </div>

        <div class="plano-card">
            <div class="plano-header">
                <div class="plano-title">Essencial</div>
                <div class="plano-price" data-original="59.90">R$ 59,90<small>/mês</small></div>
            </div>
            <ul class="plano-features">
                <li><i class="fa-solid fa-check"></i> 16 Usuários Totais</li>
                <li><i class="fa-solid fa-check"></i> Controle Completo</li>
            </ul>
            <form action="../actions/checkout_plano.php" method="POST" onsubmit="prepararEnvio(this)">
                <input type="hidden" name="plano" value="essencial">
                <input type="hidden" name="cupom" class="hidden-cupom">
                <input type="hidden" name="ind_email" class="hidden-ind-email">
                <input type="hidden" name="ind_doc" class="hidden-ind-doc">
                <button class="btn-plano btn-outline">Assinar Essencial</button>
            </form>
        </div>
    </div>
</div>

<script>
let indicacaoValida = false;

function toggleBox(id) {
    document.getElementById(id).classList.toggle('active');
}

function validarCupom() {
    const codigo = document.getElementById('inputCupom').value;
    const msg = document.getElementById('msgCupom');
    if(!codigo) return;
    msg.innerHTML = '<span style="color:#ccc">Verificando...</span>';

    const formData = new FormData();
    formData.append('codigo', codigo);

    fetch('../actions/validar_cupom_api.php', { method:'POST', body:formData })
    .then(r => r.json())
    .then(data => {
        if(data.valid) {
            msg.innerHTML = `<span class="text-success">Cupom aplicado! -${data.valor}${data.tipo=='porcentagem'?'%':''}</span>`;
            document.querySelectorAll('.hidden-cupom').forEach(i => i.value = data.codigo);
            atualizarPrecos(data.tipo, data.valor);
        } else {
            msg.innerHTML = `<span class="text-error">${data.msg}</span>`;
            resetarPrecos();
        }
    });
}

function validarIndicacao() {
    const email = document.getElementById('emailIndicador').value;
    const doc = document.getElementById('cpfIndicador').value;
    const msg = document.getElementById('msgIndicacao');
    const btn = document.getElementById('btnConfInd');

    if(!email || !doc) {
        msg.innerHTML = '<span class="text-error">Preencha ambos.</span>';
        return;
    }
    
    btn.innerHTML = '...';

    const formData = new FormData();
    formData.append('email', email);
    formData.append('documento', doc);

    fetch('../actions/validar_indicacao_api.php', { method:'POST', body:formData })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = 'Conferir';
        if(data.valid) {
            indicacaoValida = true;
            document.getElementById('emailIndicador').classList.add('valid-border');
            document.getElementById('cpfIndicador').classList.add('valid-border');
            
            msg.innerHTML = `
                <span class="text-success"><i class="fas fa-check-circle"></i> Amigo: ${data.nome}</span>
                <span class="text-promo">Amigo indicado, ao finalizar ganhe 10% OFF.</span>
            `;
            atualizarPrecos('porcentagem', 10);
        } else {
            indicacaoValida = false;
            document.getElementById('emailIndicador').classList.add('invalid-border');
            document.getElementById('cpfIndicador').classList.add('invalid-border');
            msg.innerHTML = `<span class="text-error">Dados incorretos.</span>`;
            resetarPrecos();
        }
    });
}

function atualizarPrecos(tipo, valor) {
    document.querySelectorAll('.plano-price').forEach(el => {
        let original = parseFloat(el.getAttribute('data-original'));
        let novo = tipo === 'porcentagem' ? original - (original * (valor/100)) : original - valor;
        novo = Math.max(0, novo);
        el.innerHTML = `<span class="old-price">R$ ${original.toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
                        R$ ${novo.toLocaleString('pt-BR',{minimumFractionDigits:2})}<small>/mês</small>`;
    });
}

function resetarPrecos() {
    document.querySelectorAll('.plano-price').forEach(el => {
        let original = parseFloat(el.getAttribute('data-original'));
        el.innerHTML = `R$ ${original.toLocaleString('pt-BR',{minimumFractionDigits:2})}<small>/mês</small>`;
    });
}

function prepararEnvio(form) {
    if(indicacaoValida) {
        form.querySelector('.hidden-ind-email').value = document.getElementById('emailIndicador').value;
        form.querySelector('.hidden-ind-doc').value = document.getElementById('cpfIndicador').value;
    }
}
</script>

<?php include('../includes/footer.php'); ?>