<footer class="footer-juntter" id="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="footer-section">
                    <h5>Juntter</h5>
                    <p>Checkout digital que vende por você. Simplifique seus pagamentos online com segurança e praticidade.</p>
                    <div class="mt-3">
                        <a href="#" class="mr-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="mr-3"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 mb-4">
                <div class="footer-section">
                    <h5>Produto</h5>
                    <a id="beneficiosfooter" href="{{ route('checkout') }}#beneficios">Benefícios</a><br>
                    <a id="precosfooter" href="{{ route('checkout') }}#precos">Preços</a><br>
                    <a id="como-funcionafooter" href="{{ route('checkout') }}#como-funciona">Como Funciona</a><br>
                    <a id="apifooter" href="#">API</a>
                </div>
            </div>
            
        </div>
        <hr style="border-color: #444; margin: 40px 0 20px;">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; {{ date('Y') }} Juntter. Todos os direitos reservados.</p>
            </div>
            <div class="col-md-6 text-md-right">
                <p class="mb-0"> (11) 4003-5442 |  info@juntter.com.br</p>
            </div>
        </div>
    </div>
</footer>

<div class="chat-widget" onclick="openChat()">
    <i class="fas fa-comments" style="color: var(--dark-color); font-size: 1.5rem;"></i>
</div> 