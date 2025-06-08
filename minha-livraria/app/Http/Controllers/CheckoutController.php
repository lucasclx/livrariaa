<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Mail\PedidoConfirmado;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CheckoutRequets; 
use App\Actions\Pedidos\ProcessarPedidoAction; // Importa a nova Ação

class CheckoutController extends Controller
{
    /**
     * Exibe a página de checkout com os itens do carrinho.
     */
    public function index()
    {
        $carrinho = Carrinho::where('user_id', auth()->id())->with('items.livro')->first();

        if (!$carrinho || $carrinho->items->isEmpty()) {
            return redirect()->route('livros.index')->with('info', 'O seu carrinho está vazio.');
        }

        return view('checkout.index', compact('carrinho'));
    }

    /**
     * Processa a finalização da compra.
     * A lógica foi movida para a classe ProcessarPedidoAction.
     */
    public function processar(CheckoutReques $request, ProcessarPedidoAction $action)
    {
        try {
            // Delega toda a lógica complexa para a Ação.
            $pedido = $action->execute(auth()->user(), $request->validated());

            // Enviar e-mail de confirmação (pode ser movido para uma Job no futuro)
            Mail::to(auth()->user()->email)->send(new PedidoConfirmado($pedido));

            // Redireciona para uma página de sucesso com os detalhes do pedido.
            return redirect()->route('pedidos.show', $pedido->id)
                             ->with('success', 'Pedido realizado com sucesso! Um e-mail de confirmação foi enviado.');

        } catch (\Exception $e) {
            // Se a Ação lançar qualquer exceção (ex: falta de stock), captura aqui.
            return redirect()->route('carrinho.index')
                             ->withErrors(['critical' => $e->getMessage()]);
        }
    }
}