<div id="calculadora-container" style="display:none;">
  <div id="calculadora-header">
    Calculadora
    <span id="fechar-calculadora">×</span>
  </div>
  <div id="calculadora">
    <input type="text" id="display" disabled>
    <div class="botoes">
      <button onclick="limparDisplay()" class="operador">C</button>
      <button onclick="adicionarAoDisplay('/')" class="operador">/</button>
      <button onclick="adicionarAoDisplay('*')" class="operador">*</button>
      <button onclick="adicionarAoDisplay('-')" class="operador">-</button>

      <button onclick="adicionarAoDisplay('7')">7</button>
      <button onclick="adicionarAoDisplay('8')">8</button>
      <button onclick="adicionarAoDisplay('9')">9</button>
      <button onclick="adicionarAoDisplay('+')" class="operador" style="grid-row: span 2;">+</button>

      <button onclick="adicionarAoDisplay('4')">4</button>
      <button onclick="adicionarAoDisplay('5')">5</button>
      <button onclick="adicionarAoDisplay('6')">6</button>
      
      <button onclick="adicionarAoDisplay('1')">1</button>
      <button onclick="adicionarAoDisplay('2')">2</button>
      <button onclick="adicionarAoDisplay('3')">3</button>
      <button class="igual" onclick="calcular()" style="grid-row: span 2;">=</button>

      <button class="zero" onclick="adicionarAoDisplay('0')">0</button>
      <button onclick="adicionarAoDisplay('.')">.</button>
    </div>
  </div>
</div>

<style>
  /* O CSS permanece o mesmo da sua versão anterior */
  #calculadora-container {
    position: fixed;
    top: 100px;
    left: 100px;
    width: 260px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 1000;
    background-color: #1e1e1e;
    color: #fff;
    font-family: Arial, sans-serif;
    border: 2px solid #1e1e1e; /* Borda padrão */
    transition: border-color 0.3s;
  }

  /* Estilo para quando a calculadora estiver ativa */
  #calculadora-container.ativa {
    border-color: #0af; /* Borda azul para indicar foco */
  }

  #calculadora-header {
    padding: 10px;
    cursor: move;
    background-color: #272727;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
    font-weight: bold;
  }

  #fechar-calculadora { cursor:pointer; font-size:18px; font-weight:bold; }
  #calculadora { padding: 10px; }
  #display {
    width: 93%;
    margin-bottom: 10px;
    padding: 10px;
    font-size: 1.4em;
    text-align: right;
    border-radius: 6px;
    border: none;
    background-color: #2b2b2b;
    color: #fff;
  }
  .botoes {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
  }
  .botoes button {
    padding: 15px;
    font-size: 1.1em;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background-color: #3a3a3a;
    color: #fff;
    transition: 0.2s;
  }
  .botoes button:hover { background-color: #555; }
  .botoes button.operador { background-color: #ff9500; color: #fff; }
  .botoes button.operador:hover { background-color: #e08900; }
  .botoes button.igual { background-color: #0af; color: #fff; }
  .botoes button.igual:hover { background-color: #0077cc; }
  .botoes button.zero { grid-column: span 2; }
</style>

<script>
const display = document.getElementById('display');
const container = document.getElementById('calculadora-container');
const header = document.getElementById('calculadora-header');

let offsetX, offsetY;
let calculadoraAtiva = false; // Flag para controlar a interatividade

// Funções da calculadora agora verificam se ela está ativa
function adicionarAoDisplay(valor) { 
  if (!calculadoraAtiva) return; 
  display.value += valor; 
}
function limparDisplay() { 
  if (!calculadoraAtiva) return; 
  display.value = ''; 
}
function calcular() {
  if (!calculadoraAtiva) return; 
  try { 
    display.value = eval(display.value); 
  }
  catch { 
    display.value = 'Erro'; 
  }
}

// --- LÓGICA DE ATIVAÇÃO E DESATIVAÇÃO ---

// Ativar calculadora ao clicar em qualquer parte dela
container.addEventListener('click', (e) => {
  if (!calculadoraAtiva) {
    calculadoraAtiva = true;
    container.classList.add('ativa'); // Adiciona classe para feedback visual
  }
  e.stopPropagation(); // Impede que o clique se propague para o document
});

// Desativar calculadora ao clicar fora dela
document.addEventListener('click', () => {
  if (calculadoraAtiva) {
    calculadoraAtiva = false;
    container.classList.remove('ativa'); // Remove a classe
  }
});


// --- LÓGICA DE MOVIMENTAÇÃO E FECHAMENTO ---

// Movimentação da calculadora
header.addEventListener('mousedown', e => {
  e.preventDefault();
  offsetX = e.clientX - container.offsetLeft;
  offsetY = e.clientY - container.offsetTop;
  document.addEventListener('mousemove', mover);
  document.addEventListener('mouseup', pararMover);
});

function mover(e) {
  container.style.left = (e.clientX - offsetX) + 'px';
  container.style.top = (e.clientY - offsetY) + 'px';
}
function pararMover() {
  document.removeEventListener('mousemove', mover);
  document.removeEventListener('mouseup', pararMover);
}

// Fechar calculadora
document.getElementById('fechar-calculadora').addEventListener('click', (e) => {
  container.style.display = 'none';
  calculadoraAtiva = false; // Garante que a calculadora seja desativada ao fechar
  container.classList.remove('ativa');
  e.stopPropagation(); // Impede que o clique ative a calculadora novamente
});

// Interação com o Teclado
document.addEventListener('keydown', (e) => {
  // Só funciona se a calculadora estiver visível e ativa
  if (container.style.display === 'none' || !calculadoraAtiva) return;
  
  if (e.key >= '0' && e.key <= '9') adicionarAoDisplay(e.key);
  else if (['+', '-', '*', '/','.'].includes(e.key)) adicionarAoDisplay(e.key);
  else if (e.key === 'Enter' || e.key === '=') calcular();
  else if (e.key === 'Backspace') display.value = display.value.slice(0, -1);
  else if (e.key === 'Escape' || e.key.toLowerCase() === 'c') limparDisplay();
});
</script>