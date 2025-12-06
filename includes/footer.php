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

<footer>
  <p>
      &copy; <?= date("Y") ?> App Controle de Contas. Desenvolvido por Felippe Fardin.
      <a href="../pages/tutorial.php">Tutorial</a>
      <a href="../pages/suporte.php">Suporte</a>
      <a href="../pages/protecao_de_dados.php">Proteção de Dados</a>
  </p>
</footer>

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
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Funções de ajuste de fonte do header (CORRIGIDO)
    function adjustFontSize(amount) {
        // Seleciona tanto o elemento raiz (html) quanto o corpo (body)
        // Isso garante que unidades 'rem' (html) e 'em/px herdados' (body) sejam afetados
        const elements = [document.documentElement, document.body];
        
        elements.forEach(el => {
            // Pega o tamanho atual computado
            let currentSize = parseFloat(window.getComputedStyle(el, null).getPropertyValue('font-size'));
            
            // Se não conseguir ler (NaN), define um padrão de 16px
            if (!currentSize) currentSize = 16;
            
            // Aplica o novo tamanho
            el.style.fontSize = (currentSize + amount) + 'px';
        });
    }

    function resetFontSize() {
        // Remove o estilo inline para voltar ao definido no CSS
        document.documentElement.style.fontSize = '';
        document.body.style.fontSize = '';
    }

    // Script para os botões flutuantes (Calculadora e Calendário) - MANTIDO IGUAL
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
</script>
</body>
</html>