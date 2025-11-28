<div id="calculadora-container" style="display:none;">
  <div id="calculadora-header">
    <i class="fas fa-calculator" style="margin-right: 5px;"></i> Calculadora
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
/* === CALCULADORA BASE (DESKTOP) === */
#calculadora-container {
  position: fixed;
  top: 15%;
  left: 70%; /* Posicionada mais à direita por padrão */
  width: 260px;
  background-color: #1e1e1e;
  color: #eee;
  font-family: "Segoe UI", Arial, sans-serif;
  border-radius: 10px;
  border: 1px solid #444;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
  z-index: 1200;
  overflow: hidden; /* Garante que nada saia das bordas arredondadas */
}

#calculadora-container.ativa {
  border-color: #00bfff;
  box-shadow: 0 0 15px rgba(0, 191, 255, 0.4);
}

#calculadora-header {
  background-color: #2a2a2a;
  color: #00bfff;
  font-size: 14px;
  font-weight: bold;
  padding: 8px 12px;
  cursor: move; /* Cursor de movimento */
  display: flex;
  justify-content: space-between;
  align-items: center;
  user-select: none;
  border-bottom: 1px solid #333;
}

#fechar-calculadora {
  cursor: pointer;
  color: #aaa;
  font-size: 18px;
  font-weight: bold;
  line-height: 1;
}
#fechar-calculadora:hover {
  color: #ff5555;
}

#calculadora {
  padding: 10px;
  background-color: #1e1e1e;
}

#display {
  width: 100%;
  padding: 8px 10px;
  font-size: 1.4em;
  text-align: right;
  background-color: #252525;
  border: 1px solid #333;
  border-radius: 6px;
  color: #fff;
  margin-bottom: 10px;
  outline: none;
  box-sizing: border-box;
  font-family: monospace;
  height: 40px;
}

.botoes {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
}

.botoes button {
  padding: 10px 0;
  font-size: 1.1em;
  border: none;
  border-radius: 5px;
  background-color: #333;
  color: #eee;
  cursor: pointer;
  transition: background-color 0.1s;
}

.botoes button:active {
    background-color: #555;
    transform: translateY(1px);
}

.botoes button:hover {
  background-color: #444;
}

.botoes button.operador {
  background-color: #e69500;
  color: #fff;
}
.botoes button.operador:hover {
  background-color: #ffaa00;
}

.botoes button.igual {
  background-color: #007bff;
  color: #fff;
}
.botoes button.igual:hover {
  background-color: #0056b3;
}

.botoes button.zero {
  grid-column: span 2;
}

/* === RESPONSIVIDADE (TABLET E MOBILE) === */
@media (max-width: 768px) {
  #calculadora-container {
    /* Tamanho reduzido para mobile */
    width: 200px; 
    top: 100px;
    left: 20px;
  }

  #calculadora-header {
    padding: 6px 10px;
    font-size: 13px;
  }

  #display {
    font-size: 1.1em;
    height: 30px;
    margin-bottom: 6px;
  }

  .botoes {
    gap: 4px;
  }

  .botoes button {
    padding: 8px 0;
    font-size: 0.9em;
  }
}
</style>

<script>
const display = document.getElementById('display');
const container = document.getElementById('calculadora-container');
const header = document.getElementById('calculadora-header');

let offsetX, offsetY;
let calculadoraAtiva = false;

// --- FUNÇÕES DA CALCULADORA ---
function garantirAtividade() {
    if (!calculadoraAtiva) {
        calculadoraAtiva = true;
        container.classList.add('ativa');
    }
}

function adicionarAoDisplay(valor) { 
  garantirAtividade();
  display.value += valor; 
}

function limparDisplay() { 
  garantirAtividade();
  display.value = ''; 
}

function calcular() {
  garantirAtividade();
  if(!display.value) return;
  try { 
    display.value = eval(display.value); 
  } catch { 
    display.value = 'Erro'; 
  }
}

// --- ARRASTAR (MOUSE E TOQUE) ---

// Iniciar arrasto (Mouse)
header.addEventListener('mousedown', iniciarArrasto);
// Iniciar arrasto (Toque)
header.addEventListener('touchstart', iniciarArrasto, {passive: false});

function iniciarArrasto(e) {
  // Se for toque, previne rolagem da tela
  if (e.type === 'touchstart') {
      // e.preventDefault(); // Opcional: comentar se quiser permitir scroll ao tocar no header (não recomendado)
  }
  
  // Identifica coordenadas iniciais (mouse ou toque)
  const clienteX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
  const clienteY = e.type === 'mousedown' ? e.clientY : e.touches[0].clientY;

  offsetX = clienteX - container.offsetLeft;
  offsetY = clienteY - container.offsetTop;

  // Adiciona ouvintes para movimento e fim
  if (e.type === 'mousedown') {
      document.addEventListener('mousemove', moverElemento);
      document.addEventListener('mouseup', pararArrasto);
  } else {
      document.addEventListener('touchmove', moverElemento, {passive: false});
      document.addEventListener('touchend', pararArrasto);
  }
}

function moverElemento(e) {
  e.preventDefault(); // Impede scroll da tela no mobile enquanto arrasta

  const clienteX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
  const clienteY = e.type === 'mousemove' ? e.clientY : e.touches[0].clientY;

  container.style.left = (clienteX - offsetX) + 'px';
  container.style.top = (clienteY - offsetY) + 'px';
}

function pararArrasto() {
  document.removeEventListener('mousemove', moverElemento);
  document.removeEventListener('mouseup', pararArrasto);
  document.removeEventListener('touchmove', moverElemento);
  document.removeEventListener('touchend', pararArrasto);
}

// --- ESTADOS E FECHAMENTO ---

// Ativar visualmente ao clicar
container.addEventListener('click', (e) => {
  garantirAtividade();
  e.stopPropagation();
});

// Fechar
document.getElementById('fechar-calculadora').addEventListener('click', (e) => {
  container.style.display = 'none';
  calculadoraAtiva = false;
  container.classList.remove('ativa');
  e.stopPropagation(); // Previne reabrir se o botão de abrir estiver por baixo
});

// Teclado
document.addEventListener('keydown', (e) => {
  if (container.style.display === 'none') return;
  
  if (['0','1','2','3','4','5','6','7','8','9','+','-','*','/','.','Enter','=','Backspace','Escape','c'].includes(e.key)) {
      garantirAtividade();
  }

  if (e.key >= '0' && e.key <= '9') adicionarAoDisplay(e.key);
  else if (['+', '-', '*', '/','.'].includes(e.key)) adicionarAoDisplay(e.key);
  else if (e.key === 'Enter' || e.key === '=') calcular();
  else if (e.key === 'Backspace') display.value = display.value.slice(0, -1);
  else if (e.key === 'Escape' || e.key.toLowerCase() === 'c') limparDisplay();
});
</script>