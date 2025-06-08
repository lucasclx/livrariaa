<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Livro;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Categoria;
use App\Models\Avaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Aplicar middleware de admin
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Exibir dashboard principal do admin
     */
    public function index(Request $request)
    {
        // Período de análise (padrão: últimos 30 dias)
        $periodo = $request->get('periodo', '30');
        $dataInicio = match($periodo) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            '365' => now()->subDays(365),
            default => now()->subDays(30)
        };

        // Estatísticas gerais
        $estatisticas = $this->obterEstatisticasGerais($dataInicio);
        
        // Gráficos e métricas
        $vendasPorDia = $this->obterVendasPorDia($dataInicio);
        $livrosMaisVendidos = $this->obterLivrosMaisVendidos($dataInicio, 10);
        $categoriasMaisVendidas = $this->obterCategoriasMaisVendidas($dataInicio, 5);
        $pedidosRecentes = $this->obterPedidosRecentes(10);
        $alertas = $this->obterAlertas();
        $metricas = $this->obterMetricas($dataInicio);
        
        return view('admin.dashboard', compact(
            'estatisticas',
            'vendasPorDia',
            'livrosMaisVendidos',
            'categoriasMaisVendidas',
            'pedidosRecentes',
            'alertas',
            'metricas',
            'periodo'
        ));
    }

    /**
     * Obter estatísticas gerais
     */
    private function obterEstatisticasGerais(Carbon $dataInicio): array
    {
        // Vendas do período
        $vendas = Pedido::where('created_at', '>=', $dataInicio)
            ->whereIn('status', ['processando', 'enviado', 'entregue'])
            ->selectRaw('
                COUNT(*) as total_pedidos,
                SUM(total) as receita_total,
                AVG(total) as ticket_medio
            ')
            ->first();

        // Vendas do período anterior (para comparação)
        $diasPeriodo = $dataInicio->diffInDays(now());
        $dataInicioAnterior = $dataInicio->copy()->subDays($diasPeriodo);
        
        $vendasAnteriores = Pedido::whereBetween('created_at', [$dataInicioAnterior, $dataInicio])
            ->whereIn('status', ['processando', 'enviado', 'entregue'])
            ->selectRaw('
                COUNT(*) as total_pedidos,
                SUM(total) as receita_total
            ')
            ->first();

        // Calcular variações percentuais
        $variacao_pedidos = $this->calcularVariacao(
            $vendas->total_pedidos ?? 0, 
            $vendasAnteriores->total_pedidos ?? 0
        );
        
        $variacao_receita = $this->calcularVariacao(
            $vendas->receita_total ?? 0, 
            $vendasAnteriores->receita_total ?? 0
        );

        // Outras métricas
        $totalUsuarios = User::where('is_admin', false)->count();
        $novosUsuarios = User::where('is_admin', false)
            ->where('created_at', '>=', $dataInicio)
            ->count();

        $totalLivros = Livro::where('ativo', true)->count();
        $livrosEstoqueBaixo = Livro::where('ativo', true)
            ->where('estoque', '<=', 5)
            ->count();

        $totalAvaliacoes = Avaliacao::where('status', 'aprovada')->count();
        $avaliacoesPendentes = Avaliacao::where('status', 'pendente')->count();

        return [
            'vendas' => [
                'total_pedidos' => $vendas->total_pedidos ?? 0,
                'receita_total' => $vendas->receita_total ?? 0,
                'ticket_medio' => $vendas->ticket_medio ?? 0,
                'variacao_pedidos' => $variacao_pedidos,
                'variacao_receita' => $variacao_receita,
            ],
            'usuarios' => [
                'total' => $totalUsuarios,
                'novos' => $novosUsuarios,
            ],
            'produtos' => [
                'total' => $totalLivros,
                'estoque_baixo' => $livrosEstoqueBaixo,
            ],
            'avaliacoes' => [
                'total' => $totalAvaliacoes,
                'pendentes' => $avaliacoesPendentes,
            ]
        ];
    }

    /**
     * Obter vendas por dia para gráfico
     */
    private function obterVendasPorDia(Carbon $dataInicio): array
    {
        $vendas = Pedido::where('created_at', '>=', $dataInicio)
            ->whereIn('status', ['processando', 'enviado', 'entregue'])
            ->selectRaw('
                DATE(created_at) as data,
                COUNT(*) as pedidos,
                SUM(total) as receita
            ')
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        // Preencher dias sem vendas
        $dados = [];
        $dataAtual = $dataInicio->copy()->startOfDay();
        $hoje = now()->startOfDay();

        while ($dataAtual <= $hoje) {
            $dataStr = $dataAtual->format('Y-m-d');
            $venda = $vendas->firstWhere('data', $dataStr);
            
            $dados[] = [
                'data' => $dataAtual->format('d/m'),
                'data_completa' => $dataStr,
                'pedidos' => $venda->pedidos ?? 0,
                'receita' => $venda->receita ?? 0,
            ];
            
            $dataAtual->addDay();
        }

        return $dados;
    }

    /**
     * Obter livros mais vendidos
     */
    private function obterLivrosMaisVendidos(Carbon $dataInicio, int $limite): array
    {
        return DB::table('pedido_items')
            ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
            ->join('livros', 'pedido_items.livro_id', '=', 'livros.id')
            ->where('pedidos.created_at', '>=', $dataInicio)
            ->whereIn('pedidos.status', ['processando', 'enviado', 'entregue'])
            ->selectRaw('
                livros.id,
                livros.titulo,
                livros.autor,
                livros.preco,
                livros.capa,
                SUM(pedido_items.quantidade) as total_vendido,
                SUM(pedido_items.quantidade * pedido_items.preco_unitario) as receita_total
            ')
            ->groupBy('livros.id', 'livros.titulo', 'livros.autor', 'livros.preco', 'livros.capa')
            ->orderBy('total_vendido', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    /**
     * Obter categorias mais vendidas
     */
    private function obterCategoriasMaisVendidas(Carbon $dataInicio, int $limite): array
    {
        return DB::table('pedido_items')
            ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
            ->join('livros', 'pedido_items.livro_id', '=', 'livros.id')
            ->join('categorias', 'livros.categoria_id', '=', 'categorias.id')
            ->where('pedidos.created_at', '>=', $dataInicio)
            ->whereIn('pedidos.status', ['processando', 'enviado', 'entregue'])
            ->selectRaw('
                categorias.id,
                categorias.nome,
                SUM(pedido_items.quantidade) as total_vendido,
                SUM(pedido_items.quantidade * pedido_items.preco_unitario) as receita_total,
                COUNT(DISTINCT pedido_items.pedido_id) as pedidos_diferentes
            ')
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderBy('total_vendido', 'desc')
            ->limit($limite)
            ->get()
            ->toArray();
    }

    /**
     * Obter pedidos recentes
     */
    private function obterPedidosRecentes(int $limite): array
    {
        return Pedido::with(['user', 'items'])
            ->latest()
            ->limit($limite)
            ->get()
            ->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->user->name ?? $pedido->nome_cliente,
                    'total' => $pedido->total,
                    'status' => $pedido->status,
                    'status_formatted' => $pedido->status_formatted,
                    'created_at' => $pedido->created_at,
                    'total_itens' => $pedido->items->sum('quantidade'),
                ];
            })
            ->toArray();
    }

    /**
     * Obter alertas e notificações importantes
     */
    private function obterAlertas(): array
    {
        $alertas = [];

        // Livros com estoque baixo
        $estoqueBaixo = Livro::where('ativo', true)
            ->where('estoque', '<=', 5)
            ->count();
        
        if ($estoqueBaixo > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Estoque Baixo',
                'mensagem' => "{$estoqueBaixo} livro(s) com estoque baixo",
                'icone' => 'fas fa-exclamation-triangle'
            ];
        }

        // Avaliações pendentes
        $avaliacoesPendentes = Avaliacao::where('status', 'pendente')->count();
        
        if ($avaliacoesPendentes > 0) {
            $alertas[] = [
                'tipo' => 'info',
                'titulo' => 'Avaliações Pendentes',
                'mensagem' => "{$avaliacoesPendentes} avaliação(ões) aguardando moderação",
                'icone' => 'fas fa-star'
            ];
        }

        // Pedidos pendentes
        $pedidosPendentes = Pedido::where('status', 'pendente')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        
        if ($pedidosPendentes > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Pedidos Pendentes',
                'mensagem' => "{$pedidosPendentes} pedido(s) pendente(s) nas últimas 24h",
                'icone' => 'fas fa-clock'
            ];
        }

        // Livros sem categoria
        $livrosSemCategoria = Livro::whereNull('categoria_id')
            ->where('ativo', true)
            ->count();
        
        if ($livrosSemCategoria > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Livros sem Categoria',
                'mensagem' => "{$livrosSemCategoria} livro(s) sem categoria definida",
                'icone' => 'fas fa-tags'
            ];
        }

        return $alertas;
    }

    /**
     * Obter métricas avançadas
     */
    private function obterMetricas(Carbon $dataInicio): array
    {
        // Taxa de conversão (pedidos / visitantes únicos)
        // Para este exemplo, vamos simular dados de visitantes
        $totalPedidos = Pedido::where('created_at', '>=', $dataInicio)->count();
        $visitantesUnicos = $totalPedidos * 10; // Simulação: 10 visitantes por pedido
        $taxaConversao = $visitantesUnicos > 0 ? ($totalPedidos / $visitantesUnicos) * 100 : 0;

        // Tempo médio entre pedidos por cliente
        $clientesComMultiplosPedidos = DB::table('pedidos')
            ->select('user_id')
            ->where('created_at', '>=', $dataInicio)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Taxa de produtos por pedido
        $itensPorPedido = DB::table('pedido_items')
            ->join('pedidos', 'pedido_items.pedido_id', '=', 'pedidos.id')
            ->where('pedidos.created_at', '>=', $dataInicio)
            ->selectRaw('AVG(pedido_items.quantidade) as media_itens')
            ->first();

        // Taxa de cancelamento
        $totalPedidosPeriodo = Pedido::where('created_at', '>=', $dataInicio)->count();
        $pedidosCancelados = Pedido::where('created_at', '>=', $dataInicio)
            ->where('status', 'cancelado')
            ->count();
        $taxaCancelamento = $totalPedidosPeriodo > 0 ? ($pedidosCancelados / $totalPedidosPeriodo) * 100 : 0;

        // Avaliação média dos produtos
        $avaliacaoMedia = Avaliacao::where('status', 'aprovada')
            ->where('created_at', '>=', $dataInicio)
            ->avg('rating') ?? 0;

        // Crescimento de usuários
        $novosUsuarios = User::where('created_at', '>=', $dataInicio)
            ->where('is_admin', false)
            ->count();

        return [
            'taxa_conversao' => round($taxaConversao, 2),
            'clientes_recorrentes' => $clientesComMultiplosPedidos,
            'itens_por_pedido' => round($itensPorPedido->media_itens ?? 0, 1),
            'taxa_cancelamento' => round($taxaCancelamento, 2),
            'avaliacao_media' => round($avaliacaoMedia, 1),
            'novos_usuarios' => $novosUsuarios,
            'visitantes_unicos' => $visitantesUnicos, // Simulado
        ];
    }

    /**
     * Calcular variação percentual
     */
    private function calcularVariacao(float $atual, float $anterior): float
    {
        if ($anterior == 0) {
            return $atual > 0 ? 100 : 0;
        }
        
        return round((($atual - $anterior) / $anterior) * 100, 1);
    }

    /**
     * Exportar dados do dashboard
     */
    public function exportar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:vendas,produtos,usuarios',
            'formato' => 'required|in:csv,xlsx,pdf',
            'periodo' => 'required|in:7,30,90,365'
        ]);

        $periodo = $request->get('periodo', '30');
        $dataInicio = now()->subDays($periodo);

        try {
            switch ($request->tipo) {
                case 'vendas':
                    return $this->exportarVendas($dataInicio, $request->formato);
                case 'produtos':
                    return $this->exportarProdutos($dataInicio, $request->formato);
                case 'usuarios':
                    return $this->exportarUsuarios($dataInicio, $request->formato);
                default:
                    return redirect()->back()->withErrors(['erro' => 'Tipo de exportação inválido.']);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao exportar dados do dashboard', [
                'tipo' => $request->tipo,
                'formato' => $request->formato,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors(['erro' => 'Erro ao gerar exportação.']);
        }
    }

    /**
     * Exportar dados de vendas
     */
    private function exportarVendas(Carbon $dataInicio, string $formato)
    {
        $vendas = DB::table('pedidos')
            ->join('users', 'pedidos.user_id', '=', 'users.id')
            ->where('pedidos.created_at', '>=', $dataInicio)
            ->whereIn('pedidos.status', ['processando', 'enviado', 'entregue'])
            ->select([
                'pedidos.numero_pedido',
                'users.name as cliente',
                'users.email',
                'pedidos.total',
                'pedidos.status',
                'pedidos.forma_pagamento',
                'pedidos.created_at'
            ])
            ->orderBy('pedidos.created_at', 'desc')
            ->get();

        $nomeArquivo = 'vendas_' . $dataInicio->format('Y-m-d') . '_' . now()->format('Y-m-d');

        if ($formato === 'csv') {
            return $this->gerarCSV($vendas, $nomeArquivo);
        } elseif ($formato === 'xlsx') {
            return $this->gerarExcel($vendas, $nomeArquivo);
        } else {
            return $this->gerarPDF($vendas, $nomeArquivo, 'Relatório de Vendas');
        }
    }

    /**
     * Exportar dados de produtos
     */
    private function exportarProdutos(Carbon $dataInicio, string $formato)
    {
        $produtos = DB::table('livros')
            ->leftJoin('categorias', 'livros.categoria_id', '=', 'categorias.id')
            ->leftJoin(
                DB::raw('(SELECT livro_id, SUM(quantidade) as total_vendido 
                         FROM pedido_items 
                         JOIN pedidos ON pedido_items.pedido_id = pedidos.id 
                         WHERE pedidos.created_at >= "' . $dataInicio->format('Y-m-d') . '"
                         AND pedidos.status IN ("processando", "enviado", "entregue")
                         GROUP BY livro_id) as vendas'),
                'livros.id', '=', 'vendas.livro_id'
            )
            ->select([
                'livros.titulo',
                'livros.autor',
                'livros.isbn',
                'categorias.nome as categoria',
                'livros.preco',
                'livros.estoque',
                'livros.ativo',
                DB::raw('COALESCE(vendas.total_vendido, 0) as total_vendido')
            ])
            ->orderBy('vendas.total_vendido', 'desc')
            ->get();

        $nomeArquivo = 'produtos_' . $dataInicio->format('Y-m-d') . '_' . now()->format('Y-m-d');

        if ($formato === 'csv') {
            return $this->gerarCSV($produtos, $nomeArquivo);
        } elseif ($formato === 'xlsx') {
            return $this->gerarExcel($produtos, $nomeArquivo);
        } else {
            return $this->gerarPDF($produtos, $nomeArquivo, 'Relatório de Produtos');
        }
    }

    /**
     * Exportar dados de usuários
     */
    private function exportarUsuarios(Carbon $dataInicio, string $formato)
    {
        $usuarios = DB::table('users')
            ->leftJoin(
                DB::raw('(SELECT user_id, COUNT(*) as total_pedidos, SUM(total) as total_gasto 
                         FROM pedidos 
                         WHERE created_at >= "' . $dataInicio->format('Y-m-d') . '"
                         AND status IN ("processando", "enviado", "entregue")
                         GROUP BY user_id) as pedidos'),
                'users.id', '=', 'pedidos.user_id'
            )
            ->where('users.is_admin', false)
            ->select([
                'users.name',
                'users.email',
                'users.created_at',
                DB::raw('COALESCE(pedidos.total_pedidos, 0) as total_pedidos'),
                DB::raw('COALESCE(pedidos.total_gasto, 0) as total_gasto')
            ])
            ->orderBy('pedidos.total_gasto', 'desc')
            ->get();

        $nomeArquivo = 'usuarios_' . $dataInicio->format('Y-m-d') . '_' . now()->format('Y-m-d');

        if ($formato === 'csv') {
            return $this->gerarCSV($usuarios, $nomeArquivo);
        } elseif ($formato === 'xlsx') {
            return $this->gerarExcel($usuarios, $nomeArquivo);
        } else {
            return $this->gerarPDF($usuarios, $nomeArquivo, 'Relatório de Usuários');
        }
    }

    /**
     * Gerar arquivo CSV
     */
    private function gerarCSV($dados, string $nomeArquivo)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$nomeArquivo}.csv",
        ];

        $callback = function() use ($dados) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            if ($dados->isNotEmpty()) {
                // Cabeçalho
                $primeiroItem = (array) $dados->first();
                fputcsv($file, array_keys($primeiroItem), ';');
                
                // Dados
                foreach ($dados as $item) {
                    fputcsv($file, (array) $item, ';');
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Gerar arquivo Excel (simulado como CSV por simplicidade)
     */
    private function gerarExcel($dados, string $nomeArquivo)
    {
        // Em uma implementação real, usaria PHPSpreadsheet ou similar
        return $this->gerarCSV($dados, $nomeArquivo);
    }

    /**
     * Gerar arquivo PDF
     */
    private function gerarPDF($dados, string $nomeArquivo, string $titulo)
    {
        // Em uma implementação real, usaria dompdf ou similar
        $html = view('admin.relatorios.pdf', compact('dados', 'titulo'))->render();
        
        // Por simplicidade, retornar como HTML
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', "inline; filename={$nomeArquivo}.html");
    }

    /**
     * Obter dados para gráficos via AJAX
     */
    public function dadosGrafico(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:vendas,categorias,usuarios,avaliacoes',
            'periodo' => 'required|in:7,30,90,365'
        ]);

        $periodo = $request->get('periodo', '30');
        $dataInicio = now()->subDays($periodo);

        try {
            switch ($request->tipo) {
                case 'vendas':
                    $dados = $this->obterVendasPorDia($dataInicio);
                    break;
                case 'categorias':
                    $dados = $this->obterCategoriasMaisVendidas($dataInicio, 10);
                    break;
                case 'usuarios':
                    $dados = $this->obterNovoUsuariosPorDia($dataInicio);
                    break;
                case 'avaliacoes':
                    $dados = $this->obterAvaliacoesPorDia($dataInicio);
                    break;
                default:
                    return response()->json(['error' => 'Tipo inválido'], 400);
            }

            return response()->json([
                'success' => true,
                'dados' => $dados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao obter dados do gráfico'
            ], 500);
        }
    }

    /**
     * Obter novos usuários por dia
     */
    private function obterNovoUsuariosPorDia(Carbon $dataInicio): array
    {
        $usuarios = User::where('created_at', '>=', $dataInicio)
            ->where('is_admin', false)
            ->selectRaw('DATE(created_at) as data, COUNT(*) as total')
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        $dados = [];
        $dataAtual = $dataInicio->copy()->startOfDay();
        $hoje = now()->startOfDay();

        while ($dataAtual <= $hoje) {
            $dataStr = $dataAtual->format('Y-m-d');
            $usuario = $usuarios->firstWhere('data', $dataStr);
            
            $dados[] = [
                'data' => $dataAtual->format('d/m'),
                'total' => $usuario->total ?? 0,
            ];
            
            $dataAtual->addDay();
        }

        return $dados;
    }

    /**
     * Obter avaliações por dia
     */
    private function obterAvaliacoesPorDia(Carbon $dataInicio): array
    {
        $avaliacoes = Avaliacao::where('created_at', '>=', $dataInicio)
            ->selectRaw('DATE(created_at) as data, COUNT(*) as total, AVG(rating) as media')
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        $dados = [];
        $dataAtual = $dataInicio->copy()->startOfDay();
        $hoje = now()->startOfDay();

        while ($dataAtual <= $hoje) {
            $dataStr = $dataAtual->format('Y-m-d');
            $avaliacao = $avaliacoes->firstWhere('data', $dataStr);
            
            $dados[] = [
                'data' => $dataAtual->format('d/m'),
                'total' => $avaliacao->total ?? 0,
                'media' => $avaliacao->media ? round($avaliacao->media, 1) : 0,
            ];
            
            $dataAtual->addDay();
        }

        return $dados;
    }

    /**
     * Atualizar configurações do dashboard
     */
    public function atualizarConfiguracoes(Request $request)
    {
        $request->validate([
            'alertas_email' => 'boolean',
            'periodo_padrao' => 'in:7,30,90,365',
            'items_por_pagina' => 'integer|min:10|max:100'
        ]);

        // Salvar configurações do usuário
        auth()->user()->configuracoes_dashboard = $request->only([
            'alertas_email',
            'periodo_padrao',
            'items_por_pagina'
        ]);
        auth()->user()->save();

        return response()->json([
            'success' => true,
            'message' => 'Configurações atualizadas com sucesso!'
        ]);
    }
}