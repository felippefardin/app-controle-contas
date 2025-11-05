<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-F8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Contas - Gestão Financeira Completa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            color: #0d6efd !important;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1554224155-169543018d41?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3wzNjUyOXwwfDF8c2VhcmNofDE0fHxmaW5hbmNlfGVufDB8fHx8MTcxNzYwMjg2M3ww&ixlib=rb-4.0.3&q=80&w=1920');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
        }
        .hero-section .lead {
            font-size: 1.3rem;
            margin-bottom: 30px;
        }
        .btn-primary-custom {
            background-color: #0d6efd;
            border-color: #0d6efd;
            font-weight: 600;
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        .btn-primary-custom:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            font-weight: 700;
            color: #343a40;
        }
        .feature-card {
            background: #ffffff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 30px;
            text-align: center;
            height: 100%;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .cta-section {
            background-color: #0d6efd;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .cta-section h2 {
            font-weight: 700;
            margin-bottom: 30px;
        }
        .btn-light-custom {
            background-color: #ffffff;
            border-color: #ffffff;
            color: #0d6efd;
            font-weight: 600;
            padding: 12px 30px;
            font-size: 1.1rem;
        }
        footer {
            padding: 30px 0;
            background-color: #343a40;
            color: #adb5bd;
            text-align: center;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/app-controle-contas/index.php">
                <i class="bi bi-wallet2"></i> Controle de Contas
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#funcionalidades">Funcionalidades</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#cta">Começar</a>
                    </li>
                    
                    <li class="nav-item ms-lg-2 mb-2 mb-lg-0">
                        <a class="btn btn-outline-primary" href="/app-controle-contas/pages/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Fazer Login
                        </a>
                    </li>
                    
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="/app-controle-contas/pages/registro.php">
                            <i class="bi bi-person-plus-fill"></i> Assinar Agora
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <h1>Tome o Controle Total das Suas Finanças</h1>
            <p class="lead">O sistema de gestão financeira simples e poderoso que ajuda você a organizar suas contas, controlar seu estoque e impulsionar suas vendas.</p>
            
            <a href="/app-controle-contas/pages/registro.php" class="btn btn-primary-custom btn-lg">
                Comece Agora (Teste Grátis)
            </a>
        </div>
    </header>

    <section id="funcionalidades" class="py-5">
        <div class="container py-5">
            <h2 class="section-title">A Plataforma Completa para sua Gestão</h2>
            <div class="row g-4">
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-calendar-check"></i></div>
                        <h5 class="fw-bold">Contas a Pagar e Receber</h5>
                        <p>Nunca mais perca um vencimento. Gerencie todos os seus pagamentos e recebimentos em um único lugar, com lembretes automáticos.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-cart-check"></i></div>
                        <h5 class="fw-bold">Controle de Vendas e Estoque</h5>
                        <p>Registre suas vendas, gerencie seu catálogo de produtos e mantenha seu estoque sempre atualizado de forma integrada.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <h5 class="fw-bold">Relatórios Inteligentes</h5>
                        <p>Visualize o desempenho do seu negócio com relatórios detalhados de fluxo de caixa, despesas por categoria, vendas e muito mais.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
                        <h5 class="fw-bold">Fluxo de Caixa Diário</h5>
                        <p>Acompanhe todas as entradas e saídas do seu caixa em tempo real, garantindo a saúde financeira do seu dia a dia.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                        <h5 class="fw-bold">Gestão de Pessoas</h5>
                        <p>Mantenha um cadastro organizado de seus clientes, fornecedores e usuários do sistema, facilitando o relacionamento e o histórico.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-shield-lock"></i></div>
                        <h5 class="fw-bold">Segurança e Multi-usuário</h5>
                        <p>Gerencie permissões de acesso para diferentes usuários e tenha a tranquilidade de que seus dados estão protegidos.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container py-5">
            <h2 class="section-title">Feito para Quem Busca Resultados</h2>
            <div class="row text-center">
                <div class="col-md-8 mx-auto">
                    <i class="bi bi-quote" style="font-size: 3rem; color: #0d6efd;"></i>
                    <p class="lead fst-italic">"Desde que comecei a usar o Controle de Contas, minha organização financeira mudou da água para o vinho. Finalmente sei para onde meu dinheiro está indo e consigo planejar meu crescimento."</p>
                    <p class="fw-bold">- Empreendedor Satisfeito</p>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="cta-section">
        <div class="container">
            <h2>Pronto para transformar sua gestão financeira?</h2>
            <p class="lead mb-4">Cadastre-se agora e experimente todas as funcionalidades.</p>
            
            <a href="/app-controle-contas/pages/registro.php" class="btn btn-light-custom btn-lg">
                Quero me cadastrar!
            </a>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Controle de Contas. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>