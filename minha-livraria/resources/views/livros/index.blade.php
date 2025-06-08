@extends('layouts.app')

@section('title', 'Catálogo de Livros - Livraria Elegante')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Cabeçalho da página -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">📚 Catálogo de Livros</h1>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">{{ $livros->total() }} livros encontrados</span>
            </div>
        </div>

        <!-- Estatísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-number">{{ $stats['total_livros'] }}</div>
                        <div class="text-muted">Total de Livros</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-number">{{ $stats['total_categorias'] }}</div>
                        <div class="text-muted">Categorias</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-number">{{ $stats['livros_destaque'] }}</div>
                        <div class="text-muted">Em Destaque</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-number">R$ {{ number_format($stats['valor_medio'], 2, ',', '.') }}</div>
                        <div class="text-muted">Preço Médio</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('livros.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <label for="busca" class="form-label">🔍 Buscar livros</label>
                        <input type="text" 
                               class="form-control" 
                               id="busca" 
                               name="busca" 
                               value="{{ request('busca') }}" 
                               placeholder="Título, autor ou editora...">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="categoria" class="form-label">📂 Categoria</label>
                        <select class="form-select" id="categoria" name="categoria">
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" 
                                        {{ request('categoria') == $categoria->id ? 'selected' : '' }}>
                                    {{ $categoria->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="preco_min" class="form-label">💰 Preço Min.</label>
                        <input type="number" 
                               class="form-control" 
                               id="preco_min" 
                               name="preco_min" 
                               value="{{ request('preco_min') }}" 
                               step="0.01" 
                               min="0">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="preco_max" class="form-label">💰 Preço Max.</label>
                        <input type="number" 
                               class="form-control" 
                               id="preco_max" 
                               name="preco_max" 
                               value="{{ request('preco_max') }}" 
                               step="0.01" 
                               min="0">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('livros.index') }}" class="btn btn-outline-elegant">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ordenação -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
                Mostrando {{ $livros->firstItem() }} a {{ $livros->lastItem() }} 
                de {{ $livros->total() }} resultados
            </p>
            
            <div class="dropdown">
                <button class="btn btn-outline-elegant dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-sort"></i> Ordenar por
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['orderby' => 'titulo', 'order' => 'asc']) }}">Nome A-Z</a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['orderby' => 'titulo', 'order' => 'desc']) }}">Nome Z-A</a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['orderby' => 'preco', 'order' => 'asc']) }}">Menor Preço</a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['orderby' => 'preco', 'order' => 'desc']) }}">Maior Preço</a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['orderby' => 'created_at', 'order' => 'desc']) }}">Mais Recentes</a></li>
                </ul>
            </div>
        </div>

        <!-- Grade de livros -->
        @if($livros->count() > 0)
            <div class="row">
                @foreach($livros as $livro)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card book-card h-100">
                            <div class="position-relative">
                                <div class="book-cover">
                                    <img src="{{ $livro->imagem_capa }}" 
                                         class="card-img-top" 
                                         alt="{{ $livro->titulo }}"
                                         style="height: 300px; object-fit: cover;">
                                </div>
                                
                                @if($livro->destaque)
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-warning text-dark">⭐ Destaque</span>
                                    </div>
                                @endif
                                
                                @if($livro->preco_promocional)
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-danger">🔥 Promoção</span>
                                    </div>
                                @endif
                                
                                <div class="position-absolute bottom-0 end-0 m-2">
                                    <span class="badge {{ $livro->status_estoque['classe'] }}">
                                        {{ $livro->status_estoque['texto'] }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge badge-category">{{ $livro->categoria->nome }}</span>
                                </div>
                                
                                <h5 class="card-title mb-2">{{ Str::limit($livro->titulo, 50) }}</h5>
                                <p class="card-text text-muted mb-2">
                                    <i class="fas fa-user"></i> {{ $livro->autor }}
                                </p>
                                <p class="card-text text-muted mb-3">
                                    <i class="fas fa-building"></i> {{ $livro->editora }}
                                </p>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            @if($livro->preco_promocional)
                                                <span class="price text-decoration-line-through text-muted">
                                                    R$ {{ number_format($livro->preco, 2, ',', '.') }}
                                                </span><br>
                                                <span class="price text-success">
                                                    R$ {{ number_format($livro->preco_promocional, 2, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="price">
                                                    R$ {{ number_format($livro->preco, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($livro->estoque > 0)
                                            <small class="text-success">
                                                <i class="fas fa-check-circle"></i> 
                                                {{ $livro->estoque }} disponível
                                            </small>
                                        @endif
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('livros.show', $livro->slug) }}" 
                                           class="btn btn-outline-elegant">
                                            <i class="fas fa-eye"></i> Ver Detalhes
                                        </a>
                                        
                                        @if($livro->estoque > 0)
                                            <form action="{{ route('carrinho.adicionar') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="livro_id" value="{{ $livro->id }}">
                                                <input type="hidden" name="quantidade" value="1">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-times"></i> Indisponível
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Paginação -->
            <div class="d-flex justify-content-center mt-4">
                {{ $livros->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-search fa-4x text-muted"></i>
                </div>
                <h3>Nenhum livro encontrado</h3>
                <p class="text-muted">Tente ajustar os filtros ou fazer uma nova busca.</p>
                <a href="{{ route('livros.index') }}" class="btn btn-primary">
                    <i class="fas fa-refresh"></i> Ver Todos os Livros
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit ao mudar categoria
    document.getElementById('categoria').addEventListener('change', function() {
        this.form.submit();
    });
    
    // Loading ao submeter formulário
    document.querySelector('form').addEventListener('submit', function() {
        const button = this.querySelector('button[type="submit"]');
        button.innerHTML = '<span class="loading-books"></span> Buscando...';
        button.disabled = true;
    });
</script>
@endpush