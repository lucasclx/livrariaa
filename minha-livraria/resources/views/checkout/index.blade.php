@extends('layouts.app')

@section('title', 'Finalizar Compra - Livraria Elegante')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">💳 Finalizar Compra</h1>
            <a href="{{ route('carrinho.index') }}" class="btn btn-outline-elegant">
                <i class="fas fa-arrow-left"></i> Voltar ao Carrinho
            </a>
        </div>

        <!-- Steps do checkout -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="text-center">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="small">Carrinho</div>
                            </div>
                            <div class="flex-grow-1 align-self-center">
                                <hr class="border-success">
                            </div>
                            <div class="text-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="small font-weight-bold">Checkout</div>
                            </div>
                            <div class="flex-grow-1 align-self-center">
                                <hr class="border-muted">
                            </div>
                            <div class="text-center">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="small">Confirmação</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('checkout.processar') }}" method="POST">
            @csrf
            <div class="row">
                <!-- Dados do cliente e entrega -->
                <div class="col-lg-8 mb-4">
                    <!-- Dados pessoais -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user"></i> Dados Pessoais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome_cliente" class="form-label">Nome Completo *</label>
                                    <input type="text" 
                                           class="form-control @error('nome_cliente') is-invalid @enderror" 
                                           id="nome_cliente" 
                                           name="nome_cliente" 
                                           value="{{ old('nome_cliente', auth()->user()->name ?? '') }}" 
                                           required>
                                    @error('nome_cliente')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email_cliente" class="form-label">E-mail *</label>
                                    <input type="email" 
                                           class="form-control @error('email_cliente') is-invalid @enderror" 
                                           id="email_cliente" 
                                           name="email_cliente" 
                                           value="{{ old('email_cliente', auth()->user()->email ?? '') }}" 
                                           required>
                                    @error('email_cliente')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telefone_cliente" class="form-label">Telefone *</label>
                                    <input type="tel" 
                                           class="form-control @error('telefone_cliente') is-invalid @enderror" 
                                           id="telefone_cliente" 
                                           name="telefone_cliente" 
                                           value="{{ old('telefone_cliente') }}" 
                                           placeholder="(11) 99999-9999"
                                           required>
                                    @error('telefone_cliente')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Endereço de entrega -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-truck"></i> Endereço de Entrega
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="endereco_entrega" class="form-label">Endereço Completo *</label>
                                <textarea class="form-control @error('endereco_entrega') is-invalid @enderror" 
                                          id="endereco_entrega" 
                                          name="endereco_entrega" 
                                          rows="3" 
                                          placeholder="Rua, número, complemento, bairro, cidade, estado, CEP"
                                          required>{{ old('endereco_entrega') }}</textarea>
                                @error('endereco_entrega')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    Inclua todas as informações necessárias para a entrega
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Forma de pagamento -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card"></i> Forma de Pagamento
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="forma_pagamento" id="cartao_credito" value="cartao_credito" {{ old('forma_pagamento') == 'cartao_credito' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="cartao_credito">
                                            <i class="fas fa-credit-card text-primary"></i>
                                            Cartão de Crédito
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="forma_pagamento" id="cartao_debito" value="cartao_debito" {{ old('forma_pagamento') == 'cartao_debito' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="cartao_debito">
                                            <i class="fas fa-credit-card text-success"></i>
                                            Cartão de Débito
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="forma_pagamento" id="pix" value="pix" {{ old('forma_pagamento') == 'pix' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="pix">
                                            <i class="fas fa-qrcode text-info"></i>
                                            PIX <span class="badge bg-success">Instantâneo</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="forma_pagamento" id="boleto" value="boleto" {{ old('forma_pagamento') == 'boleto' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="boleto">
                                            <i class="fas fa-barcode text-warning"></i>
                                            Boleto Bancário
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @error('forma_pagamento')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Resumo do pedido -->
                <div class="col-lg-4">
                    <div class="card position-sticky" style="top: 100px;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt"></i> Resumo do Pedido
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Itens -->
                            <div class="mb-3">
                                <h6 class="mb-2">Itens ({{ $carrinho->total_itens }})</h6>
                                @foreach($itens as $item)
                                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ Str::limit($item->livro->titulo, 30) }}</div>
                                            <div class="text-muted">{{ $item->quantidade }}x R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</div>
                                        </div>
                                        <div class="text-end">
                                            R$ {{ number_format($item->subtotal, 2, ',', '.') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <hr>
                            
                            <!-- Totais -->
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>R$ {{ number_format($carrinho->total, 2, ',', '.') }}</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frete:</span>
                                <span class="text-success">Grátis</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Desconto:</span>
                                <span>R$ 0,00</span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong class="fs-5">Total:</strong>
                                <strong class="text-success fs-5">
                                    R$ {{ number_format($carrinho->total, 2, ',', '.') }}
                                </strong>
                            </div>
                            
                            <!-- Botão finalizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-lock"></i> Finalizar Pedido
                                </button>
                            </div>
                        </div>
                        
                        <!-- Garantias -->
                        <div class="card-footer bg-light">
                            <div class="text-center">
                                <div class="row">
                                    <div class="col-4">
                                        <i class="fas fa-shield-alt text-success"></i>
                                        <div class="small">Compra Segura</div>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-truck text-success"></i>
                                        <div class="small">Frete Grátis</div>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-undo text-success"></i>
                                        <div class="small">7 dias p/ trocar</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Máscara para telefone
    document.getElementById('telefone_cliente').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{5})(\d{4})$/, '$1-$2');
        e.target.value = value;
    });
    
    // Validação do formulário
    document.querySelector('form').addEventListener('submit', function(e) {
        const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked');
        
        if (!formaPagamento) {
            e.preventDefault();
            alert('Por favor, selecione uma forma de pagamento.');
            return;
        }
        
        // Loading button
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        submitBtn.disabled = true;
        
        // Confirmação
        if (!confirm('Confirma a finalização do pedido?')) {
            e.preventDefault();
            submitBtn.innerHTML = '<i class="fas fa-lock"></i> Finalizar Pedido';
            submitBtn.disabled = false;
        }
    });
    
    // Auto-select primeira forma de pagamento se nenhuma estiver selecionada
    document.addEventListener('DOMContentLoaded', function() {
        const formaPagamentos = document.querySelectorAll('input[name="forma_pagamento"]');
        const algumSelecionado = Array.from(formaPagamentos).some(radio => radio.checked);
        
        if (!algumSelecionado && formaPagamentos.length > 0) {
            formaPagamentos[0].checked = true;
        }
    });
</script>
@endpush