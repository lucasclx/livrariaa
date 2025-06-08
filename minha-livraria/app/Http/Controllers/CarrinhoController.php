<?php
// app/Http/Controllers/CarrinhoController.php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\CarrinhoItem;
use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CarrinhoController extends Controller
{
    private function getOrCreateCarrinho()
    {
        $sessionId = Session::getId();
        $userId = auth()->id();

        $carrinho = Carrinho::where('session_id', $sessionId)->first();

        if (!$carrinho) {
            $carrinho = Carrinho::create([
                'session_id' => $sessionId,
                'user_id' => $userId
            ]);
        }

        session(['cart_id' => $carrinho->id]);

        return $carrinho;
    }

    public function index()
    {
        $carrinho = null;
        $itens = collect();

        if (session('cart_id')) {
            $carrinho = Carrinho::with(['itens.livro.categoria'])->find(session('cart_id'));
            $itens = $carrinho ? $carrinho->itens : collect();
        }

        return view('carrinho.index', compact('carrinho', 'itens'));
    }

    public function adicionar(Request $request)
    {
        $request->validate([
            'livro_id' => 'required|exists:livros,id',
            'quantidade' => 'required|integer|min:1'
        ]);

        $livro = Livro::findOrFail($request->livro_id);

        if ($livro->estoque < $request->quantidade) {
            return back()->with('error', 'Quantidade solicitada não disponível em estoque.');
        }

        $carrinho = $this->getOrCreateCarrinho();

        $itemExistente = CarrinhoItem::where('carrinho_id', $carrinho->id)
            ->where('livro_id', $livro->id)
            ->first();

        if ($itemExistente) {
            $novaQuantidade = $itemExistente->quantidade + $request->quantidade;
            
            if ($livro->estoque < $novaQuantidade) {
                return back()->with('error', 'Quantidade total solicitada excede o estoque disponível.');
            }

            $itemExistente->update(['quantidade' => $novaQuantidade]);
        } else {
            CarrinhoItem::create([
                'carrinho_id' => $carrinho->id,
                'livro_id' => $livro->id,
                'quantidade' => $request->quantidade,
                'preco_unitario' => $livro->preco_final
            ]);
        }

        return back()->with('success', 'Livro adicionado ao carrinho com sucesso!');
    }

    public function atualizar(Request $request, CarrinhoItem $item)
    {
        $request->validate([
            'quantidade' => 'required|integer|min:1'
        ]);

        if ($item->livro->estoque < $request->quantidade) {
            return back()->with('error', 'Quantidade solicitada não disponível em estoque.');
        }

        $item->update(['quantidade' => $request->quantidade]);

        return back()->with('success', 'Quantidade atualizada com sucesso!');
    }

    public function remover(CarrinhoItem $item)
    {
        $item->delete();

        return back()->with('success', 'Item removido do carrinho.');
    }

    public function limpar()
    {
        if (session('cart_id')) {
            $carrinho = Carrinho::find(session('cart_id'));
            if ($carrinho) {
                $carrinho->itens()->delete();
            }
        }

        return back()->with('success', 'Carrinho limpo com sucesso!');
    }
}