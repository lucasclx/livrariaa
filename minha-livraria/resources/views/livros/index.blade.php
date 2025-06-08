@extends('layouts.app')

@section('title', 'Catálogo de Livros')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h1 class="page-title">Nosso Catálogo</h1>
    </div>
</div>

{{-- Filtros (Exemplo) --}}
<div class="card filter-card mb-4">
    <div class="card-body">
        <form action="{{ route('livros.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Buscar por título ou autor..." value="{{ request('search') }}">
            </div>
            <div class="col-md-5">
                 <select name="categoria_id" class="form-select">
                    <option value="">Todas as Categorias</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>


<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    @forelse($livros as $livro)
        <div class="col">
            <div class="card book-card h-100">
                 <div class="book-cover" style="background-image: url('{{ $livro->imagem_url ?? 'https://via.placeholder.com/300x420.png/F4F1E8/2C1810?text=Sem+Capa' }}')"></div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ Str::limit($livro->titulo, 45) }}</h5>
                    <p class="card-text text-muted small">{{ Str::limit($livro->autor, 50) }}</p>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="price mb-0">R$ {{ number_format($livro->preco, 2, ',', '.') }}</p>
                             <span class="badge badge-category">{{ $livro->categoria->nome ?? 'Sem Categoria' }}</span>
                        </div>
                        <a href="{{ route('livros.show', $livro) }}" class="btn btn-gold w-100">Ver Detalhes</a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center">
                Nenhum livro encontrado com os filtros atuais.
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $livros->appends(request()->query())->links() }}
</div>
@endsection