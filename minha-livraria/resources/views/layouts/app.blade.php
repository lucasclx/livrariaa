<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Minha Livraria'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS customizado -->
    <style>
        /* Estilo customizado para a livraria */
        .page-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .book-card {
            transition: transform 0.2s ease-in-out;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .book-cover img {
            transition: transform 0.3s ease;
        }
        
        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }
        
        .badge-category {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-elegant {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-elegant:hover {
            background: linear-gradient(45deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        
        .btn-outline-elegant {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline-elegant:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-gold {
            background: linear-gradient(45deg, #f39c12 0%, #d68910 100%);
            border: none;
            color: white;
        }
        
        .btn-gold:hover {
            background: linear-gradient(45deg, #e67e22 0%, #ca6f1e 100%);
            color: white;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .filter-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        
        .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .floating-book {
            transition: transform 0.3s ease;
        }
        
        .floating-book:hover {
            transform: translateY(-3px);
        }
        
        /* Badges de estoque */
        .badge-stock-ok {
            background-color: #27ae60;
        }
        
        .badge-stock-low {
            background-color: #f39c12;
        }
        
        .badge-stock-out {
            background-color: #e74c3c;
        }
        
        /* Loading animation */
        .loading-books {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Navbar customizada */
        .navbar-elegant {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-elegant .navbar-brand,
        .navbar-elegant .nav-link {
            color: white !important;
        }
        
        .navbar-elegant .nav-link:hover {
            color: rgba(255,255,255,0.8) !important;
        }
        
        /* Footer */
        .footer-elegant {
            background: #2c3e50;
            color: white;
            margin-top: 3rem;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-elegant">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('livros.index') }}">
                <i class="fas fa-book-open me-2"></i>
                📚 Minha Livraria
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('livros.index') }}">
                            <i class="fas fa-book me-1"></i>Catálogo
                        </a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('pedidos.index') }}">
                                <i class="fas fa-receipt me-1"></i>Meus Pedidos
                            </a>
                        </li>
                    @endauth
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Carrinho -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('carrinho.index') }}">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge bg-warning text-dark rounded-pill position-absolute top-0 start-100 translate-middle">
                                {{ session('carrinho_count', 0) }}
                            </span>
                        </a>
                    </li>
                    
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">
                                <i class="fas fa-user-plus me-1"></i>Cadastre-se
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user-edit me-2"></i>Perfil
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('pedidos.index') }}">
                                    <i class="fas fa-receipt me-2"></i>Meus Pedidos
                                </a></li>
                                @if(Auth::user()->is_admin ?? false)
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-cog me-2"></i>Administração
                                    </a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mensagens de feedback -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show m-0" role="alert">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Conteúdo principal -->
    <main class="py-4">
        <div class="container">
            <!-- Exibir erros de validação -->
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Atenção!</h6>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-elegant py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-book-open me-2"></i>Minha Livraria</h5>
                    <p class="mb-0">Sua livraria online de confiança</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end justify-content-start">
                        <div class="me-4">
                            <i class="fas fa-phone me-2"></i>
                            (11) 99999-9999
                        </div>
                        <div>
                            <i class="fas fa-envelope me-2"></i>
                            contato@minhalivraria.com
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            © {{ date('Y') }} Minha Livraria. Todos os direitos reservados.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts customizados -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && alert.classList.contains('show')) {
                        bootstrap.Alert.getOrCreateInstance(alert).close();
                    }
                }, 5000);
            });
        });
        
        // Loading state for forms
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        const originalText = submitButton.innerHTML;
                        submitButton.innerHTML = '<span class="loading-books me-2"></span>Processando...';
                        
                        // Re-enable after 10 seconds as fallback
                        setTimeout(function() {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                        }, 10000);
                    }
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>