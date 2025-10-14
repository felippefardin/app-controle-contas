<div style="position: fixed; bottom: 20px; left: 20px; z-index: 1002; display: flex; gap: 15px;">
    
    <a href="#" id="abrir-calculadora" title="Abrir Calculadora" style="font-size: 24px; text-decoration: none;">
        &#128290; </a>

    <a href="#" id="abrir-calendario" title="Abrir Calendário" style="font-size: 24px; text-decoration: none;">
        &#128197; </a>

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
// Isso garante que todo o HTML da página já foi carregado antes deles
include_once 'calculadora.php';
include_once 'calendario.php';
?>

</body>
</html>