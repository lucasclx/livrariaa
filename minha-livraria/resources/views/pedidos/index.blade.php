@extends('layouts.app')

@section('title', 'Meus Pedidos - Minha Livraria')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">📦 Meus Pedidos</h1>
            <a href="{{ route('livros.index') }}" class="btn btn-outline-elegant">
                <i class="fas fa-plus"></i> Fazer Novo Pedido
            </a>
        </div>

        <!-- Estatísticas do usuário -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-number">{{ $estatisticas['total_pedidos'] }}</div>
                        <div class="stat-label">Total de Pedidos</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-number">R$ {{ number_format($estatisticas['total_gasto'], 2, ',', '.') }}</div>
                        <div class="stat-label">Total Gasto</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-number">{{ $estatisticas['pedidos_pendentes'] }}</div>
                        <div class="stat-label">Pedidos Pendentes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-number">
                            {{ $estatisticas['ultimo_pedido'] ? $estatisticas['ultimo_pedido']->diffForHumans() : 'Nunca' }}
                        </div>
                        <div class="stat-label">Último Pedido</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card filter-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('pedidos.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos os status</option>
                            <option value="pendente" {{ request('status') == 'pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="processando" {{ request('status') == 'processando' ? 'selected' : '' }}>Processando</option>
                            <option value="enviado" {{ request('status') == 'enviado' ? 'selected' : '' }}>Enviado</option>
                            <option value="entregue" {{ request('status') == 'entregue' ? 'selected' : '' }}>Entregue</option>
                            <option value="cancelado" {{ request('status') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="{{ request('data_inicio') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" id="data_fim" class="form-control" value="{{ request('data_fim') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="busca" class="form-label">Buscar</label>
                        <div class="input-group">
                            <input type="text" name="busca" id="busca" class="form-control" 
                                   placeholder="Número do pedido ou livro" value="{{ request('busca') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($pedidos->count() > 0)
            <!-- Lista de pedidos -->
            <div class="row">
                @foreach($pedidos as $pedido)
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">
                                        <i class="fas fa-receipt me-2"></i>
                                        Pedido #{{ $pedido->numero_pedido }}
                                    </h6>
                                    <small class="text-muted">{{ $pedido->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $pedido->status_formatted['classe'] }} fs-6">
                                        {{ $pedido->status_formatted['texto'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Informações do pedido -->
                                    <div class="col-md-8">
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Total:</strong> 
                                                <span class="text-success fs-5">R$ {{ number_format($pedido->total, 2, ',', '.') }}</span>
                                            </div>
                                            <div class="col-sm-6">
                                                <strong>Itens:</strong> {{ $pedido->total_itens }}
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-6">
                                                <strong>Pagamento:</strong> {{ $pedido->forma_pagamento_formatted }}
                                            </div>
                                            <div class="col-sm-6">
                                                @if($pedido->data_entrega)
                                                    <strong>Entrega:</strong> {{ $pedido->data_entrega->format('d/m/Y') }}
                                                @else
                                                    <strong>Previsão:</strong> 
                                                    <span class="text-muted">{{ $pedido->created_at->addDays(5)->format('d/m/Y') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Livros do pedido (preview) -->
                                        <div class="mb-3">
                                            <strong>Livros:</strong>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                @foreach($pedido->itens->take(3) as $item)
                                                    <div class="card border-0 bg-light" style="width: 120px;">
                                                        <img src="{{ $item->livro->imagem_capa ?? 'https://via.placeholder.com/80x120.png?text=Sem+Capa' }}" 
                                                             class="card-img-top" 
                                                             alt="{{ $item->livro->titulo }}"
                                                             style="height: 80px; object-fit: cover;">
                                                        <div class="card-body p-2">
                                                            <p class="card-text small mb-0" title="{{ $item->livro->titulo }}">
                                                                {{ Str::limit($item->livro->titulo, 15) }}
                                                            </p>
                                                            <small class="text-muted">Qtd: {{ $item->quantidade }}</small>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                @if($pedido->itens->count() > 3)
                                                    <div class="d-flex align-items-center justify-content-center bg-light rounded" 
                                                         style="width: 120px; height: 120px;">
                                                        <div class="text-center">
                                                            <i class="fas fa-plus-circle fa-2x text-muted"></i>
                                                            <br>
                                                            <small class="text-muted">+{{ $pedido->itens->count() - 3 }} mais</small>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ações do pedido -->
                                    <div class="col-md-4">
                                        <div class="d-grid gap-2">
                                            <a href="{{ route('pedidos.show', $pedido) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i> Ver Detalhes
                                            </a>
                                            
                                            @if(in_array($pedido->status, ['pendente', 'processando']))
                                                <button type="button" class="btn btn-outline-warning btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelarModal{{ $pedido->id }}">
                                                    <i class="fas fa-times"></i> Cancelar Pedido
                                                </button>
                                            @endif
                                            
                                            @if($pedido->status == 'entregue')
                                                <a href="{{ route('pedidos.avaliar', $pedido) }}" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-star"></i> Avaliar
                                                </a>
                                                <button type="button" class="btn btn-outline-info btn-sm"
                                                        onclick="repetirPedido({{ $pedido->id }})">
                                                    <i class="fas fa-redo"></i> Repetir Pedido
                                                </button>
                                            @endif
                                            
                                            @if(in_array($pedido->status, ['processando', 'enviado', 'entregue']))
                                                <a href="{{ route('pedidos.nota-fiscal', $pedido) }}" 
                                                   class="btn btn-outline-secondary btn-sm" 
                                                   target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Nota Fiscal
                                                </a>
                                            @endif
                                            
                                            <!-- Rastreamento para pedidos enviados -->
                                            @if($pedido->status == 'enviado')
                                                <button type="button" class="btn btn-outline-info btn-sm"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#rastreamentoModal{{ $pedido->id }}">
                                                    <i class="fas fa-truck"></i> Rastrear
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Cancelamento -->
                    @if(in_array($pedido->status, ['pendente', 'processando']))
                        <div class="modal fade" id="cancelarModal{{ $pedido->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Cancelar Pedido</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Tem certeza que deseja cancelar o pedido <strong>#{{ $pedido->numero_pedido }}</strong>?</p>
                                        <p class="text-muted small">Esta ação não pode ser desfeita. Os produtos voltarão ao estoque.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                                        <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-danger">Sim, Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Modal de Rastreamento -->
                    @if($pedido->status == 'enviado')
                        <div class="modal fade" id="rastreamentoModal{{ $pedido->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Rastreamento do Pedido</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Timeline de rastreamento -->
                                        <div class="timeline">
                                            <div class="timeline-item completed">
                                                <div class="timeline-marker bg-success"></div>
                                                <div class="timeline-content">
                                                    <h6>Pedido Confirmado</h6>
                                                    <p class="text-muted">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                                                </div>
                                            </div>
                                            <div class="timeline-item completed">
                                                <div class="timeline-marker bg-success"></div>
                                                <div class="timeline-content">
                                                    <h6>Produto Enviado</h6>
                                                    <p class="text-muted">{{ $pedido->updated_at->format('d/m/Y H:i') }}</p>
                                                </div>
                                            </div>
                                            <div class="timeline-item active">
                                                <div class="timeline-marker bg-primary"></div>
                                                <div class="timeline-content">
                                                    <h6>Em Trânsito</h6>
                                                    <p class="text-muted">Previsão de entrega: {{ $pedido->created_at->addDays(5)->format('d/m/Y') }}</p>
                                                </div>
                                            </div>
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-light"></div>
                                                <div class="timeline-content">
                                                    <h6>Entregue</h6>
                                                    <p class="text-muted">Aguardando entrega</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Paginação -->
            <div class="d-flex justify-content-center">
                {{ $pedidos->withQueryString()->links() }}
            </div>
        @else
            <!-- Estado vazio -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                </div>
                <h4>Você ainda não fez nenhum pedido</h4>
                <p class="text-muted mb-4">Explore nosso catálogo e encontre seus livros favoritos!</p>
                <a href="{{ route('livros.index') }}" class="btn btn-primary">
                    <i class="fas fa-book"></i> Ver Catálogo
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
/* Timeline customizada para rastreamento */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-item.completed .timeline-marker {
    box-shadow: 0 0 0 2px #28a745;
}

.timeline-item.active .timeline-marker {
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-content h6 {
    margin-bottom: 0.25rem;
}

.timeline-content p {
    margin-bottom: 0;
    font-size: 0.875rem;
}

/* Animação para cards de pedido */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

/* Estilo para preview de livros */
.card.border-0.bg-light {
    transition: all 0.2s ease;
}

.card.border-0.bg-light:hover {
    background-color: #f8f9fa !important;
    transform: scale(1.05);
}
</style>
@endpush

@push('scripts')
<script>
// Função para repetir pedido
function repetirPedido(pedidoId) {
    if (confirm('Deseja adicionar todos os itens deste pedido ao seu carrinho?')) {
        // Aqui você faria uma requisição AJAX para adicionar os itens
        fetch(`/pedidos/${pedidoId}/repetir`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Itens adicionados ao carrinho com sucesso!');
                // Redirecionar para o carrinho
                window.location.href = '/carrinho';
            } else {
                alert('Erro ao adicionar itens ao carrinho: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro ao processar solicitação');
            console.error('Erro:', error);
        });
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.classList.contains('show')) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    });
});

// Filtro inteligente - aplicar automaticamente em mudanças
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            if (this.value) {
                // Submit automático quando selecionar um status
                setTimeout(() => {
                    this.closest('form').submit();
                }, 300);
            }
        });
    }
});
</script>
@endpush