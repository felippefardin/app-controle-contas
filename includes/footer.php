<style>
    /* Estilos do Rodapé Principal */
.footer {
    background-color: #222;
    color: #aaa;
    padding: 15px 0;
    text-align: center;
    position: relative; /* Remove o fixo para não cobrir conteúdo */
    width: 100%;
    font-size: 0.9em;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.3);
    z-index: 1000;
    margin-top: 40px; /* Espaço entre o conteúdo e o rodapé */
}

/* Caso queira que o footer fique no fundo mesmo em páginas curtas */
body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
main {
    flex: 1;
}

/* Links do rodapé */
.footer a {
    color: #0af;
    text-decoration: none;
    margin: 0 10px;
}
.footer a:hover {
    text-decoration: underline;
}

/* Ícones flutuantes de ação */
.action-buttons {
    position: fixed;
    bottom: 80px; /* Posição acima do rodapé */
    left: 20px;
    z-index: 1002;
    display: flex;
    flex-direction: column;
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

<body>
   

    <main>
        <!-- conteúdo principal da página -->
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
</body>
