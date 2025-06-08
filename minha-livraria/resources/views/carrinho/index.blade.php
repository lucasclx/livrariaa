@extends('layouts.app')

@section('title', 'Carrinho de Compras - Livraria Elegante')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">🛒 Carrinho de Compras</h1>
            <a href="{{ route('livros.index') }}" class="btn btn-outline-elegant">
                <i class="fas fa-arrow-left"></i> Continuar Comprando
            </a>
        </div>

        @if($carrinho && $itens->count() > 0)
            <div class="row">
                <!-- Itens do carrinho -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> 
                                Itens no Carrinho ({{ $carrinho->total_itens }})
                            </h5>
                            <form action="{{ route('carrinho.limpar') }}" method="POST" 
                                  onsubmit="return confirm('Tem certeza que deseja limpar todo o carrinho?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Limpar Carrinho
                                </button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            @foreach($itens as $item)
                                <div class="border-bottom p-3">
                                    <div class="row align-items-center">
                                        <!-- Imagem do livro -->
                                        <div class="col-md-2 col-3 mb-3 mb-md-0">
                                            <img src="{{ $item->livro->imagem_capa }}" 
                                                 class="img-fluid rounded" 
                                                 alt="{{ $item->livro->titulo }}"
                                                 style="max-height: 100px; object-fit: cover;">
                                        </div>
                                        
                                        <!-- Informações do livro -->
                                        <div class="col-md-4 col-9">
                                            <h6 class="mb-1">
                                                <a href="{{ route('livros.show', $item->livro->slug) }}" 
                                                   class="text-decoration-none">
                                                    {{ $item->livro->titulo }}
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-1 small">
                                                <i class="fas fa-user"></i> {{ $item->livro->autor }}
                                            </p>
                                            <p class="text-muted mb-1 small">
                                                <i class="fas fa-folder"></i> {{ $item->livro->categoria->nome }}
                                            </p>
                                            <span class="badge {{ $item->livro->status_estoque['classe'] }}">
                                                {{ $item->livro->status_estoque['texto'] }}
                                            </span>
                                        </div>
                                        
                                        <!-- Preço unitário -->
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="small text-muted">Preço Unit.</div>
                                            <div class="fw-bold">
                                                R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}
                                            </div>
                                        </div>
                                        
                                        <!-- Quantidade -->
                                        <div class="col-md-2 col-6 text-center mb-2 mb-md-0">
                                            <div class="small text-muted">Quantidade</div>
                                            <form action="{{ route('carrinho.atualizar', $item) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <div class="input-group input-group-sm" style="max-width: 80px; margin: 0 auto;">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity({{ $item->id }}, -1)">-</button>
                                                    <input type="number" 
                                                           class="form-control text-center" 
                                                           name="quantidade" 
                                                           value="{{ $item->quantidade }}" 
                                                           min="1" 
                                                           max="{{ $item->livro->estoque }}"
                                                           onchange="this.form.submit()"
                                                           id="qty-{{ $item->id }}">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="changeQuantity({{ $item->id }}, 1)">+</button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- Subtotal e ações -->
                                        <div class="col-md-2 col-12 text-center">
                                            <div class="small text-muted">Subtotal</div>
                                            <div class="fw-bold text-success mb-2">
                                                R$ {{ number_format($item->subtotal, 2, ',', '.') }}
                                            </div>
                                            <form action="{{ route('carrinho.remover', $item) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Remover este item do carrinho?')"
                                                        title="Remover item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Resumo do pedido -->
                <div class="col-lg-4">
                    <div class="card position-sticky" style="top: 100px;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator"></i> Resumo do Pedido
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal ({{ $carrinho->total_itens }} itens):</span>
                                <span>R$ {{ number_format($carrinho->total, 2, ',', '.') }}</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frete:</span>
                                <span class="text-success">Grátis</span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-success fs-5">
                                    R$ {{ number_format($carrinho->total, 2, ',', '.') }}
                                </strong>
                            </div>
                            
                            @auth
                                <div class="d-grid gap-2">
                                    <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg">
                                        <i class="fas fa-credit-card"></i> Finalizar Compra
                                    </a>
                                    <a href="{{ route('livros.index') }}" class="btn btn-outline-elegant">
                                        <i class="fas fa-plus"></i> Adicionar Mais Livros
                                    </a>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Faça login</strong> para finalizar sua compra.
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('login') }}" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Fazer Login
                                    </a>
                                    <a href="{{ route('register') }}" class="btn btn-outline-elegant">
                                        <i class="fas fa-user-plus"></i> Criar Conta
                                    </a>
                                </div>
                            @endauth
                        </div>
                        
                        <!-- Informações adicionais -->
                        <div class="card-footer bg-light">
                            <div class="row text-center">
                                <div class="col-4">
                                    <i class="fas fa-shipping-fast text-success"></i>
                                    <div class="small">Frete Grátis</div>
                                </div>
                                <div class="col-4">
                                    <i class="fas fa-shield-alt text-success"></i>
                                    <div class="small">Compra Segura</div>
                                </div>
                                <div class="col-4">
                                    <i class="fas fa-undo text-success"></i>
                                    <div class="small">7 dias p/ trocar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <!-- Carrinho vazio -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                </div>
                <h3>Seu carrinho está vazio</h3>
                <p class="text-muted mb-4">
                    Que tal começar adicionando alguns livros incríveis?
                </p>
                <a href="{{ route('livros.index') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-book-open"></i> Explorar Catálogo
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Toast para feedback -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cartToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-shopping-cart text-success me-2"></i>
            <strong class="me-auto">Carrinho</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Item atualizado com sucesso!
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function changeQuantity(itemId, change) {
        const input = document.getElementById('qty-' + itemId);
        const currentValue = parseInt(input.value);
        const newValue = currentValue + change;
        const maxValue = parseInt(input.getAttribute('max'));
        
        if (newValue >= 1 && newValue <= maxValue) {
            input.value = newValue;
            input.form.submit();
        }
    }
    
    // Auto-submit com delay para quantidade
    let timeoutId;
    document.querySelectorAll('input[name="quantidade"]').forEach(function(input) {
        input.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                this.form.submit();
            }, 1000); // Aguarda 1 segundo após parar de digitar
        });
    });
    
    // Mostrar toast se houver mensagem de sucesso
    @if(session('success'))
        const toast = new bootstrap.Toast(document.getElementById('cartToast'));
        document.getElementById('toastMessage').textContent = '{{ session('success') }}';
        toast.show();
    @endif
    
    // Confirmar remoção de item
    document.querySelectorAll('form button[title="Remover item"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja remover este item do carrinho?')) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush