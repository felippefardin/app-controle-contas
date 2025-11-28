<?php
require_once 'database.php';
require_once 'includes/session_init.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Controle de Contas - Gestão Financeira em PHP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --accent-color: #ffc107;
            --bg-light: #f8f9fa;
            --text-dark: #212529;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--bg-light);
            color: #495057;
            overflow-x: hidden;
        }

        /* --- Navbar --- */
        .navbar {
            padding: 15px 0;
            background-color: #fff !important;
        }
        .navbar-brand {
            font-weight: 800;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }
        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            margin: 0 10px;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* --- Hero Section --- */
        .hero-section {
            background: linear-gradient(135deg, #f0f8ff 0%, #ffffff 100%);
            padding: 160px 0 100px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-bg-shape {
            position: absolute;
            top: -10%;
            right: -10%;
            width: 50%;
            height: 100%;
            background: linear-gradient(45deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05));
            border-radius: 50% 0 0 50%;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-dark);
            margin-bottom: 20px;
        }
        
        .text-highlight {
            color: var(--primary-color);
        }

        .hero-lead {
            font-size: 1.25rem;
            color: #6c757d;
            margin-bottom: 40px;
            font-weight: 400;
        }

        /* Placeholder Imagem Dashboard */
        .project-screenshot-wrapper {
            position: relative;
            margin-top: 20px;
        }
        
        .project-screenshot {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(13, 110, 253, 0.15);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- Cards --- */
        .feature-card {
            background: #ffffff;
            border: none;
            border-radius: 15px;
            padding: 40px 30px;
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid #f0f0f0;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
            border-color: rgba(13, 110, 253, 0.3);
        }
        .feature-icon-box {
            width: 70px;
            height: 70px;
            background-color: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 25px;
        }

        /* --- Planos --- */
        .pricing-section { background-color: #fff; }
        .pricing-card {
            border: 1px solid #dee2e6;
            border-radius: 15px;
            padding: 30px;
            background: #fff;
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .pricing-card.featured {
            border: 2px solid var(--primary-color);
            background: linear-gradient(to bottom, #f8fbff, #fff);
            transform: scale(1.05);
            z-index: 2;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.15);
        }
        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        .pricing-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .pricing-price small {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 400;
        }
        .trial-badge {
            background-color: #e9ecef;
            color: var(--text-dark);
            font-size: 0.85rem;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        .featured .trial-badge {
            background-color: var(--accent-color);
            color: #000;
        }

        /* --- Botões --- */
        .btn-primary-custom {
            background-color: var(--primary-color);
            color: #fff;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(13, 110, 253, 0.4);
            color: #fff;
        }
        
        .btn-outline-custom {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            background: transparent;
            transition: all 0.3s ease;
        }
        .btn-outline-custom:hover {
            background-color: rgba(13, 110, 253, 0.05);
            color: var(--primary-dark);
        }

        .cta-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            padding: 100px 0;
            color: white;
            text-align: center;
        }

        /* --- NOVO: Botão de Suporte Flutuante --- */
        .btn-support-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0d6efd;
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.4);
            cursor: pointer;
            z-index: 9999;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid #fff;
        }
        
        .btn-support-float:hover {
            transform: scale(1.15) rotate(10deg);
            background: #0a58ca;
        }

        .btn-support-float::before {
            content: '';
            position: absolute;
            width: 100%; height: 100%;
            background: inherit;
            border-radius: 50%;
            z-index: -1;
            animation: pulse 2s infinite;
            opacity: 0.6;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        .support-tooltip {
            position: absolute;
            right: 75px;
            background: #fff;
            color: #333;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
            transform: translateX(10px);
        }
        
        .btn-support-float:hover .support-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        /* Responsivo */
        @media (max-width: 991px) {
            .hero-section { padding: 120px 0 60px; text-align: center; }
            .hero-title { font-size: 2.5rem; }
            .project-screenshot-wrapper { margin-top: 60px; }
            .hero-bg-shape { display: none; }
            .pricing-card.featured { transform: scale(1); margin-top: 20px; margin-bottom: 20px;}
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-wallet2"></i> App Controle
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#funcionalidades">Recursos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#planos">Planos</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-primary px-4 rounded-pill" href="pages/login.php">
                            Acessar Sistema
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-primary px-4 rounded-pill shadow-sm" href="pages/registro.php">
                            Criar Conta
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="btn-support-float" data-bs-toggle="modal" data-bs-target="#modalSuporte">
        <i class="bi bi-headset"></i>
        <div class="support-tooltip">Precisando de ajuda?</div>
    </div>

    <header class="hero-section">
        <div class="hero-bg-shape"></div>
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Controle Financeiro Simples e <span class="text-highlight">Eficaz.</span></h1>
                    <p class="hero-lead">
                        Sistema desenvolvido em PHP e MySQL para gestão completa de contas a pagar e receber. Controle múltiplos usuários, exporte relatórios e organize suas finanças.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        <a href="pages/registro.php" class="btn btn-primary-custom">
                            Começar Agora
                        </a>
                        <a href="#funcionalidades" class="btn btn-outline-custom">
                            Ver Funcionalidades
                        </a>
                    </div>
                    <div class="mt-4 text-muted small">
                        <i class="bi bi-lock-fill text-primary me-2"></i> Acesso Seguro
                        <i class="bi bi-check-circle-fill text-success ms-3 me-2"></i> Multi-usuário
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="project-screenshot-wrapper">
                        <div class="project-screenshot p-0">
                            <img src="img/imgrelatorios.png" 
                                 alt="Dashboard Financeiro" 
                                 class="img-fluid w-100 h-100" 
                                 style="object-fit: cover; border-radius: 15px; box-shadow: 0 15px 40px rgba(0,0,0,0.1);">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="funcionalidades" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase">O que o sistema oferece</h6>
                <h2 class="fw-bold display-6">Tudo para sua gestão financeira</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <i class="bi bi-arrow-down-up"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Contas a Pagar e Receber</h4>
                        <p class="text-muted">Cadastre lançamentos, marque como pagos ou pendentes e visualize o status de vencimento com alertas visuais claros.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Gestão de Usuários</h4>
                        <p class="text-muted">Controle total sobre quem acessa o sistema. Perfis de Administrador e Usuário Padrão com permissões diferenciadas.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Relatórios e Exportação</h4>
                        <p class="text-muted">Gere relatórios detalhados de movimentações e exporte seus dados para Excel ou PDF para análises externas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="planos" class="pricing-section py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase">Planos de Acesso</h6>
                <h2 class="fw-bold display-6">Escale conforme sua necessidade</h2>
                <p class="lead text-muted">Gerencie permissões e quantidade de usuários.</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card">
                        <div class="text-center mb-3">
                            <span class="trial-badge">Inicial</span>
                            <h3 class="pricing-title">Básico</h3>
                            <div class="pricing-price">R$19,90<small>/mês</small></div>
                        </div>
                        <ul class="list-unstyled mb-4 flex-grow-1">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="bi bi-person text-primary me-2 fs-5"></i> 
                                <strong>Ideal para pequenas equipes</strong>
                            </li>
                            <li class="mb-2 ms-4 text-muted small"><i class="bi bi-dot"></i> Até 3 Usuários</li>
                            <li class="mb-3 ms-4 text-muted small"><i class="bi bi-dot"></i> Gestão de Contas Básica</li>
                            <hr>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Controle de Vencimentos</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Suporte por E-mail</li>
                        </ul>
                        <button type="button" class="btn btn-outline-primary rounded-pill w-100 py-2 fw-bold" onclick="abrirModalPlano('basico')">
                            Ver Detalhes e Assinar
                        </button>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card featured">
                        <div class="text-center mb-3">
                            <span class="trial-badge">Mais Popular</span>
                            <h3 class="pricing-title text-primary">Plus</h3>
                            <div class="pricing-price">R$39,90<small>/mês</small></div>
                        </div>
                        <ul class="list-unstyled mb-4 flex-grow-1">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="bi bi-people-fill text-primary me-2 fs-5"></i> 
                                <strong>Equipes em crescimento</strong>
                            </li>
                            <li class="mb-2 ms-4 text-muted small"><i class="bi bi-dot"></i> Até 6 Usuários</li>
                            <li class="mb-3 ms-4 text-muted small"><i class="bi bi-dot"></i> Relatórios Avançados</li>
                            <hr>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Exportação (Excel/PDF)</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Suporte Prioritário</li>
                        </ul>
                        <button type="button" class="btn btn-primary-custom w-100 py-3" onclick="abrirModalPlano('plus')">
                            Ver Detalhes e Assinar
                        </button>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card">
                        <div class="text-center mb-3">
                            <span class="trial-badge bg-success text-white">Completo</span>
                            <h3 class="pricing-title">Essencial</h3>
                            <div class="pricing-price">R$59,90<small>/mês</small></div>
                        </div>
                        <ul class="list-unstyled mb-4 flex-grow-1">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="bi bi-buildings-fill text-primary me-2 fs-5"></i> 
                                <strong>Para grandes operações</strong>
                            </li>
                            <li class="mb-2 ms-4 text-muted small"><i class="bi bi-dot"></i> Até 16 Usuários</li>
                            <li class="mb-3 ms-4 text-muted small"><i class="bi bi-dot"></i> Gestão de Logs e Auditoria</li>
                            <hr>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Todas as funcionalidades</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Suporte Dedicado</li>
                        </ul>
                        <button type="button" class="btn btn-outline-primary rounded-pill w-100 py-2 fw-bold" onclick="abrirModalPlano('essencial')">
                            Ver Detalhes e Assinar
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
    
    <section id="pfpj" class="py-5" style="background-color: #f1f5f9;">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <img src="img/ChatGPT Image 10 de nov. de 2025, 12_38_54.png" alt="Dashboard Financeiro" class="img-fluid rounded-4 shadow-lg">
                </div>
                <div class="col-md-6 ps-md-5">
                    <h2 class="fw-bold mb-4 display-6">Organização para todos os perfis</h2>
                    <p class="lead mb-4">Seja você um profissional autônomo ou uma empresa com equipe financeira.</p>
                    
                    <div class="d-flex mb-4">
                        <div class="me-3">
                            <i class="bi bi-laptop fs-2 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Acesso em qualquer lugar</h5>
                            <p class="text-muted mb-0">Sistema web responsivo. Acesse suas contas a pagar e receber pelo computador ou celular.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="bi bi-shield-lock fs-2 text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold">Segurança de Dados</h5>
                            <p class="text-muted mb-0">Login seguro, níveis de permissão (Admin/User) e banco de dados protegido.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="cta-section">
        <div class="container">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold mb-3 display-5">Pronto para organizar suas finanças?</h2>
                <p class="lead mb-5 opacity-75">Crie sua conta agora mesmo e tenha controle total sobre o fluxo de caixa.</p>
                <a href="pages/registro.php" class="btn btn-light btn-lg fw-bold px-5 py-3 rounded-pill shadow text-primary">
                    Criar Minha Conta
                </a>
            </div>
        </div>
    </section>

    <section id="faq" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Dúvidas Frequentes</h2>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="accordionFAQ">
                        <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Preciso instalar algum software?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                <div class="accordion-body text-muted">
                                    Não. O App Controle de Contas é 100% online. Você só precisa de um navegador e acesso à internet.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Posso adicionar outros usuários?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                <div class="accordion-body text-muted">
                                    Sim! Dependendo do plano escolhido, você pode adicionar múltiplos usuários e definir se eles terão acesso administrativo ou padrão.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Como faço para exportar meus dados?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                <div class="accordion-body text-muted">
                                    O sistema possui um módulo de relatórios onde é possível filtrar por período e exportar as informações para Excel ou PDF.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    require_once 'database.php';
    $conn = getMasterConnection();
    $feedbacks = $conn->query("SELECT * FROM feedbacks WHERE aprovado = 1 ORDER BY criado_em DESC LIMIT 6");
    if ($feedbacks->num_rows > 0):
    ?>
    <section id="feedbacks" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase">Depoimentos</h6>
                <h2 class="fw-bold display-6">O que dizem nossos usuários</h2>
            </div>
            <div class="row g-4">
                <?php while($f = $feedbacks->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-warning mb-3">
                                <?= str_repeat('<i class="bi bi-star-fill"></i>', $f['pontuacao']) ?>
                            </div>
                            <p class="card-text text-muted fst-italic">"<?= htmlspecialchars($f['descricao']) ?>"</p>
                            <div class="d-flex align-items-center mt-4">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px; font-weight:bold;">
                                    <?= strtoupper(substr($f['nome'], 0, 1)) ?>
                                </div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($f['nome']) ?></h6>
                                    <small class="text-muted">Usuário do Sistema</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; $conn->close(); ?>

    <footer class="bg-dark text-white py-4 text-center">
        <div class="container">
            <div class="mb-3">
                <i class="bi bi-wallet2 fs-4 text-primary"></i> <span class="fw-bold fs-5 ms-2">App Controle de Contas</span>
            </div>
            <p class="mb-0 text-secondary">&copy; <?php echo date('Y'); ?> Felipe Fardin. Todos os direitos reservados.</p>
        </div>
    </footer>

    <div class="modal fade" id="modalSuporte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-headset me-2"></i> Suporte Rápido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formSuporteIndex">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="anonimoSuporte" name="anonimo">
                            <label class="form-check-label text-muted" for="anonimoSuporte">Enviar Anonimamente</label>
                        </div>
                        
                        <div id="dadosIdentificacao">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Seu Nome</label>
                                <input type="text" name="nome" class="form-control bg-light" placeholder="Como devemos te chamar?">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">WhatsApp</label>
                                    <input type="text" name="whatsapp" class="form-control bg-light" placeholder="(00) 00000-0000">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small fw-bold">E-mail</label>
                                    <input type="email" name="email" class="form-control bg-light" placeholder="seu@email.com">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Como podemos ajudar?</label>
                            <textarea name="descricao" class="form-control bg-light" rows="4" placeholder="Descreva sua dúvida ou problema..." required style="resize: none;"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnEnviarSuporte" class="btn btn-primary fw-bold px-4" onclick="enviarSuporte()">
                        Enviar Solicitação <i class="bi bi-send-fill ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalhesPlano" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="modalPlanoTitulo">Detalhes do Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary display-4" id="modalPlanoPreco">R$ 0,00</h2>
                        <small class="text-muted fw-bold text-uppercase" id="modalPlanoBadge">Período de Teste</small>
                        <p class="text-muted mt-2" id="modalPlanoDesc">Descrição curta do plano.</p>
                    </div>
                    
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Incluso neste plano:</h6>
                    <ul class="list-unstyled" id="modalPlanoLista">
                        </ul>
                </div>
                <div class="modal-footer border-0 bg-light justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                    <a href="#" id="btnAssinarModal" class="btn btn-primary-custom px-4">
                        Assinar Agora <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dados dos planos centralizados para fácil manutenção
        const dadosPlanos = {
            'basico': {
                titulo: 'Plano Básico',
                preco: 'R$ 19,90<small class="fs-6 text-muted">/mês</small>',
                badge: '15 Dias Grátis',
                desc: 'Ideal para pequenas equipes e profissionais liberais.',
                features: [
                    'Até 3 Usuários',
                    'Gestão de Contas Básica',
                    'Controle de Vencimentos',
                    'Suporte por E-mail',
                    'Acesso via Celular e PC'
                ]
            },
            'plus': {
                titulo: 'Plano Plus',
                preco: 'R$ 39,90<small class="fs-6 text-muted">/mês</small>',
                badge: '15 Dias Grátis - Mais Popular',
                desc: 'Perfeito para equipes em crescimento que precisam de dados.',
                features: [
                    'Todos benefícios do Básico',
                    'Até 6 Usuários',
                    'Relatórios Avançados',
                    'Exportação (Excel/PDF)',
                    'Suporte Prioritário',
                    'Controle de Vencimentos',
                    'Gestão de Anexos',
                    '01 chamada gratís de treinamento via chat online',
                    '01 chamada gratís de treinamento via vídeo chamada',
                ]
            },
            'essencial': {
                titulo: 'Plano Essencial',
                preco: 'R$ 59,90<small class="fs-6 text-muted">/mês</small>',
                badge: '30 Dias Grátis',
                desc: 'Solução completa para grandes operações e auditoria.',
                features: [
                    'Até 16 Usuários',
                    'Gestão de Logs e Auditoria',
                    'Todas as funcionalidades',
                    'Suporte Dedicado (WhatsApp)',
                    'Treinamento Inicial',
                    'Backup Diário Automático',
                    '03 chamadas gratís de treinamento via chat online',
                    '01 chamada gratís de treinamento via vídeo chamada',
                ]
            }
        };

        function abrirModalPlano(planoKey) {
            const data = dadosPlanos[planoKey];
            if (!data) return;

            // Preenche os elementos do modal
            document.getElementById('modalPlanoTitulo').innerText = data.titulo;
            document.getElementById('modalPlanoPreco').innerHTML = data.preco;
            document.getElementById('modalPlanoBadge').innerText = data.badge;
            document.getElementById('modalPlanoDesc').innerText = data.desc;

            // Monta a lista de features com ícones
            const listaEl = document.getElementById('modalPlanoLista');
            listaEl.innerHTML = ''; // Limpa anterior
            data.features.forEach(feat => {
                const li = document.createElement('li');
                li.className = 'mb-2 d-flex align-items-center';
                li.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i> ${feat}`;
                listaEl.appendChild(li);
            });

            // Atualiza o link do botão de ação
            const btnAssinar = document.getElementById('btnAssinarModal');
            // Mantém a estrutura de URL existente: pages/registro.php?plano=xyz
            btnAssinar.href = `pages/registro.php?plano=${planoKey}`;

            // Abre o modal usando Bootstrap 5
            const modalEl = document.getElementById('modalDetalhesPlano');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script type="text/javascript">
    // Tawk.to Script existente
    // var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    // (function(){
    // var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    // s1.async=true;
    // s1.src='https://embed.tawk.to/692252545a6d17195e8d14ce/1jan136ki';
    // s1.charset='UTF-8';
    // s1.setAttribute('crossorigin','*');
    // s0.parentNode.insertBefore(s1,s0);
    // })();

    // --- LÓGICA DO SUPORTE NO INDEX ---
    
    // Toggle Anonimo
    const checkAnonimo = document.getElementById('anonimoSuporte');
    const divIdentificacao = document.getElementById('dadosIdentificacao');
    
    if(checkAnonimo) {
        checkAnonimo.addEventListener('change', function() {
            if (this.checked) {
                divIdentificacao.style.display = 'none';
                // Limpa os campos para evitar envio acidental de dados ocultos
                divIdentificacao.querySelectorAll('input').forEach(i => i.value = '');
            } else {
                divIdentificacao.style.display = 'block';
            }
        });
    }

    function enviarSuporte() {
        const btn = document.getElementById('btnEnviarSuporte');
        const originalHTML = btn.innerHTML;
        const form = document.getElementById('formSuporteIndex');
        
        // Validação básica HTML5
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Estado de Carregamento
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';

        const formData = new FormData(form);
        
        // IMPORTANTE: Caminho ajustado para a raiz (actions/...)
        fetch('actions/enviar_suporte_login.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                // Fecha o modal
                const modalEl = document.getElementById('modalSuporte');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();

                // Exibe mensagem bonita ou alert padrão
                alert(data.msg); 
                form.reset();
            } else {
                alert(data.msg || 'Erro ao processar solicitação.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão. Verifique sua internet e tente novamente.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }
    </script>
    
    <div vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
</body>
</html>