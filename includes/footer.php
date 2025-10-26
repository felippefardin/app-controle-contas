<?php include('../pages/calculadora.php'); ?>
<?php include('../pages/calendario.php'); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

 </main>

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

  <style>
    .footer {
        background-color: #222;
        color: #aaa;
        padding: 15px 0;
        text-align: center;
        width: 100%;
        font-size: 0.9em;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.3);
        margin-top: auto;
    }

    .footer a {
        color: #0af;
        text-decoration: none;
        margin: 0 10px;
    }
    .footer a:hover { text-decoration: underline; }

    .action-buttons {
        position: fixed;
        bottom: 80px;
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
    let mainElement = document.querySelector('main');
    let fontSize = 100;
    function adjustFontSize(amount) {
      fontSize += amount * 10;
       if (mainElement) {
        mainElement.style.fontSize = fontSize + '%';
      }
    }
    function resetFontSize() {
      fontSize = 100;
       if (mainElement) {
        mainElement.style.fontSize = '100%';
      }
    }
   
// Botões flutuantes
const botaoCalc = document.getElementById('abrir-calculadora');
const botaoCal = document.getElementById('abrir-calendario');

// Containers das janelas
const calcContainer = document.getElementById('calculadora-container');
const calContainer = document.getElementById('calendario-container');

// Abertura
botaoCalc.addEventListener('click', (e) => {
  e.preventDefault();
  fecharAmbos();
  calcContainer.style.display = 'block';
});

botaoCal.addEventListener('click', (e) => {
  e.preventDefault();
  fecharAmbos();
  calContainer.style.display = 'block';
});

// Fecha uma ao abrir outra
function fecharAmbos() {
  calcContainer.style.display = 'none';
  calContainer.style.display = 'none';
}


  </script>

</body>
</html>