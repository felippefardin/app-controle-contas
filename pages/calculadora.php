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
/* === CALCULADORA PADRÃO SISTEMA === */
#calculadora-container {
  position: fixed;
  top: 100px;
  left: 100px;
  width: 260px;
  background-color: #1e1e1e;
  color: #eee;
  font-family: "Segoe UI", Arial, sans-serif;
  border-radius: 12px;
  border: 1px solid #2a2a2a;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
  z-index: 1200;
  transition: all 0.3s ease;
}

#calculadora-container.ativa {
  border-color: #00bfff;
  box-shadow: 0 0 15px rgba(0, 191, 255, 0.5);
}

#calculadora-header {
  background-color: #2c2c2c;
  color: #00bfff;
  font-weight: bold;
  padding: 10px 14px;
  border-radius: 12px 12px 0 0;
  cursor: move;
  display: flex;
  justify-content: space-between;
  align-items: center;
  user-select: none;
}

#fechar-calculadora {
  cursor: pointer;
  color: #ccc;
  font-size: 18px;
  transition: 0.2s;
}
#fechar-calculadora:hover {
  color: #ff5555;
}

#calculadora {
  padding: 15px;
  background-color: #1e1e1e;
  border-radius: 0 0 12px 12px;
}

#display {
  width: 100%;
  padding: 12px;
  font-size: 1.4em;
  text-align: right;
  background-color: #2a2a2a;
  border: none;
  border-radius: 8px;
  color: #fff;
  margin-bottom: 12px;
  outline: none;
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
  border-radius: 8px;
  background-color: #333;
  color: #fff;
  cursor: pointer;
  transition: 0.2s ease;
}

.botoes button:hover {
  background-color: #444;
}

.botoes button.operador {
  background-color: #ff9500;
  color: #fff;
}
.botoes button.operador:hover {
  background-color: #e08900;
}

.botoes button.igual {
  background-color: #00bfff;
  color: #fff;
  font-weight: bold;
}
.botoes button.igual:hover {
  background-color: #0095cc;
}

.botoes button.zero {
  grid-column: span 2;
}

/* === RESPONSIVIDADE === */
@media (max-width: 768px) {
  #calculadora-container {
    width: 90%;
    left: 5%;
    top: 80px;
  }
  #display {
    font-size: 1.2em;
  }
  .botoes button {
    padding: 12px;
    font-size: 1em;
  }
}
</style>

<script>
const display = document.getElementById('display');
const container = document.getElementById('calculadora-container');
const header = document.getElementById('calculadora-header');

let offsetX, offsetY;
let calculadoraAtiva = false;

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
  } catch { 
    display.value = 'Erro'; 
  }
}

// Ativar/desativar interatividade
container.addEventListener('click', (e) => {
  if (!calculadoraAtiva) {
    calculadoraAtiva = true;
    container.classList.add('ativa');
  }
  e.stopPropagation();
});
document.addEventListener('click', () => {
  if (calculadoraAtiva) {
    calculadoraAtiva = false;
    container.classList.remove('ativa');
  }
});

// Movimentação
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

// Fechar
document.getElementById('fechar-calculadora').addEventListener('click', (e) => {
  container.style.display = 'none';
  calculadoraAtiva = false;
  container.classList.remove('ativa');
  e.stopPropagation();
});

// Teclado
document.addEventListener('keydown', (e) => {
  if (container.style.display === 'none' || !calculadoraAtiva) return;
  if (e.key >= '0' && e.key <= '9') adicionarAoDisplay(e.key);
  else if (['+', '-', '*', '/','.'].includes(e.key)) adicionarAoDisplay(e.key);
  else if (e.key === 'Enter' || e.key === '=') calcular();
  else if (e.key === 'Backspace') display.value = display.value.slice(0, -1);
  else if (e.key === 'Escape' || e.key.toLowerCase() === 'c') limparDisplay();
});
</script>
