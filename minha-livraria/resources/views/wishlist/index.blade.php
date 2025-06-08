@extends('layouts.app')

@section('title', 'Minha Wishlist')

@section('content')
<div class="container my-5">
    <h2 class="mb-4">💖 Minha Wishlist</h2>

    @if($itens->isEmpty())
        <p class="text-muted">Você ainda não adicionou nenhum livro à sua wishlist.</p>
    @else
        <ul class="list-group">
            @foreach($itens as $item)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>{{ $item->livro->titulo }}</span>
                    <form action="{{ route('wishlist.remover', $item) }}" method="POST" class="ms-3">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Remover</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
