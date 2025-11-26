</main>

<?php
// Inclui os componentes da calculadora e do calendário que ficarão ocultos
// Verifica se os arquivos existem antes de incluir para evitar erros
if (file_exists(__DIR__ . '/../pages/calculadora.php')) {
    include(__DIR__ . '/../pages/calculadora.php');
}
if (file_exists(__DIR__ . '/../pages/calendario.php')) {
    include(__DIR__ . '/../pages/calendario.php');
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="action-buttons">
    <a href="https://wa.me/5527999642716" target="_blank" title="Suporte via WhatsApp" style="background-color: #25D366;">
        <i class="fab fa-whatsapp"></i>
    </a>
    
    <a href="#" id="abrir-calculadora" title="Abrir Calculadora">
        <i class="fas fa-calculator"></i>
    </a>
    <a href="#" id="abrir-calendario" title="Abrir Calendário">
        <i class="fas fa-calendar-alt"></i>
    </a>
</div>

<div class="floating-icon-footer" data-bs-toggle="modal" data-bs-target="#modalFeedbackFooter">
    <i class="fa-solid fa-comment-dots"></i>
    <span class="tooltip-footer">Deixe seu feedback</span>
</div>

<footer>
  <p>
      &copy; <?= date("Y") ?> App Controle de Contas. Desenvolvido por Felippe Fardin.
      <a href="../pages/tutorial.php">Tutorial</a>
      <a href="../pages/suporte.php">Suporte</a>
      <a href="../pages/protecao_de_dados.php">Proteção de Dados</a>
  </p>
</footer>

<div class="modal fade" id="modalFeedbackFooter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1f1f1f; color: #eee; border: 1px solid #333;">
            <div class="modal-header" style="border-bottom: 1px solid #333;">
                <h5 class="modal-title" style="color: #ffc107;"><i class="fa-solid fa-star"></i> Enviar Feedback</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formFeedbackFooter">
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="anonimoFeedFooter" name="anonimo">
                        <label class="form-check-label" for="anonimoFeedFooter" style="color: #aaa;">Enviar Anonimamente</label>
                    </div>

                    <div id="dadosIdentificacaoFooter">
                        <div class="mb-3">
                            <label class="form-label" style="color: #ccc;">Nome</label>
                            <input type="text" name="nome" class="form-control" style="background: #252525; border: 1px solid #444; color: #fff;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: #ccc;">Email</label>
                            <input type="email" name="email" class="form-control" style="background: #252525; border: 1px solid #444; color: #fff;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: #ccc;">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" style="background: #252525; border: 1px solid #444; color: #fff;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #ccc;">Avaliação</label>
                        <select name="pontuacao" class="form-select" style="background: #252525; border: 1px solid #444; color: #fff;">
                            <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                            <option value="4">⭐⭐⭐⭐ Muito Bom</option>
                            <option value="3">⭐⭐⭐ Bom</option>
                            <option value="2">⭐⭐ Regular</option>
                            <option value="1">⭐ Ruim</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="color: #ccc;">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="3" required style="background: #252525; border: 1px solid #444; color: #fff;" placeholder="Conte sua experiência..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #333;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-warning" onclick="enviarFeedbackFooter()" style="color: #000; font-weight: bold;">Enviar Feedback</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para o rodapé fixo */
    footer {
        background-color: #1f1f1f;
        color: #aaa;
        text-align: center;
        padding: 15px 20px;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.3);
        font-size: 0.9em;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        box-sizing: border-box;
    }

    footer p { margin: 0; }

    footer a {
        color: #0af;
        text-decoration: none;
        margin: 0 10px;
    }
    footer a:hover { text-decoration: underline; }

    /* Estilos para os botões de ação flutuantes (Esquerda) */
    .action-buttons {
        position: fixed;
        bottom: 70px; 
        left: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 1002;
    }
    .action-buttons a {
        background-color: #007bff;
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 22px;
        text-decoration: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .action-buttons a:hover {
        background-color: #0056b3;
        transform: scale(1.1);
    }

    /* Estilos para o botão de Feedback (Direita) */
    .floating-icon-footer {
        position: fixed;
        bottom: 70px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #ffc107; /* Amarelo destaque */
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        cursor: pointer;
        z-index: 1002;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        transition: all 0.3s;
    }
    .floating-icon-footer:hover {
        transform: scale(1.1);
        background: #e0a800;
    }
    .tooltip-footer {
        position: absolute;
        right: 60px;
        background: #333;
        color: #fff;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: 0.3s;
        pointer-events: none;
    }
    .floating-icon-footer:hover .tooltip-footer {
        opacity: 1;
        visibility: visible;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Funções de ajuste de fonte do header (mantidas)
    function adjustFontSize(amount) {
        const body = document.body;
        let currentSize = parseFloat(window.getComputedStyle(body, null).getPropertyValue('font-size'));
        body.style.fontSize = (currentSize + amount) + 'px';
    }

    function resetFontSize() {
        document.body.style.fontSize = '';
    }

    // Script para os botões flutuantes (Calculadora e Calendário)
    document.addEventListener('DOMContentLoaded', () => {
        const botaoCalc = document.getElementById('abrir-calculadora');
        const botaoCal = document.getElementById('abrir-calendario');
        const calcContainer = document.getElementById('calculadora-container');
        const calContainer = document.getElementById('calendario-container');

        if (botaoCalc && botaoCal && calcContainer && calContainer) {
            const fecharAmbos = () => {
                calcContainer.style.display = 'none';
                calContainer.style.display = 'none';
            };

            botaoCalc.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const isCalculadoraVisivel = calcContainer.style.display === 'block';
                fecharAmbos();
                if (!isCalculadoraVisivel) {
                    calcContainer.style.display = 'block';
                }
            });

            botaoCal.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const isCalendarioVisivel = calContainer.style.display === 'block';
                fecharAmbos();
                if (!isCalendarioVisivel) {
                    calContainer.style.display = 'block';
                }
            });
        }
    });

    // Script para o Modal de Feedback
    document.addEventListener('DOMContentLoaded', () => {
        const checkAnonimo = document.getElementById('anonimoFeedFooter');
        const dadosIdentificacao = document.getElementById('dadosIdentificacaoFooter');

        if(checkAnonimo && dadosIdentificacao) {
            checkAnonimo.addEventListener('change', function() {
                if (this.checked) {
                    dadosIdentificacao.style.display = 'none';
                    dadosIdentificacao.querySelectorAll('input').forEach(input => input.value = '');
                } else {
                    dadosIdentificacao.style.display = 'block';
                }
            });
        }
    });

    function enviarFeedbackFooter() {
        const form = document.getElementById('formFeedbackFooter');
        if(!form) return;

        const formData = new FormData(form);
        
        // Botão com loading
        const btnEnviar = document.querySelector('#modalFeedbackFooter .modal-footer button.btn-warning');
        const textoOriginal = btnEnviar.innerText;
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        fetch('../actions/enviar_feedback_publico.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                // SweetAlert ou alert padrão
                if(typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Sucesso!', text: data.msg, background: '#1f1f1f', color: '#fff' });
                } else {
                    alert(data.msg);
                }
                
                form.reset();
                
                // Fecha o modal usando a instância do Bootstrap
                const modalEl = document.getElementById('modalFeedbackFooter');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
            } else {
                alert(data.msg || 'Erro ao enviar feedback.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão ao enviar feedback.');
        })
        .finally(() => {
            btnEnviar.disabled = false;
            btnEnviar.innerText = textoOriginal;
        });
    }
</script>

<script type="text/javascript">
// var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
// (function(){
// var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
// s1.async=true;
// s1.src='https://embed.tawk.to/692252545a6d17195e8d14ce/1jan1ihtl';
// s1.charset='UTF-8';
// s1.setAttribute('crossorigin','*');
// s0.parentNode.insertBefore(s1,s0);
// })();
</script>
</body>
</html>