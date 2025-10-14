<div id="calendario-container" style="display:none;">
    <div id="calendario-header">
        Calendário
        <span id="fechar-calendario">×</span>
    </div>
    <div id="calendario-body">
        <div class="calendario-nav">
            <button id="prev-month">&lt;</button>
            <h3 id="month-year"></h3>
            <button id="next-month">&gt;</button>
        </div>
        <div class="dias-semana">
            <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
        </div>
        <div id="dias-calendario" class="dias-grid">
            </div>
    </div>
</div>

<style>
    /* Estilo geral no mesmo padrão da calculadora */
    #calendario-container {
        position: fixed;
        top: 150px;
        left: 150px;
        width: 300px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 999; /* Um pouco abaixo da calculadora */
        background-color: #1e1e1e;
        color: #fff;
        font-family: Arial, sans-serif;
        border: 2px solid #1e1e1e;
        transition: border-color 0.3s;
    }

    #calendario-container.ativa {
        border-color: #27ae60; /* Verde para diferenciar do azul da calculadora */
    }

    #calendario-header {
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

    #fechar-calendario { cursor:pointer; font-size:18px; font-weight:bold; }
    
    #calendario-body { padding: 10px; }

    .calendario-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .calendario-nav h3 {
        margin: 0;
        font-size: 1.1em;
        color: #fff;
    }
    .calendario-nav button {
        background: #3a3a3a;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        padding: 5px 10px;
    }
    .calendario-nav button:hover { background-color: #555; }

    .dias-semana, .dias-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        gap: 5px;
    }

    .dias-semana div {
        font-weight: bold;
        font-size: 0.9em;
        color: #888;
    }
    
    .dias-grid div {
        padding: 8px 0;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .dias-grid div:not(.outro-mes):hover {
        background-color: #555;
    }
    
    .dias-grid .outro-mes {
        color: #555;
    }

    .dias-grid .hoje {
        background-color: #27ae60;
        color: #fff;
        font-weight: bold;
    }

</style>

<script>
    const calendarioContainer = document.getElementById('calendario-container');
    const calendarioHeader = document.getElementById('calendario-header');
    const monthYearEl = document.getElementById('month-year');
    const diasContainer = document.getElementById('dias-calendario');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');

    let calendarioAtivo = false;
    let dataAtual = new Date();

    const renderizarCalendario = () => {
        const mes = dataAtual.getMonth();
        const ano = dataAtual.getFullYear();

        monthYearEl.textContent = `${dataAtual.toLocaleString('pt-BR', { month: 'long' })} ${ano}`;

        diasContainer.innerHTML = '';
        
        const primeiroDiaMes = new Date(ano, mes, 1).getDay();
        const ultimoDiaMes = new Date(ano, mes + 1, 0).getDate();
        const ultimoDiaMesAnterior = new Date(ano, mes, 0).getDate();

        // Dias do mês anterior
        for (let i = primeiroDiaMes; i > 0; i--) {
            diasContainer.innerHTML += `<div class="outro-mes">${ultimoDiaMesAnterior - i + 1}</div>`;
        }
        
        // Dias do mês atual
        for (let i = 1; i <= ultimoDiaMes; i++) {
            const hoje = new Date();
            let classe = '';
            if (i === hoje.getDate() && mes === hoje.getMonth() && ano === hoje.getFullYear()) {
                classe = 'hoje';
            }
            diasContainer.innerHTML += `<div class="${classe}">${i}</div>`;
        }
        
        // Preencher o restante com os dias do próximo mês
        const totalDiasGrid = diasContainer.children.length;
        const diasRestantes = (7 - (totalDiasGrid % 7)) % 7;
        for (let i = 1; i <= diasRestantes; i++) {
             diasContainer.innerHTML += `<div class="outro-mes">${i}</div>`;
        }
    };
    
    prevMonthBtn.addEventListener('click', () => {
        dataAtual.setMonth(dataAtual.getMonth() - 1);
        renderizarCalendario();
    });

    nextMonthBtn.addEventListener('click', () => {
        dataAtual.setMonth(dataAtual.getMonth() + 1);
        renderizarCalendario();
    });
    
    // --- LÓGICA DE ATIVAÇÃO E DESATIVAÇÃO ---
    calendarioContainer.addEventListener('click', (e) => {
      if (!calendarioAtivo) {
        calendarioAtivo = true;
        calendarioContainer.classList.add('ativa');
      }
      e.stopPropagation();
    });

    document.addEventListener('click', () => {
      if (calendarioAtivo) {
        calendarioAtivo = false;
        calendarioContainer.classList.remove('ativa');
      }
    });
    
    // --- LÓGICA DE MOVIMENTAÇÃO E FECHAMENTO ---
    let offsetXCal, offsetYCal;
    calendarioHeader.addEventListener('mousedown', e => {
      e.preventDefault();
      offsetXCal = e.clientX - calendarioContainer.offsetLeft;
      offsetYCal = e.clientY - calendarioContainer.offsetTop;
      document.addEventListener('mousemove', moverCalendario);
      document.addEventListener('mouseup', pararMoverCalendario);
    });

    function moverCalendario(e) {
      calendarioContainer.style.left = (e.clientX - offsetXCal) + 'px';
      calendarioContainer.style.top = (e.clientY - offsetYCal) + 'px';
    }

    function pararMoverCalendario() {
      document.removeEventListener('mousemove', moverCalendario);
      document.removeEventListener('mouseup', pararMoverCalendario);
    }
    
    document.getElementById('fechar-calendario').addEventListener('click', (e) => {
      calendarioContainer.style.display = 'none';
      calendarioAtivo = false;
      calendarioContainer.classList.remove('ativa');
      e.stopPropagation();
    });
    
    // Renderiza o calendário ao carregar o script
    renderizarCalendario();
</script>