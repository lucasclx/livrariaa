<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use App\Models\Carrinho;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Importar a facade Auth

class CarrinhoController extends Controller
{
    /**
     * Método privado para obter ou criar o carrinho do utilizador.
     * Agora, lida com utilizadores autenticados (via user_id) e
     * visitantes (via session_id).
     *
     * @return Carrinho
     */
    private function getOrCreateCarrinho(): Carrinho
    {
        if (Auth::check()) {
            // Cenário 1: Utilizador está autenticado.
            // O carrinho é identificado pelo user_id.
            $carrinho = Carrinho::firstOrCreate(['user_id' => Auth::id()]);
        } else {
            // Cenário 2: Utilizador é um visitante.
            // O carrinho é identificado pelo ID da sessão atual.
            $carrinho = Carrinho::firstOrCreate(['session_id' => session()->getId()]);
        }

        return $carrinho;
    }

    /**
     * Exibe os itens no carrinho de compras.
     */
    public function index()
    {
        // Usamos o eager loading para carregar os livros e evitar o problema de N+1 queries.
        $carrinho = $this->getOrCreateCarrinho()->load('items.livro');

        return view('carrinho.index', compact('carrinho'));
    }

    /**
     * Adiciona um livro ao carrinho.
     */
    public function adicionar(Request $request, Livro $livro)
    {
        $carrinho = $this->getOrCreateCarrinho();

        // Verifica se o item já existe no carrinho
        $item = $carrinho->items()->where('livro_id', $livro->id)->first();

        if ($item) {
            // Se existe, incrementa a quantidade
            $item->increment('quantidade', $request->input('quantidade', 1));
        } else {
            // Se não existe, cria um novo item
            $carrinho->items()->create([
                'livro_id' => $livro->id,
                'quantidade' => $request->input('quantidade', 1),
                'preco_unitario' => $livro->preco, // Grava o preço no item do carrinho
            ]);
        }

        return redirect()->route('carrinho.index')->with('success', 'Livro adicionado ao carrinho!');
    }

    /**
     * Remove um item do carrinho.
     */
    public function remover($itemId)
    {
        $carrinho = $this->getOrCreateCarrinho();
        $item = $carrinho->items()->findOrFail($itemId);
        $item->delete();

        return redirect()->route('carrinho.index')->with('success', 'Item removido do carrinho.');
    }

    /**
     * Atualiza a quantidade de um item no carrinho.
     */
    public function atualizar(Request $request, $itemId)
    {
        $carrinho = $this->getOrCreateCarrinho();
        $item = $carrinho->items()->findOrFail($itemId);
        
        $quantidade = $request->input('quantidade');

        if ($quantidade > 0) {
            $item->update(['quantidade' => $quantidade]);
        } else {
            // Se a quantidade for 0 ou menos, remove o item
            $item->delete();
        }

        return redirect()->route('carrinho.index')->with('success', 'Carrinho atualizado.');
    }
}