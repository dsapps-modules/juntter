<nav class="main-header navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a id="logo-navbar" href="{{ route('checkout') }}" class="navbar-brand">
            <img src="{{ asset('logo/JUNTTER-MODELO-1-SF.webp') }}" alt="Juntter" class="brand-image" style="height:36px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a id="beneficioslink" href="{{ route('checkout') }}#beneficios" class="nav-link">Benefícios</a></li>
                <li class="nav-item"><a id="precoslink" href="{{ route('checkout') }}#precos" class="nav-link">Preços</a></li>
                <li class="nav-item"><a id="como-funcionalink" href="{{ route('checkout') }}#como-funciona" class="nav-link">Como Funciona</a></li>
                <li class="nav-item"><a id="depoimentoslink" href="{{ route('checkout') }}#depoimentos" class="nav-link">Depoimentos</a></li>
                <li class="nav-item"><a id="faqlink" href="{{ route('checkout') }}#faq" class="nav-link">FAQ</a></li>
                
                <!-- Sempre mostrar apenas o botão de login na página pública -->
                <li class="nav-item"><a id="login-navbar" href="{{ route('login.redirect') }}" class="btn btn-warning ml-2 px-4">Login</a></li>
            </ul>
        </div>
    </div>
</nav> 