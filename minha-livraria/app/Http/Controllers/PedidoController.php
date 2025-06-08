<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PedidoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Aplicar middleware de autenticação para todas as rotas
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Exibir lista de pedidos do usuário
     */
    public function index(Request $request)
    {
        $query = Pedido::where('user_id', Auth::id())
            ->with(['items.livro.categoria']);
        
        // Filtros opcionais
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        
        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function($q) use ($busca) {
                $q->where('numero_pedido', 'like', "%{$busca}%")
                  ->orWhereHas('items.livro', function($subQ) use ($busca) {
                      $subQ->where('titulo', 'like', "%{$busca}%")
                           ->orWhere('autor', 'like', "%{$busca}%");
                  });
            });
        }
        
        $pedidos = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Estatísticas para o dashboard
        $estatisticas = [
            'total_pedidos' => Pedido::where('user_id', Auth::id())->count(),
            'total_gasto' => Pedido::where('user_id', Auth::id())
                ->whereIn('status', ['processando', 'enviado', 'entregue'])
                ->sum('total'),
            'pedidos_pendentes' => Pedido::where('user_id', Auth::id())
                ->where('status', 'pendente')
                ->count(),
            'ultimo_pedido' => Pedido::where('user_id', Auth::id())
                ->latest()
                ->first()?->created_at,
        ];
        
        return view('pedidos.index', compact('pedidos', 'estatisticas'));
    }
    
    /**
     * Exibir detalhes de um pedido específico
     */
    public function show(Pedido $pedido)
    {
        // Verificar autorização usando Policy
        $this->authorize('view', $pedido);
        
        // Carregar relacionamentos necessários
        $pedido->load([
            'items.livro.categoria',
            'user',
        ]);
        
        // Calcular informações adicionais
        $resumo = [
            'subtotal' => $pedido->items->sum(function($item) {
                return $item->quantidade * $item->preco_unitario;
            }),
            'total_itens' => $pedido->items->sum('quantidade'),
            'desconto' => 0, // Implementar lógica de desconto se necessário
            'frete' => 0, // Implementar lógica de frete se necessário
        ];
        
        // Timeline do pedido para tracking
        $timeline = $this->gerarTimelinePedido($pedido);
        
        return view('pedidos.show', compact('pedido', 'resumo', 'timeline'));
    }
    
    /**
     * Página de sucesso após criação do pedido
     */
    public function sucesso(Pedido $pedido)
    {
        // Verificar se o pedido pertence ao usuário logado
        // Permite acesso por 30 minutos após criação para casos de logout/login
        if ($pedido->user_id !== Auth::id()) {
            // Se não é o dono, só permite acesso se foi criado recentemente
            if ($pedido->created_at->diffInMinutes(now()) > 30) {
                abort(403, 'Acesso negado a este pedido.');
            }
        }
        
        $pedido->load([
            'items.livro.categoria',
            'user'
        ]);
        
        // Calcular informações para exibição
        $resumo = [
            'subtotal' => $pedido->items->sum(function($item) {
                return $item->quantidade * $item->preco_unitario;
            }),
            'total_itens' => $pedido->items->sum('quantidade'),
            'estimativa_entrega' => $this->calcularEstimativaEntrega($pedido),
        ];
        
        // Sugestões de produtos relacionados
        $sugestoes = $this->obterSugestoesProdutos($pedido);
        
        return view('pedidos.sucesso', compact('pedido', 'resumo', 'sugestoes'));
    }
    
    /**
     * Cancelar um pedido (se permitido)
     */
    public function cancelar(Pedido $pedido)
    {
        $this->authorize('cancel', $pedido);
        
        // Verificar se o pedido pode ser cancelado
        if (!in_array($pedido->status, ['pendente', 'processando'])) {
            return redirect()->back()->withErrors([
                'erro' => 'Este pedido não pode mais ser cancelado.'
            ]);
        }
        
        // Verificar tempo limite para cancelamento (ex: 2 horas)
        if ($pedido->created_at->diffInHours(now()) > 2) {
            return redirect()->back()->withErrors([
                'erro' => 'O prazo para cancelamento deste pedido expirou.'
            ]);
        }
        
        try {
            // Atualizar status do pedido
            $pedido->update([
                'status' => 'cancelado',
                'observacoes' => 'Cancelado pelo cliente em ' . now()->format('d/m/Y H:i')
            ]);
            
            // Devolver items ao estoque
            foreach ($pedido->items as $item) {
                $item->livro->increment('estoque', $item->quantidade);
            }
            
            // Log da ação
            \Log::info('Pedido cancelado pelo cliente', [
                'pedido_id' => $pedido->id,
                'user_id' => Auth::id(),
                'total' => $pedido->total
            ]);
            
            return redirect()->route('pedidos.show', $pedido)
                ->with('success', 'Pedido cancelado com sucesso. O estoque foi atualizado.');
                
        } catch (\Exception $e) {
            \Log::error('Erro ao cancelar pedido', [
                'pedido_id' => $pedido->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->withErrors([
                'erro' => 'Erro interno. Tente novamente ou entre em contato conosco.'
            ]);
        }
    }
    
    /**
     * Rastrear pedido (página pública com número do pedido)
     */
    public function rastrear(Request $request)
    {
        $request->validate([
            'numero_pedido' => 'required|string',
            'email' => 'required|email'
        ]);
        
        $pedido = Pedido::where('numero_pedido', $request->numero_pedido)
            ->where('email_cliente', $request->email)
            ->with(['items.livro', 'user'])
            ->first();
        
        if (!$pedido) {
            return redirect()->back()->withErrors([
                'erro' => 'Pedido não encontrado. Verifique o número do pedido e e-mail.'
            ]);
        }
        
        $timeline = $this->gerarTimelinePedido($pedido);
        
        return view('pedidos.rastreamento', compact('pedido', 'timeline'));
    }
    
    /**
     * Gerar timeline do pedido para tracking
     */
    private function gerarTimelinePedido(Pedido $pedido): array
    {
        $timeline = [
            [
                'status' => 'pedido_criado',
                'titulo' => 'Pedido Criado',
                'descricao' => 'Seu pedido foi criado e está sendo processado',
                'data' => $pedido->created_at,
                'concluido' => true,
                'icone' => 'fas fa-shopping-cart'
            ]
        ];
        
        // Adicionar eventos baseados no status atual
        switch ($pedido->status) {
            case 'pendente':
                $timeline[] = [
                    'status' => 'aguardando_pagamento',
                    'titulo' => 'Aguardando Pagamento',
                    'descricao' => 'Aguardando confirmação do pagamento',
                    'data' => null,
                    'concluido' => false,
                    'icone' => 'fas fa-credit-card'
                ];
                break;
                
            case 'processando':
                $timeline[] = [
                    'status' => 'pagamento_confirmado',
                    'titulo' => 'Pagamento Confirmado',
                    'descricao' => 'Pagamento confirmado, preparando para envio',
                    'data' => $pedido->updated_at,
                    'concluido' => true,
                    'icone' => 'fas fa-check-circle'
                ];
                $timeline[] = [
                    'status' => 'preparando_envio',
                    'titulo' => 'Preparando Envio',
                    'descricao' => 'Separando produtos para envio',
                    'data' => null,
                    'concluido' => false,
                    'icone' => 'fas fa-box'
                ];
                break;
                
            case 'enviado':
                $timeline[] = [
                    'status' => 'pagamento_confirmado',
                    'titulo' => 'Pagamento Confirmado',
                    'descricao' => 'Pagamento confirmado',
                    'data' => $pedido->updated_at,
                    'concluido' => true,
                    'icone' => 'fas fa-check-circle'
                ];
                $timeline[] = [
                    'status' => 'enviado',
                    'titulo' => 'Produto Enviado',
                    'descricao' => 'Produto enviado para entrega',
                    'data' => $pedido->updated_at,
                    'concluido' => true,
                    'icone' => 'fas fa-truck'
                ];
                $timeline[] = [
                    'status' => 'em_transito',
                    'titulo' => 'Em Trânsito',
                    'descricao' => 'Produto a caminho do destino',
                    'data' => null,
                    'concluido' => false,
                    'icone' => 'fas fa-route'
                ];
                break;
                
            case 'entregue':
                $timeline[] = [
                    'status' => 'pagamento_confirmado',
                    'titulo' => 'Pagamento Confirmado',
                    'descricao' => 'Pagamento confirmado',
                    'data' => $pedido->created_at->addHours(2),
                    'concluido' => true,
                    'icone' => 'fas fa-check-circle'
                ];
                $timeline[] = [
                    'status' => 'enviado',
                    'titulo' => 'Produto Enviado',
                    'descricao' => 'Produto enviado para entrega',
                    'data' => $pedido->created_at->addDays(1),
                    'concluido' => true,
                    'icone' => 'fas fa-truck'
                ];
                $timeline[] = [
                    'status' => 'entregue',
                    'titulo' => 'Produto Entregue',
                    'descricao' => 'Produto entregue com sucesso',
                    'data' => $pedido->data_entrega ?? $pedido->updated_at,
                    'concluido' => true,
                    'icone' => 'fas fa-home'
                ];
                break;
                
            case 'cancelado':
                $timeline[] = [
                    'status' => 'cancelado',
                    'titulo' => 'Pedido Cancelado',
                    'descricao' => 'Pedido foi cancelado',
                    'data' => $pedido->updated_at,
                    'concluido' => true,
                    'icone' => 'fas fa-times-circle',
                    'classe' => 'text-danger'
                ];
                break;
        }
        
        return $timeline;
    }
    
    /**
     * Calcular estimativa de entrega
     */
    private function calcularEstimativaEntrega(Pedido $pedido): \Carbon\Carbon
    {
        $diasUteis = 5; // Padrão de 5 dias úteis
        
        // Ajustar baseado na forma de pagamento
        if ($pedido->forma_pagamento === 'boleto') {
            $diasUteis += 2; // Boleto demora mais para compensar
        }
        
        // Ajustar baseado na localização (implementar lógica de CEP)
        // Por enquanto, usar um padrão simples
        
        return now()->addWeekdays($diasUteis);
    }
    
    /**
     * Obter sugestões de produtos relacionados
     */
    private function obterSugestoesProdutos(Pedido $pedido): \Illuminate\Database\Eloquent\Collection
    {
        // Obter categorias dos livros comprados
        $categoriasCompradas = $pedido->items->pluck('livro.categoria_id')->unique();
        
        // Buscar livros similares
        return \App\Models\Livro::whereIn('categoria_id', $categoriasCompradas)
            ->whereNotIn('id', $pedido->items->pluck('livro_id'))
            ->where('ativo', true)
            ->where('estoque', '>', 0)
            ->inRandomOrder()
            ->limit(4)
            ->get();
    }
    
    /**
     * Avaliar pedido - redireciona para página de avaliação
     */
    public function avaliar(Pedido $pedido)
    {
        $this->authorize('view', $pedido);
        
        // Verificar se o pedido foi entregue
        if ($pedido->status !== 'entregue') {
            return redirect()->back()->withErrors([
                'erro' => 'Você só pode avaliar pedidos que foram entregues.'
            ]);
        }
        
        // Verificar se já passou tempo suficiente desde a entrega
        if ($pedido->data_entrega && $pedido->data_entrega->diffInDays(now()) < 1) {
            return redirect()->back()->withErrors([
                'erro' => 'Aguarde pelo menos 1 dia após a entrega para avaliar.'
            ]);
        }
        
        $pedido->load(['items.livro.categoria']);
        
        // Verificar quais livros já foram avaliados
        $livrosAvaliados = \App\Models\Avaliacao::where('user_id', auth()->id())
            ->whereIn('livro_id', $pedido->items->pluck('livro_id'))
            ->pluck('livro_id')
            ->toArray();
            
        return view('pedidos.avaliar', compact('pedido', 'livrosAvaliados'));
    }
    
    /**
     * Solicitar reembolso
     */
    public function solicitarReembolso(Request $request, Pedido $pedido)
    {
        $this->authorize('view', $pedido);
        
        $request->validate([
            'motivo' => 'required|string|in:produto_defeituoso,produto_errado,nao_chegou,nao_gostei,outro',
            'descricao' => 'required|string|min:10|max:1000',
            'fotos.*' => 'nullable|image|max:2048'
        ]);
        
        // Verificar se o pedido permite reembolso
        if (!in_array($pedido->status, ['entregue', 'enviado'])) {
            return redirect()->back()->withErrors([
                'erro' => 'Reembolso não disponível para este status de pedido.'
            ]);
        }
        
        // Verificar prazo para reembolso (ex: 30 dias)
        $prazoReembolso = 30;
        if ($pedido->data_entrega && $pedido->data_entrega->diffInDays(now()) > $prazoReembolso) {
            return redirect()->back()->withErrors([
                'erro' => "O prazo para reembolso ({$prazoReembolso} dias) expirou."
            ]);
        }
        
        try {
            // Criar solicitação de reembolso
            $solicitacao = \App\Models\SolicitacaoReembolso::create([
                'pedido_id' => $pedido->id,
                'user_id' => auth()->id(),
                'motivo' => $request->motivo,
                'descricao' => $request->descricao,
                'valor_solicitado' => $pedido->total,
                'status' => 'pendente'
            ]);
            
            // Processar upload de fotos se houver
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('reembolsos/' . $solicitacao->id, 'public');
                    $solicitacao->fotos()->create(['caminho' => $path]);
                }
            }
            
            // Enviar notificação para admin
            // \Notification::route('mail', config('mail.admin_email'))
            //     ->notify(new \App\Notifications\NovasolicitacaoReembolso($solicitacao));
            
            return redirect()->route('pedidos.show', $pedido)
                ->with('success', 'Solicitação de reembolso enviada com sucesso. Entraremos em contato em até 2 dias úteis.');
                
        } catch (\Exception $e) {
            \Log::error('Erro ao solicitar reembolso', [
                'pedido_id' => $pedido->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->withErrors([
                'erro' => 'Erro interno. Tente novamente ou entre em contato conosco.'
            ]);
        }
    }
    
    /**
     * Repetir pedido - adicionar todos os itens ao carrinho
     */
    public function repetir(Pedido $pedido)
    {
        $this->authorize('view', $pedido);
        
        try {
            $carrinho = \App\Models\Carrinho::firstOrCreate(['user_id' => auth()->id()]);
            
            $itensAdicionados = 0;
            $itensIndisponiveis = [];
            
            foreach ($pedido->items as $item) {
                // Verificar se o livro ainda está disponível
                if (!$item->livro->ativo || $item->livro->estoque < $item->quantidade) {
                    $itensIndisponiveis[] = $item->livro->titulo;
                    continue;
                }
                
                // Verificar se já existe no carrinho
                $itemCarrinho = $carrinho->items()
                    ->where('livro_id', $item->livro_id)
                    ->first();
                
                if ($itemCarrinho) {
                    $itemCarrinho->increment('quantidade', $item->quantidade);
                } else {
                    $carrinho->items()->create([
                        'livro_id' => $item->livro_id,
                        'quantidade' => $item->quantidade,
                        'preco_unitario' => $item->livro->preco_final
                    ]);
                }
                
                $itensAdicionados++;
            }
            
            $mensagem = "Pedido repetido com sucesso! {$itensAdicionados} itens adicionados ao carrinho.";
            
            if (!empty($itensIndisponiveis)) {
                $mensagem .= " Alguns itens não estão mais disponíveis: " . implode(', ', $itensIndisponiveis);
            }
            
            return redirect()->route('carrinho.index')->with('success', $mensagem);
            
        } catch (\Exception $e) {
            \Log::error('Erro ao repetir pedido', [
                'pedido_id' => $pedido->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->withErrors([
                'erro' => 'Erro ao repetir pedido. Tente novamente.'
            ]);
        }
    }
    
    /**
     * Baixar nota fiscal em PDF
     */
    public function notaFiscal(Pedido $pedido)
    {
        $this->authorize('view', $pedido);
        
        // Verificar se o pedido tem nota fiscal disponível
        if (!in_array($pedido->status, ['processando', 'enviado', 'entregue'])) {
            return redirect()->back()->withErrors([
                'erro' => 'Nota fiscal não disponível para este pedido.'
            ]);
        }
        
        try {
            $pedido->load(['items.livro.categoria', 'user']);
            
            // Gerar PDF da nota fiscal
            $pdf = \PDF::loadView('pedidos.nota-fiscal', compact('pedido'));
            
            $nomeArquivo = "nota-fiscal-{$pedido->numero_pedido}.pdf";
            
            return $pdf->download($nomeArquivo);
            
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar nota fiscal', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->withErrors([
                'erro' => 'Erro ao gerar nota fiscal. Tente novamente.'
            ]);
        }
    }
}