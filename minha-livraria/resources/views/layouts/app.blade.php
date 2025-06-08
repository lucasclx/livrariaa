<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Livraria Elegante')</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lora:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-brown: #8B4513;
            --dark-brown: #654321;
            --light-brown: #D2B48C;
            --cream: #F5F5DC;
            --gold: #DAA520;
            --dark-gold: #B8860B;
            --paper: #FDF6E3;
            --ink: #2C1810;
            --aged-paper: #F4F1E8;
            --burgundy: #800020;
            --forest-green: #228B22;
        }

        body {
            font-family: 'Lora', serif;
            background: linear-gradient(135deg, var(--aged-paper) 0%, var(--cream) 100%);
            color: var(--ink);
            min-height: 100vh;
            position: relative;
        }

        /* Background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(218, 165, 32, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 80% 20%, rgba(139, 69, 19, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 40% 80%, rgba(218, 165, 32, 0.03) 0%, transparent 20%);
            pointer-events: none;
            z-index: -1;
        }

        /* Header Styles */
        .navbar {
            background: linear-gradient(135deg, var(--dark-brown) 0%, var(--primary-brown) 100%);
            box-shadow: 0 4px 20px rgba(139, 69, 19, 0.3);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--gold) !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand::before {
            content: '📚';
            font-size: 1.5em;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
        }

        .nav-link {
            color: var(--cream) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 1rem !important;
            border-radius: 25px;
        }

        .nav-link:hover {
            color: var(--gold) !important;
            background: rgba(218, 165, 32, 0.1);
            transform: translateY(-2px);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gold);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 80%;
        }

        /* Card Styles */
        .card {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(139, 69, 19, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(139, 69, 19, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, var(--gold) 0%, var(--dark-gold) 100%);
            color: white;
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            border-bottom: none;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        /* Book Card Styles */
        .book-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .book-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.3), transparent);
            transition: left 0.6s ease;
            z-index: 1;
        }

        .book-card:hover::before {
            left: 100%;
        }

        .book-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 50px rgba(139, 69, 19, 0.25);
        }

        .book-cover {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }

        .book-cover::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(139, 69, 19, 0.1) 100%);
        }

        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--dark-brown) 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-brown) 0%, var(--primary-brown) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4);
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--gold) 0%, var(--dark-gold) 100%);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
        }

        .btn-gold:hover {
            background: linear-gradient(135deg, var(--dark-gold) 0%, var(--gold) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(218, 165, 32, 0.4);
        }

        .btn-outline-elegant {
            border: 2px solid var(--primary-brown);
            color: var(--primary-brown);
            background: transparent;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-elegant:hover {
            background: var(--primary-brown);
            color: white;
            transform: translateY(-2px);
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 15px;
            border-left: 5px solid;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(34, 139, 34, 0.1) 0%, rgba(34, 139, 34, 0.05) 100%);
            border-left-color: var(--forest-green);
            color: var(--forest-green);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(128, 0, 32, 0.1) 0%, rgba(128, 0, 32, 0.05) 100%);
            border-left-color: var(--burgundy);
            color: var(--burgundy);
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            color: var(--dark-brown);
            font-weight: 600;
        }

        .page-title {
            font-size: 2.5rem;
            color: var(--dark-brown);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60%;
            height: 3px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--dark-gold) 100%);
            border-radius: 2px;
        }

        /* Price styling */
        .price {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--forest-green);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        /* Badge Styles */
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .badge-category {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--primary-brown) 100%);
            color: white;
        }

        .badge-stock-ok {
            background: linear-gradient(135deg, var(--forest-green) 0%, #32CD32 100%);
        }

        .badge-stock-low {
            background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
        }

        .badge-stock-out {
            background: linear-gradient(135deg, var(--burgundy) 0%, #DC143C 100%);
        }

        /* Search and Filter Styles */
        .filter-card {
            background: rgba(245, 245, 220, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(139, 69, 19, 0.1);
        }

        .form-control, .form-select {
            border: 2px solid rgba(139, 69, 19, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--ink);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
            background: white;
        }

        /* Stats Card */
        .stats-card {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1) 0%, rgba(139, 69, 19, 0.1) 100%);
            border: 1px solid rgba(218, 165, 32, 0.3);
        }

        .stat-number {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            background: linear-gradient(135deg, var(--gold) 0%, var(--dark-gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--dark-brown) 0%, var(--ink) 100%);
            color: var(--cream);
            padding: 2rem 0;
            margin-top: 4rem;
        }

        /* Animation */
        @keyframes bookFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .floating-book {
            animation: bookFloat 6s ease-in-out infinite;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
        }

        /* Loading animation */
        .loading-books::before {
            content: '📖 📚 📖';
            font-size: 1.5rem;
            animation: bookFloat 2s ease-in-out infinite;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--aged-paper);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--dark-brown) 100%);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--dark-brown) 0%, var(--primary-brown) 100%);
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('livros.index') }}">
                Livraria Elegante
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="{{ route('livros.index') }}">
                        <i class="fas fa-book-open me-1"></i> Catálogo
                    </a>
                    @can('admin')
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-cog me-1"></i> Admin
                    </a>
                    @endcan
                    <a class="nav-link position-relative" href="{{ route('carrinho.index') }}">
                        <i class="fas fa-shopping-cart"></i>
                        @php
                            $cartCount = session('cart_id') ? \App\Models\Carrinho::withCount('itens')->find(session('cart_id'))?->itens_count : 0;
                        @endphp
                        @if($cartCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>
                    @auth
                        <a class="nav-link" href="{{ route('pedidos.index') }}">
                            <i class="fas fa-list me-1"></i> Meus Pedidos
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Atenção!</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>📚 Livraria Elegante</h5>
                    <p class="mb-0">Organizando conhecimento, inspirando leitores.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-heart text-danger"></i>
                        Feito com amor pelos livros
                    </p>
                    <small>© {{ date('Y') }} - Sistema de E-commerce para Livraria</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Image preview function
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(previewId).src = e.target.result;
                    document.getElementById(previewId).style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // CSRF token for AJAX requests
        window.Laravel = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
    </script>
    
    @stack('scripts')
</body>
</html>