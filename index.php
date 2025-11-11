<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Contas - Simplifique sua Gestão Financeira</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            color: #0d6efd !important;
        }
        .hero-section {
            background: linear-gradient(rgba(13, 110, 253, 0.85), rgba(13, 110, 253, 0.85)), url('https://images.unsplash.com/photo-1565373679998-1e992a55ff0e?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 130px 0;
            text-align: center;
        }
        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
        }
        .hero-section .lead {
            font-size: 1.25rem;
            margin-bottom: 35px;
        }
        .btn-primary-custom {
            background-color: #fff;
            color: #0d6efd;
            border: none;
            font-weight: 600;
            padding: 14px 35px;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #f0f0f0;
            transform: translateY(-3px);
        }
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            font-weight: 700;
            color: #212529;
        }
        .feature-card {
            background: #ffffff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 35px 25px;
            text-align: center;
            transition: transform 0.3s ease;
            height: 100%;
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
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: white;
            text-align: center;
            padding: 90px 20px;
        }
        .cta-section h2 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 25px;
        }
        footer {
            padding: 30px 0;
            background-color: #343a40;
            color: #adb5bd;
            text-align: center;
        }
        /* Adicionando estilos para a seção de planos */
        .pricing-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.2);
        }
        .pricing-card-header {
            margin-bottom: 20px;
        }
        .pricing-card-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .pricing-card-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
            line-height: 1.2;
        }
        .pricing-card-period {
            font-size: 1rem;
            color: #6c757d;
        }
        .plan-feature {
            list-style: none;
            padding: 0;
            text-align: left;
            margin-bottom: 25px;
        }
        .plan-feature li {
            padding: 8px 0;
            border-bottom: 1px dashed #e9ecef;
            color: #495057;
        }
        .plan-feature li:last-child {
            border-bottom: none;
        }
        .plan-highlight {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #0d6efd;
        }
        .discount-tag {
            background-color: #ffc107;
            color: #343a40;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
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
                    <li class="nav-item"><a class="nav-link" href="#funcionalidades">Funcionalidades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#planos">Planos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pfpj">PF e PJ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cta">Assine Agora</a></li>
                    <li class="nav-item ms-lg-2 mb-2 mb-lg-0">
                        <a class="btn btn-outline-primary" href="/app-controle-contas/pages/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="/app-controle-contas/pages/registro.php">
                            <i class="bi bi-person-plus-fill"></i> Teste Grátis
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <h1>Organize suas finanças de forma simples e eficiente</h1>
            <p class="lead">Controle de Contas é a plataforma completa de gestão financeira — ideal para autônomos, empresas e profissionais liberais que querem ter o controle total do seu dinheiro.</p>
            <a href="/app-controle-contas/pages/registro.php" class="btn btn-primary-custom btn-lg shadow">Experimente Grátis Agora</a>
        </div>
    </header>

    <section id="funcionalidades" class="py-5">
        <div class="container py-4">
            <h2 class="section-title">Tudo que você precisa em um só lugar</h2>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-calendar-check"></i></div>
                        <h5 class="fw-bold">Contas a Pagar e Receber</h5>
                        <p>Gerencie prazos, recebimentos e evite juros. Receba alertas automáticos e tenha previsibilidade financeira.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
                        <h5 class="fw-bold">Fluxo de Caixa e Vendas</h5>
                        <p>Monitore entradas e saídas em tempo real e tenha relatórios automáticos de performance.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <h5 class="fw-bold">Relatórios Inteligentes</h5>
                        <p>Gráficos claros, indicadores automáticos e insights práticos para decisões financeiras.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="planos" class="py-5 pricing-section">
        <div class="container py-4">
            <h2 class="section-title">Escolha o plano ideal para você</h2>
            <div class="row g-4 justify-content-center">
                
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card">
                        <div>
                            <div class="pricing-card-header">
                                <h3 class="pricing-card-title">Plano Mensal</h3>
                                <div class="plan-highlight">15 dias de teste grátis!</div>
                            </div>
                            <div class="pricing-card-price">R$49,90</div>
                            <p class="pricing-card-period">por mês</p>
                            <ul class="plan-feature">
                                <li><i class="bi bi-check-lg text-success"></i> Gestão de Contas a Pagar/Receber</li>
                                <li><i class="bi bi-check-lg text-success"></i> Controle de Fluxo de Caixa</li>
                                <li><i class="bi bi-check-lg text-success"></i> Cadastro de 2 usuários</li>
                                <li><i class="bi bi-check-lg text-success"></i> Relatórios em PDF</li>
                            </ul>
                        </div>
                        <a href="/app-controle-contas/pages/registro.php?plano=mensal" class="btn btn-primary btn-lg mt-3">Começar com 15 dias Grátis</a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card border-primary" style="border: 3px solid #0d6efd;">
                        <div>
                            <div class="pricing-card-header">
                                <h3 class="pricing-card-title">Plano Trimestral</h3>
                                <div class="discount-tag">Economize 14.56%</div> 
                                <div class="plan-highlight" style="color: #fff; background-color: #0d6efd;">30 dias de teste grátis!</div>
                            </div>
                            <p style="text-decoration: line-through; color: #6c757d;">R$149,70</p>
                            <div class="pricing-card-price">R$127,90</div>
                            <p class="pricing-card-period">a cada 3 meses</p>
                            <ul class="plan-feature">
                                <li><i class="bi bi-check-lg text-success"></i> Todos os recursos do Plano Mensal</li>
                                <li><i class="bi bi-check-lg text-success"></i> **Economia de R$21,80**</li>
                                <li><i class="bi bi-check-lg text-success"></i> Cadastro de 5 usuários</li>
                                <li><i class="bi bi-check-lg text-success"></i> Exportação de Relatórios (CSV/Excel)</li>
                            </ul>
                        </div>
                        <a href="/app-controle-contas/pages/registro.php?plano=trimestral" class="btn btn-primary btn-lg mt-3">Começar com 30 dias Grátis</a>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
    
    <section id="pfpj" class="bg-white py-5">
        <div class="container py-4">
            <h2 class="section-title">Feito sob medida para Pessoa Física e Jurídica</h2>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <img src="img/ChatGPT Image 10 de nov. de 2025, 12_38_54.png" alt="Gestão Financeira" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled mt-4">
                        <li><i class="bi bi-check-circle text-primary"></i> <strong>Pessoa Física:</strong> controle gastos pessoais, saldos e metas financeiras.</li>
                        <li><i class="bi bi-check-circle text-primary"></i> <strong>Autônomos:</strong> acompanhe receitas e despesas de forma prática e profissional.</li>
                        <li><i class="bi bi-check-circle text-primary"></i> <strong>Empresas (PJ):</strong> organize finanças, fluxo de caixa, clientes, fornecedores e vendas.</li>
                    </ul>
                    <a href="/app-controle-contas/pages/registro.php" class="btn btn-primary mt-3 px-4 py-2">Quero Começar Agora</a>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="cta-section">
        <div class="container">
            <h2>Experimente grátis e veja como é fácil controlar suas finanças</h2>
            <p class="lead mb-4">Sem cartão de crédito, sem compromisso. Teste o sistema e descubra o poder de uma gestão automatizada.</p>
            <a href="/app-controle-contas/pages/registro.php" class="btn btn-light btn-lg fw-bold px-4 py-2">Criar Minha Conta Grátis</a>
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