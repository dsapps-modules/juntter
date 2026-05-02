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
                    Venda online com segurança e receba em até 1 dia útil.
                </p>
                <a href="{{ route('login') }}" class="btn-hero animate__animated animate__fadeInUp animate__delay-2s">
                    <i class="fas fa-sign-in-alt mr-2"></i>Entrar para Começar
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
                        <p>Crie links personalizados em segundos. Envie por WhatsApp, email ou redes sociais. Sem
                            complicação.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="benefit-card fade-in-up">
                        <div class="benefit-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h4>PIX</h4>
                        <p>Receba pagamentos via PIX em 1 dia útil. QR Code automático e chave PIX integrada para máxima
                            praticidade.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="benefit-card fade-in-up">
                        <div class="benefit-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h4>Checkout Personalizado</h4>
                        <p>Design profissional com sua marca. Cores, logo e layout que convertem mais. Totalmente
                            responsivo.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="benefit-card fade-in-up">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Analytics Avançado</h4>
                        <p>Relatórios detalhados de vendas, conversão e performance. Dashboards intuitivos para crescer seu
                            negócio.</p>
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

            @php
                $acelerarRates = [
                    '2x' => 6.53,
                    '3x' => 8.35,
                    '4x' => 10.15,
                    '5x' => 11.87,
                    '6x' => 13.01,
                    '7x' => 14.11,
                    '8x' => 15.21,
                    '9x' => 16.28,
                    '10x' => 17.32,
                    '11x' => 18.36,
                    '12x' => 19.37,
                    '13x' => 20.58,
                    '14x' => 21.79,
                    '15x' => 23.00,
                    '16x' => 24.21,
                    '17x' => 25.42,
                    '18x' => 26.63,
                ];

                $turboRates = [
                    '2x' => 5.63,
                    '3x' => 7.20,
                    '4x' => 8.75,
                    '5x' => 10.23,
                    '6x' => 11.21,
                    '7x' => 12.17,
                    '8x' => 13.11,
                    '9x' => 14.03,
                    '10x' => 14.93,
                    '11x' => 15.83,
                    '12x' => 16.70,
                    '13x' => 17.71,
                    '14x' => 18.73,
                    '15x' => 19.74,
                    '16x' => 20.76,
                    '17x' => 21.77,
                    '18x' => 22.79,
                ];

                $economicoRates = [
                    '2x' => 3.75,
                    '3x' => 3.75,
                    '4x' => 3.75,
                    '5x' => 3.75,
                    '6x' => 3.75,
                    '7x' => 3.75,
                    '8x' => 3.75,
                    '9x' => 3.75,
                    '10x' => 3.75,
                    '11x' => 3.75,
                    '12x' => 3.75,
                    '13x' => 5.98,
                    '14x' => 5.98,
                    '15x' => 5.98,
                    '16x' => 5.98,
                    '17x' => 5.98,
                    '18x' => 5.98,
                ];
            @endphp

            <div class="row justify-content-center">
                <div class="col-lg-3 mb-4" id="acelerar">
                    <div class="pricing-card fade-in-up">
                        <h3>Acelerar</h3>
                        <div class="pricing-rates">
                            <div class="rate-toggle">
                                <div class="rate-selector">
                                    <span>Crédito à vista</span>
                                    <x-form.payment-selector plan="acelerar" :rates="$acelerarRates" />
                                </div>
                            </div>
                            <div class="price-display-dual">
                                <div class="price-item">
                                    <span class="price-value">4,97%</span>
                                    <span class="price-label">Crédito à vista</span>
                                </div>
                                <div class="price-item">
                                    <span class="price-value parcelado-rate" data-plan="acelerar">13,01%</span>
                                    <span class="price-label parcelado-label" data-plan="acelerar">Parcelado 6x</span>
                                </div>
                            </div>
                            <p class="text-center">Ideal para quem vende mais parcelado</p>
                            <div class="fixed-rates">
                                <div class="rate-item">
                                    <i class="fas fa-qrcode"></i>
                                    <span>PIX</span>
                                    <strong>0,99%</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Boleto Pago</span>
                                    <strong>R$ 2,99</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-link"></i>
                                    <span>Link Pago</span>
                                    <strong>R$ 1,00</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Recebimento</span>
                                    <strong>1 dia</strong>
                                </div>
                            </div>
                        </div>
                        <ul id="id-plano-mais" class="list-unstyled mt-4 mb-4">
                            <li><i class="fas fa-check text-success mr-2"></i>Links ilimitados</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Checkout básico</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Suporte por email</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios básicos</li>
                        </ul>
                        <a href="{{ route('login') }}" class="btn btn-outline-warning btn-block"
                            id="btn-plano-mais">Entrar e Usar</a>
                    </div>
                </div>
                <div class="col-lg-3 mb-4" id="turbo">
                    <div class="pricing-card popular fade-in-up">
                        <h3>Turbo</h3>
                        <div class="pricing-rates">
                            <div class="rate-toggle">
                                <div class="rate-selector">
                                    <span>Crédito à vista</span>
                                    <x-form.payment-selector plan="turbo" :rates="$turboRates" />
                                </div>
                            </div>
                            <div class="price-display-dual">
                                <div class="price-item">
                                    <span class="price-value">4,28%</span>
                                    <span class="price-label">Crédito à vista</span>
                                </div>
                                <div class="price-item">
                                    <span class="price-value parcelado-rate" data-plan="turbo">11,21%</span>
                                    <span class="price-label parcelado-label" data-plan="turbo">Parcelado 6x</span>
                                </div>
                            </div>
                            <p class="text-center">Ideal para quem vende mais à vista</p>
                            <div class="fixed-rates">
                                <div class="rate-item">
                                    <i class="fas fa-qrcode"></i>
                                    <span>PIX</span>
                                    <strong>0,99%</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Boleto Pago</span>
                                    <strong>R$ 2,99</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-link"></i>
                                    <span>Link Pago</span>
                                    <strong>R$ 1,00</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Recebimento</span>
                                    <strong>1 dia</strong>
                                </div>
                            </div>
                        </div>
                        <ul id="id-plano-negocio" class="list-unstyled mt-4 mb-4">
                            <li><i class="fas fa-check text-success mr-2"></i>Tudo do plano Mais</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Checkout personalizado</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Analytics avançado</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Suporte prioritário</li>
                            <li><i class="fas fa-check text-success mr-2"></i>API básica</li>
                        </ul>
                        <a href="{{ route('login') }}" class="btn btn-warning btn-block" id="btn-plano-negocio">Entrar e
                            Usar</a>
                    </div>
                </div>
                <div class="col-lg-3 mb-4" id="economico">
                    <div class="pricing-card fade-in-up">
                        <h3>Econômico</h3>
                        <div class="pricing-rates">
                            <div class="rate-toggle">
                                <div class="rate-selector">
                                    <span>Crédito à vista</span>
                                    <x-form.payment-selector plan="economico" :rates="$economicoRates" />
                                </div>
                            </div>
                            <div class="price-display-dual">
                                <div class="price-item">
                                    <span class="price-value">3,49%</span>
                                    <span class="price-label">Crédito à vista</span>
                                </div>
                                <div class="price-item">
                                    <span class="price-value parcelado-rate" data-plan="economico">3,75%</span>
                                    <span class="price-label parcelado-label" data-plan="economico">Parcelado 6x</span>
                                </div>
                            </div>
                            <p class="text-center">Ideal para quem não precisa de antecipação</p>
                            <div class="fixed-rates">
                                <div class="rate-item">
                                    <i class="fas fa-qrcode"></i>
                                    <span>PIX</span>
                                    <strong>0,99%</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Boleto</span>
                                    <strong>R$ 2,99</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-link"></i>
                                    <span>Link Pago</span>
                                    <strong>R$ 1,00</strong>
                                </div>
                                <div class="rate-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Recebimento</span>
                                    <strong>Conforme parcelas</strong>
                                </div>
                            </div>
                        </div>
                        <ul id="id-plano-flex" class="list-unstyled mt-4 mb-4">
                            <li><i class="fas fa-check text-success mr-2"></i>Tudo do plano Negócio</li>
                            <li><i class="fas fa-check text-success mr-2"></i>API completa</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Suporte 24/7</li>
                            <li><i class="fas fa-check text-success mr-2"></i>White label</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Webhooks avançados</li>
                        </ul>
                        <a href="{{ route('login') }}" class="btn btn-outline-warning btn-block"
                            id="btn-plano-flex">Entrar e Usar</a>
                    </div>
                </div>
                <div class="col-lg-3 mb-4" id="custom">
                    <div class="pricing-card fade-in-up">
                        <h3>Customizado</h3>
                        <div class="pricing-rates">
                            <div class="price-display">
                                <span class="price-variable">Sob consulta</span>
                            </div>
                            <p class="text-center">Taxas negociadas</p>
                            <div class="fixed-rates">
                                <div class="rate-item">
                                    <span>Volumes altos</span>
                                </div>
                                <div class="rate-item">
                                    <span>Ideal para quem precisa de condições personalizadas</span>
                                </div>
                            </div>
                        </div>
                        <ul id="id-plano-customizado" class="list-unstyled mt-4 mb-4">
                            <li><i class="fas fa-check text-success mr-2"></i>Tudo do plano Flex</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Taxas negociadas</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Account manager</li>
                            <li><i class="fas fa-check text-success mr-2"></i>SLA personalizado</li>
                            <li><i class="fas fa-check text-success mr-2"></i>Consultoria especializada</li>
                        </ul>
                        <a href="#" class="btn btn-outline-warning btn-block" id="btn-plano-customizado"
                            onclick="abrirWhatsApp()">Falar com Especialista</a>
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
                        <p>Cadastre seu produto ou serviço, defina o valor e personalize a página de checkout com sua marca.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="benefit-card fade-in-up">
                        <div class="benefit-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <h4>2. Compartilhe</h4>
                        <p>Envie o link por WhatsApp, email, redes sociais ou incorpore no seu site. Seus clientes acessam e
                            pagam facilmente.</p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="benefit-card fade-in-up">
                        <div class="benefit-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h4>3. Receba</h4>
                        <p>PIX e cartão em 1 dia útil. Acompanhe todas as vendas em tempo real no seu dashboard.</p>
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
                            Antes eu perdia vendas porque era difícil receber pagamentos online. Com o Juntter, criei links
                            em segundos e minhas vendas de cursos aumentaram 200%!
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
                            Integrei a API do Juntter no meu e-commerce e automatizei tudo. Checkout personalizado, PIX em 1
                            dia útil e relatórios detalhados. Perfeito!
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
                            <p>Você cria um link personalizado para seu produto/serviço. O cliente clica, acessa uma página
                                profissional de checkout e paga via PIX, cartão ou boleto. Simples e seguro!</p>
                        </div>
                    </div>

                    <div class="faq-item fade-in-up">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Posso personalizar a página de checkout? <i class="fas fa-chevron-down float-right"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Sim! Você pode adicionar sua logo, cores da marca, descrições personalizadas e até mesmo
                                campos extras. Tudo para transmitir profissionalismo.</p>
                        </div>
                    </div>

                    <div class="faq-item fade-in-up">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Quanto tempo demora para receber? <i class="fas fa-chevron-down float-right"></i>
                        </button>
                        <div class="faq-answer">
                            <p>PIX e cartão em 1 dia útil. Boleto: 2 dias úteis após compensação. Tudo automatizado em sua
                                conta.</p>
                        </div>
                    </div>

                    <div class="faq-item fade-in-up">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Posso integrar com meu sistema? <i class="fas fa-chevron-down float-right"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Claro! Temos API completa, webhooks e SDKs para as principais linguagens. Documentação
                                detalhada e suporte técnico especializado.</p>
                        </div>
                    </div>

                    <div class="faq-item fade-in-up">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            É seguro para meus clientes? <i class="fas fa-chevron-down float-right"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Totalmente! Certificação PCI DSS, SSL 256 bits, dados criptografados e infraestrutura
                                bancária. Seus clientes pagam com total segurança.</p>
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
                    Acesse sua conta e comece a receber pagamentos online hoje mesmo
                </p>
                <a id="comecar-agora" href="{{ route('login') }}" class="btn-hero fade-in-up">
                    <i class="fas fa-sign-in-alt mr-2"></i>Entrar para Começar
                </a>
            </div>
        </div>
    </section>

    <script>
        function abrirWhatsApp() {
            const numeroWhatsApp = '5511999999999';
            const mensagem = 'Olá! Gostaria de saber mais sobre os planos personalizados do Juntter Checkout.';
            const url = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensagem)}`;
            window.open(url, '_blank');
        }
    </script>
@endsection


