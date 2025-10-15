<style>
    /* Estilos do Rodapé Principal */
    .footer {
        background-color: #222;
        color: #aaa;
        padding: 15px 0;
        text-align: center;
        position: fixed;
        bottom: 0;
        width: 100%;
        font-size: 0.9em;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.3);
        z-index: 1000; /* Garante que o rodapé fique acima de outros elementos */
    }
    .footer a {
        color: #0af;
        text-decoration: none;
        margin: 0 10px;
    }
    .footer a:hover {
        text-decoration: underline;
    }

    /* Estilos para os Ícones Flutuantes de Ação */
    .action-buttons {
        position: fixed;
        bottom: 80px; /* Posição acima do rodapé */
        left: 20px;
        z-index: 1002;
        display: flex;
        flex-direction: column; /* Ícones empilhados verticalmente */
        gap: 10px;
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

<footer class="footer">
    <p>
        © <?php echo date("Y"); ?> App Controle de Contas. Todos os direitos reservados.
        <a href="tutorial.php">Tutorial</a>
        <a href="protecao_de_dados.php">Proteção de Dados</a>
    </p>
</footer>

<div class="action-buttons">
    <a href="#" id="abrir-calculadora" title="Abrir Calculadora">
        <i class="fas fa-calculator"></i>
    </a>
    <a href="#" id="abrir-calendario" title="Abrir Calendário">
        <i class="fas fa-calendar-alt"></i>
    </a>
</div>

<script>
    // --- SCRIPTS GERAIS ---
    
    const defaultFontSize = 16;

    function adjustFontSize(change) {
        const body = document.body;
        const style = window.getComputedStyle(body).getPropertyValue('font-size');
        let fontSize = parseFloat(style);
        fontSize += change;
        if (fontSize < 12) fontSize = 12;
        if (fontSize > 24) fontSize = 24;
        body.style.fontSize = fontSize + 'px';
    }

    function resetFontSize() {
        document.body.style.fontSize = defaultFontSize + 'px';
    }

    function updateDateLabel(type) {
        const statusSelect = document.getElementById('exportStatus' + type);
        const labelInicio = document.getElementById('dateLabelInicio' + type);
        const labelFim = document.getElementById('dateLabelFim' + type);

        if (!statusSelect || !labelInicio || !labelFim) {
            return;
        }

        const selectedStatus = statusSelect.value;
        let labelText = 'De (Data de Vencimento):';
        let labelTextFim = 'Até (Data de Vencimento):';

        if (selectedStatus === 'baixada') {
            labelText = 'De (Data de Baixa):';
            labelTextFim = 'Até (Data de Baixa):';
        }

        labelInicio.textContent = labelText;
        labelFim.textContent = labelTextFim;
    }

    function validateExportForm(form) {
        const dataInicio = form.querySelector('input[name="data_inicio"]').value;
        const dataFim = form.querySelector('input[name="data_fim"]').value;

        if (dataInicio && dataFim && dataFim < dataInicio) {
            alert('A data final não pode ser anterior à data inicial.');
            return false;
        }
        return true;
    }

    // Garante que os scripts sejam executados após o carregamento completo da página
    document.addEventListener('DOMContentLoaded', function() {
        // Atualiza labels de data nos formulários de exportação
        if (document.getElementById('exportStatusPagar')) {
            updateDateLabel('Pagar');
        }
        if (document.getElementById('exportStatusReceber')) {
            updateDateLabel('Receber');
        }

        // --- EVENT LISTENERS PARA ÍCONES FLUTUANTES ---

        // Script para abrir a calculadora
        const abrirCalculadoraBtn = document.getElementById('abrir-calculadora');
        if (abrirCalculadoraBtn) {
            abrirCalculadoraBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const calculadoraContainer = document.getElementById('calculadora-container');
                if (calculadoraContainer) {
                    calculadoraContainer.style.display = 'block';
                }
            });
        }

        // Script para abrir o calendário
        const abrirCalendarioBtn = document.getElementById('abrir-calendario');
        if (abrirCalendarioBtn) {
            abrirCalendarioBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const calendarioContainer = document.getElementById('calendario-container');
                if (calendarioContainer) {
                    calendarioContainer.style.display = 'block';
                }
            });
        }
    });
</script>

<?php
// Inclui os componentes da calculadora e do calendário no final do body
include_once 'calculadora.php';
include_once 'calendario.php';
?>

</body>
</html>