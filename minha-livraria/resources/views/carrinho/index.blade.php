@extends('layouts.app')

@section('content')
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            🛒 Meu Carrinho de Compras
        </h2>
        <a href="{{ route('livros.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Continuar Comprando
        </a>
    </div>

    {{-- AQUI ESTÁ A PRIMEIRA ALTERAÇÃO: $itens->count() para $carrinho->items->count() --}}
    @if($carrinho && $carrinho->items->count() > 0)
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        {{-- AQUI ESTÁ A SEGUNDA ALTERAÇÃO: A contagem de itens totais --}}
                        <h5 class="mb-0">Itens no Carrinho ({{ $carrinho->items->sum('quantidade') }})</h5>
                        
                        {{-- O seu código tinha uma rota 'carrinho.limpar', que não existe.
                             Comentei o formulário por enquanto. Se precisar, podemos criar essa rota e método.
                        <form action="#" method="POST"
                               onsubmit="return confirm('Tem certeza que deseja limpar todo o carrinho?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i> Limpar Carrinho
                            </button>
                        </form>
                        --}}
                    </div>
                    <div class="list-group list-group-flush">
                        {{-- AQUI ESTÁ A ALTERAÇÃO PRINCIPAL: @foreach ($itens as $item) para @foreach ($carrinho->items as $item) --}}
                        @foreach ($carrinho->items as $item)
                            <div class="list-group-item d-flex align-items-center">
                                <img src="{{ $item->livro->imagem_capa ?? 'https://via.placeholder.com/80' }}"
                                     alt="{{ $item->livro->titulo }}" class="img-thumbnail mr-3" style="width: 80px; height: 120px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $item->livro->titulo }}</h6>
                                    <p class="mb-1 text-muted">Autor: {{ $item->livro->autor }}</p>
                                    <p class="mb-1 font-weight-bold">R$ {{ number_format($item->livro->preco, 2, ',', '.') }}</p>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <form action="{{ route('carrinho.atualizar', $item->id) }}" method="POST" class="form-inline mb-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="quantidade" value="{{ $item->quantidade }}" min="1" max="{{ $item->livro->estoque }}" class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" class="btn btn-sm btn-primary ml-2"><i class="fas fa-sync-alt"></i></button>
                                    </form>
                                    <form action="{{ route('carrinho.remover', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remover</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Resumo do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Subtotal</span>
                                {{-- AQUI A ÚLTIMA ALTERAÇÃO: cálculo do total --}}
                                <span>R$ {{ number_format($carrinho->items->sum(fn($item) => $item->quantidade * $item->livro->preco), 2, ',', '.') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Frete</span>
                                <span>Grátis</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between font-weight-bold h5">
                                <span>Total</span>
                                <span>R$ {{ number_format($carrinho->items->sum(fn($item) => $item->quantidade * $item->livro->preco), 2, ',', '.') }}</span>
                            </li>
                        </ul>
                        <a href="{{ route('checkout.index') }}" class="btn btn-success btn-block mt-4">
                            <i class="fas fa-check"></i> Finalizar Compra
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h4>Seu carrinho está vazio.</h4>
            <p class="text-muted">Adicione livros ao seu carrinho para vê-los aqui.</p>
        </div>
    @endif
</div>
@endsection