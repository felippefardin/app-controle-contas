<div id="calendario-container" style="display:none;">
    <div id="calendario-header">
        <i class="fas fa-calendar-alt" style="margin-right: 5px;"></i> Calendário
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
    /* === CALENDÁRIO BASE (DESKTOP) === */
    #calendario-container {
        position: fixed;
        top: 15%;
        left: 45%; /* Posicionado ao lado da calculadora (que está em 70%) */
        width: 300px;
        background-color: #1e1e1e;
        color: #eee;
        font-family: "Segoe UI", Arial, sans-serif;
        border-radius: 10px;
        border: 1px solid #444;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
        z-index: 1201; /* Um nível acima da calculadora */
        overflow: hidden;
        transition: border-color 0.3s;
    }

    #calendario-container.ativa {
        border-color: #27ae60; /* Verde para diferenciar */
        box-shadow: 0 0 15px rgba(39, 174, 96, 0.4);
    }

    #calendario-header {
        background-color: #2a2a2a;
        color: #27ae60;
        font-size: 14px;
        font-weight: bold;
        padding: 8px 12px;
        cursor: move;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
        border-bottom: 1px solid #333;
    }

    #fechar-calendario {
        cursor: pointer;
        color: #aaa;
        font-size: 18px;
        font-weight: bold;
        line-height: 1;
        transition: 0.2s;
    }
    #fechar-calendario:hover {
        color: #ff5555;
    }
    
    #calendario-body { padding: 10px; }

    .calendario-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .calendario-nav h3 {
        margin: 0;
        font-size: 1em;
        color: #eee;
        font-weight: 600;
        text-transform: capitalize;
    }
    .calendario-nav button {
        background: #333;
        color: #eee;
        border: 1px solid #444;
        border-radius: 4px;
        cursor: pointer;
        padding: 4px 10px;
        font-size: 12px;
        transition: background 0.2s;
    }
    .calendario-nav button:hover { background-color: #444; }

    .dias-semana, .dias-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        gap: 2px;
    }

    .dias-semana div {
        font-weight: bold;
        font-size: 0.85em;
        color: #888;
        padding-bottom: 5px;
    }
    
    .dias-grid div {
        padding: 8px 0;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.2s;
    }
    
    .dias-grid div:not(.outro-mes):hover {
        background-color: #444;
    }
    
    .dias-grid .outro-mes {
        color: #444;
    }

    .dias-grid .hoje {
        background-color: #27ae60;
        color: #fff;
        font-weight: bold;
        box-shadow: 0 0 5px rgba(39, 174, 96, 0.5);
    }

    /* === RESPONSIVIDADE (TABLET E MOBILE) === */
    @media (max-width: 768px) {
        #calendario-container {
            width: 260px; /* Tamanho reduzido para mobile */
            top: 100px;
            left: 20px;
        }

        #calendario-header {
            padding: 6px 10px;
            font-size: 13px;
        }

        .dias-grid div {
            padding: 6px 0;
            font-size: 0.85em;
        }
        
        .calendario-nav h3 {
            font-size: 0.95em;
        }
    }
</style>

<script>
    const calendarioContainer = document.getElementById('calendario-container');
    const calendarioHeader = document.getElementById('calendario-header');
    const monthYearEl = document.getElementById('month-year');
    const diasContainer = document.getElementById('dias-calendario');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');

    let offsetXCal, offsetYCal;
    let calendarioAtivo = false;
    let dataAtual = new Date();

    // --- FUNÇÕES DO CALENDÁRIO ---
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
        
        // Preencher dias do próximo mês para completar a grade
        const totalDiasGrid = diasContainer.children.length;
        const diasRestantes = (7 - (totalDiasGrid % 7)) % 7;
        // Se a grade tiver menos de 6 linhas (42 células), adicione mais uma linha se necessário ou preencha
        // O padrão geralmente é 6 linhas para cobrir todos os casos
        const totalCellsTarget = 42; 
        const currentCells = totalDiasGrid + diasRestantes;
        
        // Simplesmente preenche o resto da linha atual
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

    function garantirAtividadeCalendario() {
        if (!calendarioAtivo) {
            calendarioAtivo = true;
            calendarioContainer.classList.add('ativa');
        }
    }
    
    // --- ARRASTAR (MOUSE E TOQUE) ---

    // Iniciar arrasto (Mouse)
    calendarioHeader.addEventListener('mousedown', iniciarArrastoCal);
    // Iniciar arrasto (Toque)
    calendarioHeader.addEventListener('touchstart', iniciarArrastoCal, {passive: false});

    function iniciarArrastoCal(e) {
        garantirAtividadeCalendario();

        // Identifica coordenadas iniciais
        const clienteX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
        const clienteY = e.type === 'mousedown' ? e.clientY : e.touches[0].clientY;

        offsetXCal = clienteX - calendarioContainer.offsetLeft;
        offsetYCal = clienteY - calendarioContainer.offsetTop;

        // Adiciona ouvintes
        if (e.type === 'mousedown') {
            document.addEventListener('mousemove', moverCalendario);
            document.addEventListener('mouseup', pararArrastoCal);
        } else {
            document.addEventListener('touchmove', moverCalendario, {passive: false});
            document.addEventListener('touchend', pararArrastoCal);
        }
    }

    function moverCalendario(e) {
        e.preventDefault(); // Impede scroll da tela no mobile

        const clienteX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
        const clienteY = e.type === 'mousemove' ? e.clientY : e.touches[0].clientY;

        calendarioContainer.style.left = (clienteX - offsetXCal) + 'px';
        calendarioContainer.style.top = (clienteY - offsetYCal) + 'px';
    }

    function pararArrastoCal() {
        document.removeEventListener('mousemove', moverCalendario);
        document.removeEventListener('mouseup', pararArrastoCal);
        document.removeEventListener('touchmove', moverCalendario);
        document.removeEventListener('touchend', pararArrastoCal);
    }
    
    // --- ESTADOS E FECHAMENTO ---

    calendarioContainer.addEventListener('click', (e) => {
        garantirAtividadeCalendario();
        e.stopPropagation();
    });

    // Fechar ao clicar fora (opcional, se quiser fechar ao clicar no fundo da página)
    /*
    document.addEventListener('click', (e) => {
        // Verifica se clicou fora e se não foi no botão de abrir (se existir externamente)
        const botaoAbrir = document.getElementById('abrir-calendario');
        if (calendarioAtivo && !calendarioContainer.contains(e.target) && (!botaoAbrir || !botaoAbrir.contains(e.target))) {
            calendarioAtivo = false;
            calendarioContainer.classList.remove('ativa');
        }
    });
    */
    
    document.getElementById('fechar-calendario').addEventListener('click', (e) => {
        calendarioContainer.style.display = 'none';
        calendarioAtivo = false;
        calendarioContainer.classList.remove('ativa');
        e.stopPropagation();
    });
    
    // Renderiza ao carregar
    renderizarCalendario();
</script>