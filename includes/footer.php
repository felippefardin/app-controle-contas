</main>

<?php
// Inclui os componentes da calculadora e do calendário que ficarão ocultos
include(__DIR__ . '/../pages/calculadora.php');
include(__DIR__ . '/../pages/calendario.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="action-buttons">
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

    footer p {
        margin: 0;
    }

    footer a {
        color: #0af;
        text-decoration: none;
        margin: 0 10px;
    }
    footer a:hover { text-decoration: underline; }

    /* Estilos para os botões de ação flutuantes */
    .action-buttons {
        position: fixed;
        bottom: 70px; /* Ajustado para ficar acima do novo footer */
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

    // Script para os botões flutuantes (adicionado)
    document.addEventListener('DOMContentLoaded', () => {
        const botaoCalc = document.getElementById('abrir-calculadora');
        const botaoCal = document.getElementById('abrir-calendario');
        const calcContainer = document.getElementById('calculadora-container');
        const calContainer = document.getElementById('calendario-container');

        // Garante que os containers existem antes de adicionar eventos
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