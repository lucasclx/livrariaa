<?php

namespace App\Http\Controllers;

use App\Models\Carrinho;
use App\Models\Cupom;
use App\Mail\PedidoConfirmado;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CheckoutRequest;
use App\Actions\Pedidos\ProcessarPedidoAction;

class CheckoutController extends Controller
{
    /**
     * Aplicar middleware de autenticação
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Exibe a página de checkout com os itens do carrinho
     */
    public function index()
    {
        $carrinho = Carrinho::where('user_id', auth()->id())
            ->with(['items.livro.categoria'])
            ->first();

        // Verificar se o carrinho existe e não está vazio
        if (!$carrinho || $carrinho->items->isEmpty()) {
            return redirect()->route('livros.index')
                ->with('info', 'Seu carrinho está vazio. Adicione alguns livros para continuar.');
        }

        // Verificar disponibilidade dos itens
        $itensIndisponiveis = [];
        foreach ($carrinho->items as $item) {
            if (!$item->livro->ativo) {
                $itensIndisponiveis[] = $item->livro->titulo . ' (produto inativo)';
            } elseif ($item->livro->estoque < $item->quantidade) {
                $itensIndisponiveis[] = $item->livro->titulo . ' (estoque insuficiente)';
            }
        }

        if (!empty($itensIndisponiveis)) {
            return redirect()->route('carrinho.index')
                ->withErrors([
                    'itens_indisponiveis' => 'Os seguintes itens não estão mais disponíveis: ' . implode(', ', $itensIndisponiveis)
                ]);
        }

        // Calcular totais
        $subtotal = $carrinho->items->sum(function ($item) {
            return $item->quantidade * $item->livro->preco_final;
        });

        $frete = $this->calcularFrete($carrinho);
        $desconto = 0; // Será calculado se houver cupom aplicado

        // Verificar se há cupom aplicado na sessão
        $cupomAplicado = null;
        if (session()->has('cupom_aplicado')) {
            $cupomAplicado = Cupom::where('codigo', session('cupom_aplicado'))
                ->where('ativo', true)
                ->first();

            if ($cupomAplicado && $this->validarCupom($cupomAplicado, $carrinho)) {
                $desconto = $this->calcularDesconto($cupomAplicado, $subtotal);
            } else {
                // Cupom inválido, remover da sessão
                session()->forget('cupom_aplicado');
            }
        }

        $total = $subtotal + $frete - $desconto;

        // Dados do usuário para pré-preenchimento
        $usuario = auth()->user();
        $enderecoSalvo = $this->obterEnderecoSalvo($usuario);

        // Formas de pagamento disponíveis
        $formasPagamento = [
            'pix' => [
                'nome' => 'PIX',
                'descricao' => 'Aprovação instantânea',
                'desconto_adicional' => 0.05, // 5% de desconto
                'icone' => 'fas fa-qrcode'
            ],
            'cartao_credito' => [
                'nome' => 'Cartão de Crédito',
                'descricao' => 'Parcelamento em até 12x',
                'desconto_adicional' => 0,
                'icone' => 'fas fa-credit-card'
            ],
            'cartao_debito' => [
                'nome' => 'Cartão de Débito',
                'descricao' => 'Débito à vista',
                'desconto_adicional' => 0.02, // 2% de desconto
                'icone' => 'fas fa-credit-card'
            ],
            'boleto' => [
                'nome' => 'Boleto Bancário',
                'descricao' => 'Vencimento em 3 dias úteis',
                'desconto_adicional' => 0.03, // 3% de desconto
                'icone' => 'fas fa-barcode'
            ]
        ];

        $resumo = [
            'subtotal' => $subtotal,
            'frete' => $frete,
            'desconto' => $desconto,
            'total' => $total,
            'total_itens' => $carrinho->items->sum('quantidade')
        ];

        return view('checkout.index', compact(
            'carrinho',
            'resumo',
            'cupomAplicado',
            'enderecoSalvo',
            'formasPagamento'
        ));
    }

