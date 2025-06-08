<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $pedidos = Pedido::where('user_id', auth()->id())
            ->with(['itens.livro'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('pedidos.index', compact('pedidos'));
    }
    
    public function show(Pedido $pedido)
    {
        // Verificar se o pedido pertence ao usuário logado
        if ($pedido->user_id !== auth()->id()) {
            abort(403, 'Acesso negado.');
        }
        
        $pedido->load(['itens.livro.categoria']);
        
        return view('pedidos.show', compact('pedido'));
    }
    
    public function sucesso(Pedido $pedido)
    {
        // Verificar se o pedido pertence ao usuário logado ou se foi criado recentemente
        if ($pedido->user_id !== auth()->id() && 
            $pedido->created_at->diffInMinutes(now()) > 30) {
            abort(403, 'Acesso negado.');
        }
        
        $pedido->load(['itens.livro.categoria']);
        
        return view('pedidos.sucesso', compact('pedido'));
    }
}