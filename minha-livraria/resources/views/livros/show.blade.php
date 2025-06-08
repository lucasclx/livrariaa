@extends('layouts.app')

@section('title', $livro->titulo . ' - Livraria Elegante')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('livros.index') }}">
                        <i class="fas fa-book-open"></i> Catálogo
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('livros.index', ['categoria' => $livro->categoria->id]) }}">
                        {{ $livro->categoria->nome }}
                    </a>
                </li>
                <li class="breadcrumb-item active">{{ Str::limit($livro->titulo, 50) }}</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Imagem do livro -->
            <div class="col-lg-4 col-md-5 mb-4">
                <div class="position-relative">
                    <div class="card book-card floating-book">
                        <div class="book-cover">
                            <img src="{{ $livro->imagem_capa }}" 
                                 class="card-img-top w-100" 
                                 alt="{{ $livro->titulo }}"
                                 style="height: 500px; object-fit: cover;">
                        </div>
                        
                        @if($livro->destaque)
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-warning text-dark fs-6">⭐ Destaque</span>
                            </div>
                        @endif
                        
                        @if($livro->preco_promocional)
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-danger fs-6">🔥 Promoção</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informações do livro -->
            <div class="col-lg-8 col-md-7">
                <div class="mb-3">
                    <span class="badge badge-category fs-6">{{ $livro->categoria->nome }}</span>
                </div>

                <h1 class="display-5 mb-3">{{ $livro->titulo }}</h1>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-user text-primary"></i>
                            <strong>Autor:</strong> {{ $livro->autor }}
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-building text-primary"></i>
                            <strong>Editora:</strong> {{ $livro->editora }}
                        </p>
                        @if($livro->isbn)
                            <p class="mb-2">
                                <i class="fas fa-barcode text-primary"></i>
                                <strong>ISBN:</strong> {{ $livro->isbn }}
                            </p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        @if($livro->paginas)
                            <p class="mb-2">
                                <i class="fas fa-file-alt text-primary"></i>
                                <strong>Páginas:</strong> {{ $livro->paginas }}
                            </p>
                        @endif
                        <p class="mb-2">
                            <i class="fas fa-language text-primary"></i>
                            <strong>Idioma:</strong> {{ $livro->idioma }}
                        </p>
                        @if($livro->data_publicacao)
                            <p class="mb-2">
                                <i class="fas fa-calendar text-primary"></i>
                                <strong>Publicação:</strong> {{ $livro->data_publicacao->format('d/m/Y') }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Preços -->
                <div class="card mb-4" style="background: linear-gradient(135deg, rgba(218, 165, 32, 0.1) 0%, rgba(139, 69, 19, 0.1) 100%);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                @if($livro->preco_promocional)
                                    <div class="mb-2">
                                        <span class="text-muted text-decoration-line-through fs-5">
                                            De: R$ {{ number_format($livro->preco, 2, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="price display-6 text-success">
                                        Por: R$ {{ number_format($livro->preco_promocional, 2, ',', '.') }}
                                    </div>
                                    <div class="text-success">
                                        <i class="fas fa-tags"></i>
                                        Economize R$ {{ number_format($livro->preco - $livro->preco_promocional, 2, ',', '.') }}
                                        ({{ number_format((($livro->preco - $livro->preco_promocional) / $livro->preco) * 100, 0) }}% OFF)
                                    </div>
                                @else
                                    <div class="price display-6">
                                        R$ {{ number_format($livro->preco, 2, ',', '.') }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <div class="mb-2">
                                        <span class="badge {{ $livro->status_estoque['classe'] }} fs-6">
                                            {{ $livro->status_estoque['texto'] }}
                                        </span>
                                    </div>
                                    @if($livro->estoque > 0)
                                        <p class="text-success mb-0">
                                            <i class="fas fa-check-circle"></i>
                                            {{ $livro->estoque }} unidades disponíveis
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações de compra -->
                @if($livro->estoque > 0)
                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="{{ route('carrinho.adicionar') }}" method="POST" class="row g-3">
                                @csrf
                                <input type="hidden" name="livro_id" value="{{ $livro->id }}">
                                
                                <div class="col-md-3">
                                    <label for="quantidade" class="form-label">Quantidade:</label>
                                    <select class="form-select" name="quantidade" id="quantidade">
                                        @for($i = 1; $i <= min($livro->estoque, 10); $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                
                                <div class="col-md-9 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                        <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                                    </button>
                                    
                                    <button type="button" class="btn btn-gold btn-lg">
                                        <i class="fas fa-heart"></i> Favoritar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Produto Indisponível</strong><br>
                        Este livro está temporariamente fora de estoque. 
                        <a href="#" class="alert-link">Clique aqui para ser notificado quando estiver disponível.</a>
                    </div>
                @endif

                <!-- Descrição -->
                @if($livro->descricao)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Descrição
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">{{ $livro->descricao }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Livros relacionados -->
        @if($livrosRelacionados->count() > 0)
            <div class="mt-5">
                <h3 class="mb-4">
                    <i class="fas fa-book-reader"></i> 
                    Outros livros da categoria "{{ $livro->categoria->nome }}"
                </h3>
                
                <div class="row">
                    @foreach($livrosRelacionados as $relacionado)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card book-card h-100">
                                <div class="position-relative">
                                    <div class="book-cover">
                                        <img src="{{ $relacionado->imagem_capa }}" 
                                             class="card-img-top" 
                                             alt="{{ $relacionado->titulo }}"
                                             style="height: 250px; object-fit: cover;">
                                    </div>
                                    
                                    @if($relacionado->destaque)
                                        <div class="position-absolute top-0 start-0 m-2">
                                            <span class="badge bg-warning text-dark">⭐ Destaque</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title mb-2">{{ Str::limit($relacionado->titulo, 40) }}</h6>
                                    <p class="card-text text-muted mb-2 small">
                                        <i class="fas fa-user"></i> {{ Str::limit($relacionado->autor, 30) }}
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="price">
                                                R$ {{ number_format($relacionado->preco_final, 2, ',', '.') }}
                                            </span>
                                            <span class="badge {{ $relacionado->status_estoque['classe'] }}">
                                                {{ $relacionado->status_estoque['texto'] }}
                                            </span>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <a href="{{ route('livros.show', $relacionado->slug) }}" 
                                               class="btn btn-outline-elegant btn-sm">
                                                <i class="fas fa-eye"></i> Ver Detalhes
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Atualizar preço total baseado na quantidade
    document.getElementById('quantidade').addEventListener('change', function() {
        const quantidade = parseInt(this.value);
        const precoUnitario = {{ $livro->preco_final }};
        const precoTotal = quantidade * precoUnitario;
        
        // Aqui você pode adicionar lógica para mostrar o preço total
        console.log('Preço total: R$ ' + precoTotal.toFixed(2).replace('.', ','));
    });
    
    // Confirmação antes de adicionar ao carrinho
    document.querySelector('form').addEventListener('submit', function(e) {
        const quantidade = document.getElementById('quantidade').value;
        const titulo = '{{ $livro->titulo }}';
        
        if (!confirm(`Adicionar ${quantidade} unidade(s) de "${titulo}" ao carrinho?`)) {
            e.preventDefault();
        } else {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
            button.disabled = true;
        }
    });
</script>
@endpush