    /**
     * Processa a finalização da compra
     */
    public function processar(CheckoutRequest $request, ProcessarPedidoAction $action)
    {
        try {
            DB::beginTransaction();

            // Validar carrinho novamente
            $carrinho = Carrinho::where('user_id', auth()->id())
                ->with(['items.livro'])
                ->first();

            if (!$carrinho || $carrinho->items->isEmpty()) {
                return redirect()->route('carrinho.index')
                    ->withErrors(['erro' => 'Carrinho vazio ou inválido.']);
            }

            // Verificar estoque novamente (double-check)
            foreach ($carrinho->items as $item) {
                if ($item->livro->estoque < $item->quantidade) {
                    return redirect()->route('carrinho.index')
                        ->withErrors([
                            'erro' => "Produto '{$item->livro->titulo}' não possui estoque suficiente."
                        ]);
                }
            }

            // Aplicar cupom se válido
            $cupomAplicado = null;
            $valorDesconto = 0;
            
            if (session()->has('cupom_aplicado')) {
                $cupomAplicado = Cupom::where('codigo', session('cupom_aplicado'))->first();
                if ($cupomAplicado && $this->validarCupom($cupomAplicado, $carrinho)) {
                    $subtotal = $carrinho->items->sum(fn($item) => $item->quantidade * $item->livro->preco_final);
                    $valorDesconto = $this->calcularDesconto($cupomAplicado, $subtotal);
                }
            }

            // Aplicar desconto da forma de pagamento
            $descontoFormaPagamento = $this->calcularDescontoFormaPagamento($request->forma_pagamento);
            
            // Preparar dados para a action
            $dadosProcessamento = array_merge($request->validated(), [
                'cupom_id' => $cupomAplicado?->id,
                'valor_desconto' => $valorDesconto,
                'desconto_forma_pagamento' => $descontoFormaPagamento,
                'frete' => $this->calcularFrete($carrinho),
                'ip_cliente' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Processar pedido usando a Action
            $pedido = $action->execute(auth()->user(), $dadosProcessamento);

            // Incrementar uso do cupom se aplicável
            if ($cupomAplicado) {
                $cupomAplicado->increment('total_usado');
                session()->forget('cupom_aplicado');
            }

            // Salvar endereço do usuário para próximas compras
            $this->salvarEnderecoUsuario(auth()->user(), $request);

            DB::commit();

            // Enviar email de confirmação de forma assíncrona
            try {
                Mail::to(auth()->user()->email)
                    ->send(new PedidoConfirmado($pedido));

                // Log do envio de email
                Log::info('Email de confirmação enviado', [
                    'pedido_id' => $pedido->id,
                    'user_id' => auth()->id(),
                    'email' => auth()->user()->email
                ]);

            } catch (\Exception $e) {
                // Se falhar o envio do email, não falha o pedido
                Log::error('Erro ao enviar email de confirmação', [
                    'pedido_id' => $pedido->id,
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage()
                ]);
            }

            // Limpar dados temporários da sessão
            session()->forget(['cupom_aplicado', 'endereco_temp']);

            // Log de sucesso
            Log::info('Pedido processado com sucesso', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'user_id' => auth()->id(),
                'total' => $pedido->total,
                'forma_pagamento' => $pedido->forma_pagamento
            ]);

            // Redirecionar para página de sucesso
            return redirect()->route('pedidos.sucesso', $pedido->id)
                ->with('success', 'Pedido realizado com sucesso! Você receberá um email de confirmação em breve.');

        } catch (\App\Exceptions\EstoqueInsuficienteException $e) {
            DB::rollBack();
            
            return redirect()->route('carrinho.index')
                ->withErrors(['estoque' => $e->getMessage()]);

        } catch (\App\Exceptions\CupomInvalidoException $e) {
            DB::rollBack();
            session()->forget('cupom_aplicado');
            
            return redirect()->route('checkout.index')
                ->withErrors(['cupom' => $e->getMessage()]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao processar pedido', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token'])
            ]);

