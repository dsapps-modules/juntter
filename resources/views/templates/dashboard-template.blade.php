<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{asset('img/favicon.png')}}" type="image/x-icon">
    <title>@yield('title', 'Dashboard') - Juntter</title>
    
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/juntter-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-styles.css') }}">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark dashboard-navbar fixed-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('logo/JUNTTER-MODELO-1-SF.webp') }}" alt="Juntter" height="45" class="logo-img">
            </a>
            
            <!-- Mobile menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Navigation Menu -->
                <div class="navbar-nav mx-auto">
                    <div class="dropdown">
                        <button class="nav-link dropdown-toggle menu-item btn" type="button" id="cobrancaDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-credit-card me-2"></i>
                            <span>Cobrança</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-modern" aria-labelledby="cobrancaDropdown">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            
                            @if(Auth::user()->isSuperAdminOrAdminOrVendedor())
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('cobranca.index') }}"><i class="fas fa-file-invoice me-2"></i>Cobrança Única</a></li>
                            <li><a class="dropdown-item" href="{{ route('cobranca.recorrente') }}"><i class="fas fa-sync-alt me-2"></i>Cobrança Recorrente</a></li>
                            <li><a class="dropdown-item" href="{{ route('cobranca.planos') }}"><i class="fas fa-list-alt me-2"></i>Planos de Cobrança</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('cobranca.pix') }}"><i class="fas fa-paper-plane me-2"></i>Enviar Pix</a></li>
                            <li><a class="dropdown-item" href="{{ route('cobranca.pagarcontas') }}"><i class="fas fa-file-invoice-dollar me-2"></i>Pagar Contas</a></li>
                            @endif
                            
                            <li><a class="dropdown-item" href="{{ route('cobranca.saldoextrato') }}"><i class="fas fa-wallet me-2"></i>Saldo e Extrato</a></li>
                        </ul>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="navbar-nav ms-auto">
                    <div class="d-flex align-items-center">
                        <div class="user-info-container me-3">
                            <div class="user-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="user-details">
                                <span class="user-name">{{ Auth::user()->name }}</span>
                                <span class="user-role">{{ ucfirst(Auth::user()->role) }}</span>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn logout-btn" title="Sair">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-content" >
        <div class="container">
            @yield('content')
        </div>
    </div>

    <!-- AdminLTE Scripts -->
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('adminlte/dist/js/adminlte.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    
    <!-- DataTables Scripts -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    
    <!-- Dashboard Scripts -->
    <script src="{{ asset('js/dashboard.js') }}"></script>
    
    <script>
        // Garantir que dropdowns funcionem
        $(document).ready(function() {
            // Inicializar dropdowns do Bootstrap
            $('.dropdown-toggle').each(function() {
                new bootstrap.Dropdown(this, {
                    boundary: 'viewport'
                });
            });
            
            
        });
    </script>
    
    @yield('scripts')
</body>
</html>

