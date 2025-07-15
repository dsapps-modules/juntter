@extends('templates.sample-template')

@section('page')
    <!-- Conteúdo da página checkout aqui (sem scripts duplicados) -->
    <div class="loading-overlay" id="loading">
        <div class="loading-spinner"></div>
    </div>

<section class="hero-section">
    <div class="particles-container" id="particles"></div>
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title animate__animated animate__fadeInUp">
                Checkout Digital que<br>
                <span>vende por você</span>
            </h1>
            <p class="hero-subtitle animate__animated animate__fadeInUp animate__delay-1s">
                Crie links de pagamento profissionais, receba via PIX, cartão ou boleto. 
                Venda online com segurança e receba na hora.
            </p>
            <a href="{{ route('register') }}" class="btn-hero animate__animated animate__fadeInUp animate__delay-2s">
                <i class="fas fa-link mr-2"></i>Criar Meu Checkout
            </a>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="stat-card">
                    <div class="stat-number" data-count="100000">0</div>
                    <div class="stat-label">Links Criados</div>
                </div>
            </div>
            <div class="col-lg-3 mb-4">
                <div class="stat-card">
                    <div class="stat-number" data-count="98">0</div>
                    <div class="stat-label">% Taxa de Conversão</div>
                </div>
            </div>
            <div class="col-lg-3 mb-4">
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Disponibilidade</div>
                </div>
            </div>
            <div class="col-lg-3 mb-4">
                <div class="stat-card">
                    <div class="stat-number">R$ 0</div>
                    <div class="stat-label">Taxa de Setup</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="beneficios" class="section">
    <div class="container">
        <h2 class="section-title fade-in-up">Por que usar o Juntter Checkout?</h2>
        <p class="section-subtitle fade-in-up">Facilite suas vendas online com nossa plataforma completa</p>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <h4>Links de Pagamento</h4>
                    <p>Crie links personalizados em segundos. Envie por WhatsApp, email ou redes sociais. Sem complicação.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <h4>PIX Instantâneo</h4>
                    <p>Receba pagamentos via PIX na hora. QR Code automático e chave PIX integrada para máxima praticidade.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h4>Checkout Personalizado</h4>
                    <p>Design profissional com sua marca. Cores, logo e layout que convertem mais. Totalmente responsivo.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>Analytics Avançado</h4>
                    <p>Relatórios detalhados de vendas, conversão e performance. Dashboards intuitivos para crescer seu negócio.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Segurança Bancária</h4>
                    <p>Certificação PCI DSS e criptografia de ponta. Dados protegidos e transações 100% seguras.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h4>API Completa</h4>
                    <p>Integre com seu site, app ou sistema. Documentação completa e SDKs para developers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="precos" class="pricing-section section">
    <div class="container">
        <h2 class="section-title fade-in-up">Planos transparentes</h2>
        <p class="section-subtitle fade-in-up">Escolha o plano ideal para o volume do seu negócio</p>
        <div class="row justify-content-center">
            <div class="col-lg-4 mb-4">
                <div class="pricing-card fade-in-up">
                    <h3>Starter</h3>
                    <div class="price">3,99%</div>
                    <p>por transação aprovada</p>
                    <ul class="list-unstyled mt-4 mb-4">
                        <li><i class="fas fa-check text-success mr-2"></i>Links ilimitados</li>
                        <li><i class="fas fa-check text-success mr-2"></i>PIX grátis</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Cartão: 3,99%</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Checkout básico</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Suporte por email</li>
                    </ul>
                    <a id="starter" href="{{ route('register') }}" class="btn btn-outline-warning btn-block">Começar Grátis</a>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="pricing-card popular fade-in-up">
                    <h3>Pro</h3>
                    <div class="price">2,99%</div>
                    <p>por transação aprovada</p>
                    <ul class="list-unstyled mt-4 mb-4">
                        <li><i class="fas fa-check text-success mr-2"></i>Tudo do Starter</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Cartão: 2,99%</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Checkout personalizado</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Analytics avançado</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Suporte prioritário</li>
                        <li><i class="fas fa-check text-success mr-2"></i>API básica</li>
                    </ul>
                    <a id="pro" href="{{ route('register') }}" class="btn btn-warning btn-block">Escolher Pro</a>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="pricing-card fade-in-up">
                    <h3>Enterprise</h3>
                    <div class="price">1,99%</div>
                    <p>por transação aprovada</p>
                    <ul class="list-unstyled mt-4 mb-4">
                        <li><i class="fas fa-check text-success mr-2"></i>Tudo do Pro</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Cartão: 1,99%</li>
                        <li><i class="fas fa-check text-success mr-2"></i>API completa</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Suporte 24/7</li>
                        <li><i class="fas fa-check text-success mr-2"></i>White label</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Consultoria</li>
                    </ul>
                    <a id="enterprise" href="{{ route('register') }}" class="btn btn-outline-warning btn-block">Falar com Vendas</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="como-funciona" class="section" style="background: var(--light-gray);">
    <div class="container">
        <h2 class="section-title fade-in-up">Como funciona?</h2>
        <p class="section-subtitle fade-in-up">Venda online em 3 passos simples</p>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h4>1. Crie seu Link</h4>
                    <p>Cadastre seu produto ou serviço, defina o valor e personalize a página de checkout com sua marca.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h4>2. Compartilhe</h4>
                    <p>Envie o link por WhatsApp, email, redes sociais ou incorpore no seu site. Seus clientes acessam e pagam facilmente.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="benefit-card fade-in-up">
                    <div class="benefit-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h4>3. Receba</h4>
                    <p>PIX na hora, cartão em 1 dia útil. Acompanhe todas as vendas em tempo real no seu dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="depoimentos" class="section">
    <div class="container">
        <h2 class="section-title fade-in-up">O que nossos clientes dizem</h2>
        <p class="section-subtitle fade-in-up">Histórias reais de quem transformou vendas com o Juntter</p>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="testimonial-card fade-in-up">
                    <p class="testimonial-text">
                        Antes eu perdia vendas porque era difícil receber pagamentos online. Com o Juntter, criei links em segundos e minhas vendas de cursos aumentaram 200%!
                    </p>
                    <div class="testimonial-author">
                        <strong>Marina Silva</strong><br>
                        <small>Criadora de Cursos Online</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="testimonial-card fade-in-up">
                    <p class="testimonial-text">
                        Integrei a API do Juntter no meu e-commerce e automatizei tudo. Checkout personalizado, PIX instantâneo e relatórios detalhados. Perfeito!
                    </p>
                    <div class="testimonial-author">
                        <strong>Carlos Mendes</strong><br>
                        <small>Dono de E-commerce</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="faq" class="faq-section section">
    <div class="container">
        <h2 class="section-title fade-in-up">Perguntas Frequentes</h2>
        <p class="section-subtitle fade-in-up">Tire suas dúvidas sobre o Juntter Checkout</p>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item fade-in-up">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Como funciona o checkout digital? <i class="fas fa-chevron-down float-right"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Você cria um link personalizado para seu produto/serviço. O cliente clica, acessa uma página profissional de checkout e paga via PIX, cartão ou boleto. Simples e seguro!</p>
                    </div>
                </div>

                <div class="faq-item fade-in-up">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Posso personalizar a página de checkout? <i class="fas fa-chevron-down float-right"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Sim! Você pode adicionar sua logo, cores da marca, descrições personalizadas e até mesmo campos extras. Tudo para transmitir profissionalismo.</p>
                    </div>
                </div>

                <div class="faq-item fade-in-up">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Quanto tempo demora para receber? <i class="fas fa-chevron-down float-right"></i>
                    </button>
                    <div class="faq-answer">
                        <p>PIX: instantâneo! Cartão de crédito: 1 dia útil. Boleto: 2 dias úteis após compensação. Tudo automatizado em sua conta.</p>
                    </div>
                </div>

                <div class="faq-item fade-in-up">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Posso integrar com meu sistema? <i class="fas fa-chevron-down float-right"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Claro! Temos API completa, webhooks e SDKs para as principais linguagens. Documentação detalhada e suporte técnico especializado.</p>
                    </div>
                </div>

                <div class="faq-item fade-in-up">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        É seguro para meus clientes? <i class="fas fa-chevron-down float-right"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Totalmente! Certificação PCI DSS, SSL 256 bits, dados criptografados e infraestrutura bancária. Seus clientes pagam com total segurança.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section section">
    <div class="container">
        <div style="position: relative; z-index: 2;">
            <h2 class="fade-in-up" style="font-size: 3rem; font-weight: 800; margin-bottom: 20px;">
                Pronto para vender mais?
            </h2>
            <p class="fade-in-up" style="font-size: 1.3rem; margin-bottom: 40px; opacity: 0.9;">
                Crie sua conta gratuita e comece a receber pagamentos online hoje mesmo
            </p>
                <a id="comecar-agora" href="{{ route('register') }}" class="btn-hero fade-in-up">
                <i class="fas fa-rocket mr-2"></i>Começar Agora - É Grátis
            </a>
        </div>
    </div>
</section>
@endsection 