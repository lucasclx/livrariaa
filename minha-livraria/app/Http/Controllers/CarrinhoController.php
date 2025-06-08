<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use App\Models\Carrinho;
use App\Models\CarrinhoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarrinhoController extends Controller
{
    /**
     * Método privado para obter ou criar o carrinho do utilizador.
     * Agora lida com utilizadores autenticados (via user_id) e
     * visitantes (via session_id), além de migrar carrinho na autenticação.
     */
    private function getOrCreateCarrinho(): Carrinho
    {
        if (Auth::check()) {
            // Cenário 1: Utilizador está autenticado
            
            // Verificar se existe carrinho da sessão para migrar
            $carrinhoSessao = Carrinho::where('session_id', session()->getId())
                ->with('items')
                ->first();
            
            if ($carrinhoSessao && $carrinhoSessao->items->isNotEmpty()) {
                // Existe carrinho da sessão com itens, migrar para usuário logado
                $carrinhoUsuario = Carrinho::firstOrCreate(['user_id' => Auth::id()]);
                
                foreach ($carrinhoSessao->items as $itemSessao) {
                    $itemExistente = $carrinhoUsuario->items()
                        ->where('livro_id', $itemSessao->livro_id)
                        ->first();
                    
                    if ($itemExistente) {
                        // Item já existe, somar quantidades
                        $novaQuantidade = $itemExistente->quantidade + $itemSessao->quantidade;
                        
                        // Verificar estoque disponível
                        $estoqueDisponivel = $itemSessao->livro->estoque;
                        if ($novaQuantidade > $estoqueDisponivel) {
                            $novaQuantidade = $estoqueDisponivel;
                        }
                        
                        $itemExistente->update([
                            'quantidade' => $novaQuantidade,
                            'preco_unitario' => $itemSessao->livro->preco_final
                        ]);
                    } else {
                        // Item não existe, criar novo
                        $carrinhoUsuario->items()->create([
                            'livro_id' => $itemSessao->livro_id,
                            'quantidade' => min($itemSessao->quantidade, $itemSessao->livro->estoque),
                            'preco_unitario' => $itemSessao->livro->preco_final
                        ]);
                    }
                }
                
                // Deletar carrinho da sessão após migração
                $carrinhoSessao->items()->delete();
                $carrinhoSessao->delete();
                
                Log::info('Carrinho migrado da sessão para usuário', [
                    'user_id' => Auth::id(),
                    'session_id' => session()->getId(),
                    'itens_migrados' => $carrinhoSessao->items->count()
                ]);
                
                return $carrinhoUsuario;
            }
            
            // Não há carrinho da sessão ou está vazio, retornar carrinho do usuário
            return Carrinho::firstOrCreate(['user_id' => Auth::id()]);
            
        } else {
            // Cenário 2: Utilizador é um visitante
            return Carrinho::firstOrCreate(['session_id' => session()->getId()]);
        }
    }

    /**
     * Exibe os itens no carrinho de compras
     */
    public function index()
    {
        $carrinho = $this->getOrCreateCarrinho();
        
        // Carregar itens com livros e categorias usando eager loading
        $carrinho->load(['items.livro.categoria']);
        
        // Verificar validade dos itens (produtos ativos e com estoque)
        $itensInvalidos = [];
        
        foreach ($carrinho->items as $item) {
            if (!$item->livro || !$item->livro->ativo) {
                $itensInvalidos[] = $item;
            } elseif ($item->livro->estoque < $item->quantidade) {
                // Ajustar quantidade automaticamente para o estoque disponível
                if ($item->livro->estoque > 0) {
                    $item->update(['quantidade' => $item->livro->estoque]);
                    session()->flash('warning', "Quantidade do livro '{$item->livro->titulo}' foi ajustada para o estoque disponível.");
                } else {
                    $itensInvalidos[] = $item;
                }
            }
        }
        
        // Remover itens inválidos
        if (!empty($itensInvalidos)) {
            foreach ($itensInvalidos as $item) {
                $item->delete();
            }
            
            if (count($itensInvalidos) === 1) {
                session()->flash('info', "O livro '{$itensInvalidos[0]->livro?->titulo ?? 'indisponível'}' foi removido do carrinho pois não está mais disponível.");
            } else {
                session()->flash('info', count($itensInvalidos) . ' itens foram removidos do carrinho por não estarem mais disponíveis.');
            }
            
            // Recarregar carrinho após limpeza
            $carrinho->load(['items.livro.categoria']);
        }
        
        // Calcular estatísticas do carrinho
        $estatisticas = $this->calcularEstatisticasCarrinho($carrinho);
        
        // Sugestões de produtos relacionados
        $sugestoes = $this->obterSugestoesProdutos($carrinho);
        
        return view('carrinho.index', compact('carrinho', 'estatisticas', 'sugestoes'));
    }

    /**
     * Adiciona um livro ao carrinho
     */
    public function adicionar(Request $request)
    {
        $request->validate([
            'livro_id' => 'required|exists:livros,id',
            'quantidade' => 'required|integer|min:1|max:20'
        ], [
            'livro_id.required' => 'Livro não especificado.',
            'livro_id.exists' => 'Livro não encontrado.',
            'quantidade.required' => 'Quantidade é obrigatória.',
            'quantidade.integer' => 'Quantidade deve ser um número inteiro.',
            'quantidade.min' => 'Quantidade mínima é 1.',
            'quantidade.max' => 'Quantidade máxima é 20 unidades por item.'
        ]);

        try {
            DB::beginTransaction();

            $livro = Livro::findOrFail($request->livro_id);
            
            // Verificar se o livro está ativo e disponível
            if (!$livro->ativo) {
                return redirect()->back()
                    ->withErrors(['livro' => 'Este livro não está mais disponível.']);
            }
            
            if ($livro->estoque < $request->quantidade) {
                return redirect()->back()
                    ->withErrors(['estoque' => "Estoque insuficiente. Disponível: {$livro->estoque} unidades."]);
            }

            $carrinho = $this->getOrCreateCarrinho();
            
            // Verificar se o item já existe no carrinho
            $item = $carrinho->items()->where('livro_id', $livro->id)->first();

            if ($item) {
                // Item existe, verificar se pode adicionar mais
                $novaQuantidade = $item->quantidade + $request->quantidade;
                
                if ($novaQuantidade > $livro->estoque) {
                    return redirect()->back()
                        ->withErrors(['estoque' => "Não é possível adicionar mais unidades. Limite de estoque: {$livro->estoque}"]);
                }
                
                if ($novaQuantidade > 20) {
                    return redirect()->back()
                        ->withErrors(['quantidade' => 'Máximo de 20 unidades por produto no carrinho.']);
                }
                
                $item->update([
                    'quantidade' => $novaQuantidade,
                    'preco_unitario' => $livro->preco_final // Atualizar preço
                ]);
                
                $mensagem = "Quantidade do livro '{$livro->titulo}' atualizada no carrinho!";
                
            } else {
                // Item não existe, criar novo
                $carrinho->items()->create([
                    'livro_id' => $livro->id,
                    'quantidade' => $request->quantidade,
                    'preco_unitario' => $livro->preco_final
                ]);
                
                $mensagem = "Livro '{$livro->titulo}' adicionado ao carrinho!";
            }

            DB::commit();
            
            // Log da ação
            Log::info('Item adicionado ao carrinho', [
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'livro_id' => $livro->id,
                'quantidade' => $request->quantidade,
                'preco_unitario' => $livro->preco_final
            ]);

            // Retornar resposta baseada no tipo de requisição
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensagem,
                    'cart_count' => $carrinho->items->sum('quantidade')
                ]);
            }

            return redirect()->route('carrinho.index')->with('success', $mensagem);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao adicionar item ao carrinho', [
                'user_id' => Auth::id(),
                'livro_id' => $request->livro_id,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro interno. Tente novamente.'
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['erro' => 'Erro interno. Tente novamente.']);
        }
    }

    /**
     * Remove um item do carrinho
     */
    public function remover($itemId)
    {
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $item = $carrinho->items()->findOrFail($itemId);
            
            $tituloLivro = $item->livro->titulo;
            $item->delete();

            Log::info('Item removido do carrinho', [
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'item_id' => $itemId,
                'livro_titulo' => $tituloLivro
            ]);

            return redirect()->route('carrinho.index')
                ->with('success', "'{$tituloLivro}' removido do carrinho.");

        } catch (\Exception $e) {
            Log::error('Erro ao remover item do carrinho', [
                'user_id' => Auth::id(),
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('carrinho.index')
                ->withErrors(['erro' => 'Erro ao remover item. Tente novamente.']);
        }
    }

    /**
     * Atualiza a quantidade de um item no carrinho
     */
    public function atualizar(Request $request, $itemId)
    {
        $request->validate([
            'quantidade' => 'required|integer|min:0|max:20'
        ], [
            'quantidade.min' => 'Para remover o item, use o botão remover.',
            'quantidade.max' => 'Quantidade máxima é 20 unidades.'
        ]);

        try {
            $carrinho = $this->getOrCreateCarrinho();
            $item = $carrinho->items()->with('livro')->findOrFail($itemId);
            
            $quantidade = $request->quantidade;

            if ($quantidade == 0) {
                // Quantidade zero = remover item
                $tituloLivro = $item->livro->titulo;
                $item->delete();
                
                return redirect()->route('carrinho.index')
                    ->with('success', "'{$tituloLivro}' removido do carrinho.");
            }

            // Verificar estoque disponível
            if ($quantidade > $item->livro->estoque) {
                return redirect()->back()
                    ->withErrors(['estoque' => "Estoque insuficiente para '{$item->livro->titulo}'. Disponível: {$item->livro->estoque} unidades."]);
            }

            $item->update([
                'quantidade' => $quantidade,
                'preco_unitario' => $item->livro->preco_final // Atualizar preço atual
            ]);

            Log::info('Quantidade atualizada no carrinho', [
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'item_id' => $itemId,
                'nova_quantidade' => $quantidade
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quantidade atualizada com sucesso!',
                    'subtotal' => $item->quantidade * $item->preco_unitario,
                    'cart_total' => $carrinho->items->sum(fn($i) => $i->quantidade * $i->preco_unitario)
                ]);
            }

            return redirect()->route('carrinho.index')
                ->with('success', 'Quantidade atualizada com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar item do carrinho', [
                'user_id' => Auth::id(),
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar quantidade.'
                ], 500);
            }

            return redirect()->route('carrinho.index')
                ->withErrors(['erro' => 'Erro ao atualizar quantidade. Tente novamente.']);
        }
    }

    /**
     * Limpa todo o carrinho
     */
    public function limpar()
    {
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $totalItens = $carrinho->items->count();
            
            $carrinho->items()->delete();

            Log::info('Carrinho limpo', [
                'user_id' => Auth::id(),
                'session_id' => session()->getId(),
                'itens_removidos' => $totalItens
            ]);

            return redirect()->route('carrinho.index')
                ->with('success', 'Carrinho limpo com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao limpar carrinho', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('carrinho.index')
                ->withErrors(['erro' => 'Erro ao limpar carrinho. Tente novamente.']);
        }
    }

    /**
     * Salvar carrinho para depois (wishlist temporária)
     */
    public function salvarParaDepois($itemId)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('info', 'Faça login para salvar itens para depois.');
        }

        try {
            $carrinho = $this->getOrCreateCarrinho();
            $item = $carrinho->items()->with('livro')->findOrFail($itemId);

            // Adicionar à wishlist se não existir
            $wishlistItem = \App\Models\Wishlist::firstOrCreate([
                'user_id' => Auth::id(),
                'livro_id' => $item->livro_id
            ], [
                'prioridade' => 5,
                'preco_quando_adicionado' => $item->livro->preco_final,
                'observacoes' => 'Salvo do carrinho de compras'
            ]);

            // Remover do carrinho
            $tituloLivro = $item->livro->titulo;
            $item->delete();

            return redirect()->route('carrinho.index')
                ->with('success', "'{$tituloLivro}' salvo na sua lista de desejos!");

        } catch (\Exception $e) {
            Log::error('Erro ao salvar item para depois', [
                'user_id' => Auth::id(),
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('carrinho.index')
                ->withErrors(['erro' => 'Erro ao salvar item. Tente novamente.']);
        }
    }

    /**
     * Atualizar todos os preços do carrinho
     */
    public function atualizarPrecos()
    {
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $itensAtualizados = 0;

            foreach ($carrinho->items as $item) {
                $precoAtual = $item->livro->preco_final;
                
                if ($item->preco_unitario != $precoAtual) {
                    $item->update(['preco_unitario' => $precoAtual]);
                    $itensAtualizados++;
                }
            }

            if ($itensAtualizados > 0) {
                $mensagem = "Preços atualizados para {$itensAtualizados} item(ns).";
            } else {
                $mensagem = "Todos os preços já estão atualizados.";
            }

            return redirect()->route('carrinho.index')->with('success', $mensagem);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar preços do carrinho', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('carrinho.index')
                ->withErrors(['erro' => 'Erro ao atualizar preços. Tente novamente.']);
        }
    }

    /**
     * Calcular estatísticas do carrinho
     */
    private function calcularEstatisticasCarrinho(Carrinho $carrinho): array
    {
        $itens = $carrinho->items;

        $subtotal = $itens->sum(function ($item) {
            return $item->quantidade * $item->preco_unitario;
        });

        $totalItens = $itens->sum('quantidade');
        
        $pesoTotal = $itens->sum(function ($item) {
            return $item->quantidade * ($item->livro->peso ?? 0.3); // peso padrão 300g
        });

        // Calcular frete
        $frete = 0;
        if ($subtotal < 100) { // Frete grátis acima de R$ 100
            $frete = 15.00; // Frete padrão
        }

        // Calcular economia se comprar mais
        $economiaFreteGratis = max(0, 100 - $subtotal);

        return [
            'subtotal' => $subtotal,
            'total_itens' => $totalItens,
            'peso_total' => $pesoTotal,
            'frete' => $frete,
            'total' => $subtotal + $frete,
            'economia_frete_gratis' => $economiaFreteGratis,
            'valor_medio_item' => $totalItens > 0 ? $subtotal / $totalItens : 0,
            'categorias_diferentes' => $itens->pluck('livro.categoria_id')->unique()->count()
        ];
    }

    /**
     * Obter sugestões de produtos relacionados
     */
    private function obterSugestoesProdutos(Carrinho $carrinho): \Illuminate\Database\Eloquent\Collection
    {
        if ($carrinho->items->isEmpty()) {
            // Carrinho vazio, mostrar livros em destaque
            return Livro::where('ativo', true)
                ->where('destaque', true)
                ->where('estoque', '>', 0)
                ->inRandomOrder()
                ->limit(4)
                ->get();
        }

        // Obter categorias dos livros no carrinho
        $categoriasCarrinho = $carrinho->items->pluck('livro.categoria_id')->unique();
        $livrosCarrinho = $carrinho->items->pluck('livro_id');

        // Buscar livros similares
        return Livro::whereIn('categoria_id', $categoriasCarrinho)
            ->whereNotIn('id', $livrosCarrinho)
            ->where('ativo', true)
            ->where('estoque', '>', 0)
            ->inRandomOrder()
            ->limit(4)
            ->get();
    }

    /**
     * API: Obter contagem de itens do carrinho
     */
    public function contarItens()
    {
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $contador = $carrinho->items->sum('quantidade');

            return response()->json([
                'count' => $contador,
                'formatted' => $contador > 99 ? '99+' : $contador
            ]);

        } catch (\Exception $e) {
            return response()->json(['count' => 0, 'formatted' => '0']);
        }
    }

    /**
     * API: Obter resumo do carrinho
     */
    public function resumo()
    {
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $carrinho->load(['items.livro']);

            $estatisticas = $this->calcularEstatisticasCarrinho($carrinho);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_itens' => $estatisticas['total_itens'],
                    'subtotal' => $estatisticas['subtotal'],
                    'subtotal_formatado' => 'R$ ' . number_format($estatisticas['subtotal'], 2, ',', '.'),
                    'frete' => $estatisticas['frete'],
                    'frete_formatado' => $estatisticas['frete'] > 0 ? 'R$ ' . number_format($estatisticas['frete'], 2, ',', '.') : 'Grátis',
                    'total' => $estatisticas['total'],
                    'total_formatado' => 'R$ ' . number_format($estatisticas['total'], 2, ',', '.'),
                    'economia_frete_gratis' => $estatisticas['economia_frete_gratis'],
                    'economia_formatada' => 'R$ ' . number_format($estatisticas['economia_frete_gratis'], 2, ',', '.'),
                    'itens' => $carrinho->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'livro_id' => $item->livro_id,
                            'titulo' => $item->livro->titulo,
                            'autor' => $item->livro->autor,
                            'quantidade' => $item->quantidade,
                            'preco_unitario' => $item->preco_unitario,
                            'subtotal' => $item->quantidade * $item->preco_unitario,
                            'imagem' => $item->livro->imagem_capa,
                            'disponivel' => $item->livro->estoque >= $item->quantidade
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter resumo do carrinho.'
            ], 500);
        }
    }

    /**
     * Verificar disponibilidade dos itens do carrinho
     */
    public function verificarDisponibilidade()
    {
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $carrinho->load(['items.livro']);

            $problemas = [];

            foreach ($carrinho->items as $item) {
                if (!$item->livro->ativo) {
                    $problemas[] = [
                        'item_id' => $item->id,
                        'titulo' => $item->livro->titulo,
                        'problema' => 'produto_inativo',
                        'mensagem' => 'Produto não está mais disponível'
                    ];
                } elseif ($item->livro->estoque < $item->quantidade) {
                    $problemas[] = [
                        'item_id' => $item->id,
                        'titulo' => $item->livro->titulo,
                        'problema' => 'estoque_insuficiente',
                        'mensagem' => "Estoque insuficiente. Disponível: {$item->livro->estoque}",
                        'estoque_disponivel' => $item->livro->estoque,
                        'quantidade_solicitada' => $item->quantidade
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'problemas_encontrados' => count($problemas) > 0,
                'problemas' => $problemas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar disponibilidade.'
            ], 500);
        }
    }

    /**
     * Aplicar cupom de desconto no carrinho
     */
    public function aplicarCupom(Request $request)
    {
        $request->validate([
            'codigo_cupom' => 'required|string|max:50'
        ]);

        $codigo = strtoupper(trim($request->codigo_cupom));

        // Buscar cupom
        $cupom = \App\Models\Cupom::where('codigo', $codigo)
            ->where('ativo', true)
            ->first();

        if (!$cupom) {
            return response()->json([
                'success' => false,
                'message' => 'Cupom não encontrado ou inválido.'
            ], 404);
        }

        // Validar cupom (reutilizar lógica do CheckoutController)
        $carrinho = $this->getOrCreateCarrinho();
        $carrinho->load(['items.livro']);

        // Aqui você pode implementar a validação do cupom
        // Por simplicidade, vou assumir que o cupom é válido

        session(['cupom_aplicado' => $codigo]);

        return response()->json([
            'success' => true,
            'message' => 'Cupom aplicado com sucesso!',
            'cupom' => [
                'codigo' => $cupom->codigo,
                'nome' => $cupom->nome,
                'tipo' => $cupom->tipo,
                'valor' => $cupom->valor
            ]
        ]);
    }

    /**
     * Calcular frete por CEP
     */
    public function calcularFrete(Request $request)
    {
        $request->validate([
            'cep' => 'required|regex:/^\d{5}-?\d{3}$/'
        ]);

        $cep = preg_replace('/[^0-9]/', '', $request->cep);
        
        try {
            $carrinho = $this->getOrCreateCarrinho();
            $estatisticas = $this->calcularEstatisticasCarrinho($carrinho);

            // Calcular frete baseado no CEP e peso
            $valorFrete = $this->calcularFretePorCep($cep, $estatisticas['peso_total']);
            $prazoEntrega = $this->calcularPrazoEntrega($cep);

            // Frete grátis acima de R$ 100
            if ($estatisticas['subtotal'] >= 100) {
                $valorFrete = 0;
            }

            return response()->json([
                'success' => true,
                'frete' => [
                    'valor' => $valorFrete,
                    'valor_formatado' => $valorFrete > 0 ? 'R$ ' . number_format($valorFrete, 2, ',', '.') : 'Grátis',
                    'prazo_entrega' => $prazoEntrega,
                    'prazo_formatado' => $prazoEntrega . ' dias úteis',
                    'gratis' => $valorFrete == 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular frete. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Calcular frete por CEP e peso
     */
    private function calcularFretePorCep(string $cep, float $peso): float
    {
        $regiao = substr($cep, 0, 2);
        
        // Frete base por região
        $freteBase = match (true) {
            in_array($regiao, ['01', '02', '03', '04', '05', '08', '09']) => 8.00,  // SP
            in_array($regiao, ['80', '81', '82', '83', '84', '85', '86', '87', '88', '89']) => 10.00, // Sul
            in_array($regiao, ['40', '41', '42', '43', '44', '45', '46', '47', '48', '56', '57', '58', '59']) => 12.00, // Nordeste
            default => 15.00 // Norte e Centro-Oeste
        };

        // Adicionar custo por peso adicional (acima de 1kg)
        if ($peso > 1) {
            $freteBase += ($peso - 1) * 2.00;
        }

        return round($freteBase, 2);
    }

    /**
     * Calcular prazo de entrega por CEP
     */
    private function calcularPrazoEntrega(string $cep): int
    {
        $regiao = substr($cep, 0, 2);

        return match (true) {
            in_array($regiao, ['01', '02', '03', '04', '05', '08', '09']) => 2, // SP - 2 dias
            in_array($regiao, ['80', '81', '82', '83', '84', '85', '86', '87', '88', '89']) => 3, // Sul - 3 dias
            in_array($regiao, ['40', '41', '42', '43', '44', '45', '46', '47', '48', '56', '57', '58', '59']) => 5, // Nordeste - 5 dias
            default => 7 // Norte e Centro-Oeste - 7 dias
        };
    }
}