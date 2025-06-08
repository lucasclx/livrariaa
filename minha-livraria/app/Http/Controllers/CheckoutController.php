<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $carrinho = null;
        $itens = collect();
        
        if (session('cart_id')) {
            $carrinho = Carrinho::with(['itens.livro.categoria'])->find(session('cart_id'));
            $itens = $carrinho ? $carrinho->itens : collect();
        }
        
        if (!$carrinho || $itens->isEmpty()) {
            return redirect()->route('carrinho.index')
                ->with('error', 'Seu carrinho está vazio. Adicione alguns livros antes de finalizar a compra.');
        }
        
        return view('checkout.index', compact('carrinho', 'itens'));
    }
    
    public function processar(Request $request)
    {
        $request->validate([
            'nome_cliente' => 'required|string|max:255',
            'email_cliente' => 'required|email|max:255',
            'telefone_cliente' => 'required|string|max:20',
            'endereco_entrega' => 'required|string|max:500',
            'forma_pagamento' => 'required|in:cartao_credito,cartao_debito,pix,boleto',
        ]);
        
        $carrinho = null;
        if (session('cart_id')) {
            $carrinho = Carrinho::with(['itens.livro'])->find(session('cart_id'));
        }
        
        if (!$carrinho || $carrinho->itens->isEmpty()) {
            return redirect()->route('carrinho.index')
                ->with('error', 'Carrinho vazio ou não encontrado.');
        }
        
        // Verificar estoque
        foreach ($carrinho->itens as $item) {
            if ($item->livro->estoque < $item->quantidade) {
                return back()->with('error', "Produto '{$item->livro->titulo}' não possui estoque suficiente.");
            }
        }
        
        DB::beginTransaction();
        
        try {
            // Criar pedido
            $pedido = Pedido::create([
                'user_id' => auth()->id(),
                'nome_cliente' => $request->nome_cliente,
                'email_cliente' => $request->email_cliente,
                'telefone_cliente' => $request->telefone_cliente,
                'endereco_entrega' => $request->endereco_entrega,
                'total' => $carrinho->total,
                'forma_pagamento' => $request->forma_pagamento,
                'status' => 'pendente'
            ]);
            
            // Criar itens do pedido e atualizar estoque
            foreach ($carrinho->itens as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'livro_id' => $item->livro_id,
                    'quantidade' => $item->quantidade,
                    'preco_unitario' => $item->preco_unitario,
                    'subtotal' => $item->subtotal
                ]);
                
                // Reduzir estoque
                $item->livro->decrement('estoque', $item->quantidade);
            }
            
            // Limpar carrinho
            $carrinho->itens()->delete();
            $carrinho->delete();
            session()->forget('cart_id');
            
            DB::commit();
            
            // Enviar email de confirmação (implementar depois)
            // Mail::to($pedido->email_cliente)->send(new PedidoConfirmado($pedido));
            
            return redirect()->route('pedidos.sucesso', $pedido)
                ->with('success', 'Pedido realizado com sucesso!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Erro ao processar pedido. Tente novamente.');
        }
    }
}