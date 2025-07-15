<nav class="main-header navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a href="{{ route('checkout') }}" class="navbar-brand">
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
                
                @auth
                    <!-- Usuário logado -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user mr-1"></i>{{ Auth::user()->name }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            @switch(Auth::user()->nivel_acesso)
                                @case('super_admin')
                                    <a class="dropdown-item" href="{{ route('super_admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                    @break
                                @case('admin')
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                    @break
                                @case('vendedor')
                                    <a class="dropdown-item" href="{{ route('vendedor.dashboard') }}">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                    @break
                                @case('comprador')
                                    <a class="dropdown-item" href="{{ route('comprador.dashboard') }}">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                    @break
                            @endswitch
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
                                </button>
                            </form>
                        </div>
                    </li>
                @else
                    <!-- Usuário não logado -->
                    <li class="nav-item"><a id="login-navbar" href="{{ route('login') }}" class="btn btn-warning ml-2 px-4">Login</a></li>
                    <li class="nav-item"><a id="criarconta-navbar" href="{{ route('register') }}" class="btn btn-outline-warning ml-2 px-4">Criar Conta</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav> 