            return redirect()->route('checkout.index')
                ->withErrors(['erro' => 'Erro interno do sistema. Tente novamente ou entre em contato conosco.']);
        }
    }

    /**
     * Aplicar cupom de desconto
     */
    public function aplicarCupom(Request $request)
    {
        $request->validate([
            'codigo_cupom' => 'required|string|max:50'
        ]);

        $codigoCupom = strtoupper(trim($request->codigo_cupom));

        // Buscar cupom
        $cupom = Cupom::where('codigo', $codigoCupom)
            ->where('ativo', true)
            ->first();

        if (!$cupom) {
            return response()->json([
                'success' => false,
                'message' => 'Cupom não encontrado ou inválido.'
            ], 404);
        }

        // Obter carrinho
        $carrinho = Carrinho::where('user_id', auth()->id())
            ->with(['items.livro'])
            ->first();

        if (!$carrinho || $carrinho->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Carrinho vazio.'
            ], 400);
        }

        // Validar cupom
        $validacao = $this->validarCupom($cupom, $carrinho);
        if (!$validacao['valido']) {
            return response()->json([
                'success' => false,
                'message' => $validacao['mensagem']
            ], 400);
        }

        // Calcular desconto
        $subtotal = $carrinho->items->sum(fn($item) => $item->quantidade * $item->livro->preco_final);
        $valorDesconto = $this->calcularDesconto($cupom, $subtotal);

        // Salvar cupom na sessão
        session(['cupom_aplicado' => $codigoCupom]);

        return response()->json([
            'success' => true,
            'message' => 'Cupom aplicado com sucesso!',
            'desconto' => $valorDesconto,
            'desconto_formatado' => 'R$ ' . number_format($valorDesconto, 2, ',', '.'),
            'cupom' => [
                'codigo' => $cupom->codigo,
                'nome' => $cupom->nome,
                'tipo' => $cupom->tipo,
                'valor' => $cupom->valor
            ]
        ]);
    }

    /**
     * Remover cupom aplicado
     */
    public function removerCupom()
    {
        session()->forget('cupom_aplicado');

        return response()->json([
            'success' => true,
            'message' => 'Cupom removido com sucesso!'
        ]);
    }

    /**
     * Calcular frete baseado no carrinho
     */
    private function calcularFrete(Carrinho $carrinho): float
    {
        // Lógica de frete simplificada
        $pesoTotal = $carrinho->items->sum(function ($item) {
            return $item->quantidade * ($item->livro->peso ?? 0.3); // peso padrão 300g
        });

        $valorTotal = $carrinho->items->sum(function ($item) {
            return $item->quantidade * $item->livro->preco_final;
        });

        // Frete grátis acima de R$ 100
        if ($valorTotal >= 100) {
            return 0;
        }

        // Frete baseado no peso (R$ 5 + R$ 2 por kg adicional)
        $frete = 5.00;
        if ($pesoTotal > 1) {
            $frete += ($pesoTotal - 1) * 2.00;
        }

        return round($frete, 2);
    }

    /**
     * Validar cupom
     */
    private function validarCupom(Cupom $cupom, Carrinho $carrinho): array
    {
        // Verificar se está ativo
        if (!$cupom->ativo) {
            return ['valido' => false, 'mensagem' => 'Cupom inativo.'];
        }

        // Verificar validade por data
        if ($cupom->data_inicio && now() < $cupom->data_inicio) {
            return ['valido' => false, 'mensagem' => 'Cupom ainda não válido.'];
        }

        if ($cupom->data_fim && now() > $cupom->data_fim) {
            return ['valido' => false, 'mensagem' => 'Cupom expirado.'];
        }

        // Verificar limite de uso total
        if ($cupom->limite_uso_total && $cupom->total_usado >= $cupom->limite_uso_total) {
            return ['valido' => false, 'mensagem' => 'Cupom esgotado.'];
        }

        // Verificar limite de uso por usuário
        $usosUsuario = \DB::table('pedidos')
            ->where('user_id', auth()->id())
            ->where('cupom_id', $cupom->id)
            ->count();

        if ($usosUsuario >= $cupom->limite_uso_usuario) {
            return ['valido' => false, 'mensagem' => 'Você já utilizou este cupom o máximo de vezes permitido.'];
        }

        // Verificar valor mínimo do pedido
        $subtotal = $carrinho->items->sum(fn($item) => $item->quantidade * $item->livro->preco_final);
        
        if ($cupom->valor_minimo_pedido && $subtotal < $cupom->valor_minimo_pedido) {
            return [
                'valido' => false, 
                'mensagem' => 'Valor mínimo do pedido: R$ ' . number_format($cupom->valor_minimo_pedido, 2, ',', '.')
            ];
        }

        // Verificar se é apenas para primeira compra
        if ($cupom->primeira_compra_apenas) {
            $pedidosAnteriores = \DB::table('pedidos')
                ->where('user_id', auth()->id())
                ->whereNotIn('status', ['cancelado'])
                ->count();

            if ($pedidosAnteriores > 0) {
                return ['valido' => false, 'mensagem' => 'Cupom válido apenas para primeira compra.'];
            }
        }

        // Verificar categorias permitidas/excluídas
        if ($cupom->categorias_permitidas || $cupom->categorias_excluidas) {
            $categoriasCarrinho = $carrinho->items->pluck('livro.categoria_id')->unique();

            if ($cupom->categorias_permitidas) {
                $permitidas = json_decode($cupom->categorias_permitidas, true);
                if (!$categoriasCarrinho->intersect($permitidas)->count()) {
                    return ['valido' => false, 'mensagem' => 'Cupom não se aplica aos produtos do carrinho.'];
                }
            }

            if ($cupom->categorias_excluidas) {
                $excluidas = json_decode($cupom->categorias_excluidas, true);
                if ($categoriasCarrinho->intersect($excluidas)->count()) {
                    return ['valido' => false, 'mensagem' => 'Cupom não se aplica a alguns produtos do carrinho.'];
                }
            }
        }

        return ['valido' => true, 'mensagem' => ''];
    }

    /**
     * Calcular desconto do cupom
     */
    private function calcularDesconto(Cupom $cupom, float $subtotal): float
    {
        if ($cupom->tipo === 'percentual') {
            $desconto = $subtotal * ($cupom->valor / 100);
            
            // Aplicar valor máximo de desconto se definido
            if ($cupom->valor_maximo_desconto && $desconto > $cupom->valor_maximo_desconto) {
                $desconto = $cupom->valor_maximo_desconto;
            }
        } else {
            $desconto = $cupom->valor;
            
            // Desconto não pode ser maior que o subtotal
            if ($desconto > $subtotal) {
                $desconto = $subtotal;
            }
        }

        return round($desconto, 2);
    }

    /**
     * Calcular desconto da forma de pagamento
     */
    private function calcularDescontoFormaPagamento(string $formaPagamento): float
    {
        $descontos = [
            'pix' => 0.05,           // 5%
            'cartao_debito' => 0.02, // 2%
            'boleto' => 0.03,        // 3%
            'cartao_credito' => 0    // 0%
        ];

        return $descontos[$formaPagamento] ?? 0;
    }

    /**
     * Obter endereço salvo do usuário
     */
    private function obterEnderecoSalvo(User $usuario): ?array
    {
        // Buscar último endereço usado em pedidos
        $ultimoPedido = \App\Models\Pedido::where('user_id', $usuario->id)
            ->latest()
            ->first();

        if ($ultimoPedido) {
            return [
                'endereco_entrega' => $ultimoPedido->endereco_entrega,
                'cidade' => $ultimoPedido->cidade,
                'estado' => $ultimoPedido->estado,
                'cep' => $ultimoPedido->cep
            ];
        }

        return null;
    }

    /**
     * Salvar endereço do usuário para futuras compras
     */
    private function salvarEnderecoUsuario(User $usuario, CheckoutRequest $request): void
    {
        // Poderiam salvar em uma tabela de endereços do usuário
        // Por agora, o endereço ficará salvo apenas no último pedido
    }

    /**
     * Calcular CEP e tempo de entrega
     */
    public function calcularCep(Request $request)
    {
        $request->validate([
            'cep' => 'required|regex:/^\d{5}-?\d{3}$/'
        ]);

        $cep = preg_replace('/[^0-9]/', '', $request->cep);

        try {
            // Integração com API de CEP (ViaCEP)
            $response = Http::get("https://viacep.com.br/ws/{$cep}/json/");
            
            if (!$response->successful() || isset($response->json()['erro'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'CEP não encontrado.'
                ], 404);
            }

            $endereco = $response->json();

            // Calcular prazo de entrega baseado na região
            $prazoEntrega = $this->calcularPrazoEntrega($cep);
            $valorFrete = $this->calcularFretePorCep($cep);

            return response()->json([
                'success' => true,
                'endereco' => [
                    'logradouro' => $endereco['logradouro'],
                    'bairro' => $endereco['bairro'],
                    'cidade' => $endereco['localidade'],
                    'estado' => $endereco['uf']
                ],
                'frete' => [
                    'valor' => $valorFrete,
                    'valor_formatado' => 'R$ ' . number_format($valorFrete, 2, ',', '.'),
                    'prazo_entrega' => $prazoEntrega,
                    'prazo_formatado' => $prazoEntrega . ' dias úteis'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar CEP. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Calcular prazo de entrega por CEP
     */
    private function calcularPrazoEntrega(string $cep): int
    {
        // Lógica simplificada baseada na região
        $regiao = substr($cep, 0, 2);

        // Regiões Sul e Sudeste: 3-5 dias
        if (in_array($regiao, ['01', '02', '03', '04', '05', '08', '09', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89'])) {
            return 3;
        }

        // Nordeste: 5-7 dias
        if (in_array($regiao, ['40', '41', '42', '43', '44', '45', '46', '47', '48', '56', '57', '58', '59'])) {
            return 5;
        }

        // Norte e Centro-Oeste: 7-10 dias
        return 8;
    }

    /**
     * Calcular frete por CEP
     */
    private function calcularFretePorCep(string $cep): float
    {
        $regiao = substr($cep, 0, 2);

        // Frete baseado na região
        if (in_array($regiao, ['01', '02', '03', '04', '05', '08', '09'])) {
            return 10.00; // São Paulo
        }

        if (in_array($regiao, ['80', '81', '82', '83', '84', '85', '86', '87', '88', '89'])) {
            return 12.00; // Sul
        }

        if (in_array($regiao, ['40', '41', '42', '43', '44', '45', '46', '47', '48', '56', '57', '58', '59'])) {
            return 15.00; // Nordeste
        }

        return 20.00; // Norte e Centro-Oeste
    }
}