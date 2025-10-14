<?php 
// Inclua o arquivo da calculadora DEPOIS do ícone.
// O ideal é que ele fique no final do body.
include_once 'calculadora.php'; 
?>
</body>
<div style="position: fixed; bottom: 30px; left: 30px; z-index: 1002;">
  <a href="#" id="abrir-calculadora" title="Abrir Calculadora" style="font-size: 24px; text-decoration: none;">
    &#128290; </a>
</div>


<script>
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

// Garante que o label esteja correto ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('exportStatusPagar')) {
        updateDateLabel('Pagar');
    }
    if (document.getElementById('exportStatusReceber')) {
        updateDateLabel('Receber');
    }
});

</script>

<script>
  // Script para abrir a calculadora
  document.getElementById('abrir-calculadora').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('calculadora-container').style.display = 'block';
  });
</script>



</html>
