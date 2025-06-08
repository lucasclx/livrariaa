@extends('layouts.app')

@section('title', 'Finalizar Compra - Minha Livraria')

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
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="small text-success fw-bold">Carrinho</div>
                            </div>
                            <div class="flex-grow-1 align-self-center px-2">
                                <hr class="border-success border-2">
                            </div>
                            <div class="text-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="small text-primary fw-bold">Checkout</div>
                            </div>
                            <div class="flex-grow-1 align-self-center px-2">
                                <hr class="border-muted">
                            </div>
                            <div class="text-center">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="small text-muted">Confirmação</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Atenção!</h6>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('checkout.processar') }}" method="POST" id="checkout-form">
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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-truck"></i> Endereço de Entrega
                            </h5>
                            @if($enderecoSalvo)
                                <button type="button" class="btn btn-sm btn-outline-primary" id="usar-endereco-salvo">
                                    <i class="fas fa-undo"></i> Usar Último Endereço
                                </button>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="cep" class="form-label">CEP *</label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control @error('cep') is-invalid @enderror" 
                                               id="cep" 
                                               name="cep" 
                                               value="{{ old('cep', $enderecoSalvo['cep'] ?? '') }}" 
                                               placeholder="12345-678"
                                               required>
                                        <button type="button" class="btn btn-outline-secondary" id="buscar-cep">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        @error('cep')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">
                                        <small id="cep-loading" class="text-muted" style="display: none;">
                                            <i class="fas fa-spinner fa-spin"></i> Buscando CEP...
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-8 mb-3">
                                    <label for="endereco_entrega" class="form-label">Endereço *</label>
                                    <input type="text" 
                                           class="form-control @error('endereco_entrega') is-invalid @enderror" 
                                           id="endereco_entrega" 
                                           name="endereco_entrega" 
                                           value="{{ old('endereco_entrega', $enderecoSalvo['endereco_entrega'] ?? '') }}" 
                                           placeholder="Rua, número, complemento"
                                           required>
                                    @error('endereco_entrega')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cidade" class="form-label">Cidade *</label>
                                    <input type="text" 
                                           class="form-control @error('cidade') is-invalid @enderror" 
                                           id="cidade" 
                                           name="cidade" 
                                           value="{{ old('cidade', $enderecoSalvo['cidade'] ?? '') }}" 
                                           required>
                                    @error('cidade')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <select class="form-select @error('estado') is-invalid @enderror" 
                                            id="estado" 
                                            name="estado" 
                                            required>
                                        <option value="">Selecione o estado</option>
                                        <option value="AC" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'AC' ? 'selected' : '' }}>Acre</option>
                                        <option value="AL" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'AL' ? 'selected' : '' }}>Alagoas</option>
                                        <option value="AP" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'AP' ? 'selected' : '' }}>Amapá</option>
                                        <option value="AM" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'AM' ? 'selected' : '' }}>Amazonas</option>
                                        <option value="BA" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'BA' ? 'selected' : '' }}>Bahia</option>
                                        <option value="CE" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'CE' ? 'selected' : '' }}>Ceará</option>
                                        <option value="DF" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'DF' ? 'selected' : '' }}>Distrito Federal</option>
                                        <option value="ES" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'ES' ? 'selected' : '' }}>Espírito Santo</option>
                                        <option value="GO" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'GO' ? 'selected' : '' }}>Goiás</option>
                                        <option value="MA" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'MA' ? 'selected' : '' }}>Maranhão</option>
                                        <option value="MT" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'MT' ? 'selected' : '' }}>Mato Grosso</option>
                                        <option value="MS" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'MS' ? 'selected' : '' }}>Mato Grosso do Sul</option>
                                        <option value="MG" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'MG' ? 'selected' : '' }}>Minas Gerais</option>
                                        <option value="PA" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'PA' ? 'selected' : '' }}>Pará</option>
                                        <option value="PB" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'PB' ? 'selected' : '' }}>Paraíba</option>
                                        <option value="PR" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'PR' ? 'selected' : '' }}>Paraná</option>
                                        <option value="PE" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'PE' ? 'selected' : '' }}>Pernambuco</option>
                                        <option value="PI" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'PI' ? 'selected' : '' }}>Piauí</option>
                                        <option value="RJ" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'RJ' ? 'selected' : '' }}>Rio de Janeiro</option>
                                        <option value="RN" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'RN' ? 'selected' : '' }}>Rio Grande do Norte</option>
                                        <option value="RS" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'RS' ? 'selected' : '' }}>Rio Grande do Sul</option>
                                        <option value="RO" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'RO' ? 'selected' : '' }}>Rondônia</option>
                                        <option value="RR" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'RR' ? 'selected' : '' }}>Roraima</option>
                                        <option value="SC" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'SC' ? 'selected' : '' }}>Santa Catarina</option>
                                        <option value="SP" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'SP' ? 'selected' : '' }}>São Paulo</option>
                                        <option value="SE" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'SE' ? 'selected' : '' }}>Sergipe</option>
                                        <option value="TO" {{ old('estado', $enderecoSalvo['estado'] ?? '') == 'TO' ? 'selected' : '' }}>Tocantins</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                @foreach($formasPagamento as $key => $forma)
                                    <div class="col-md-6 mb-3">
                                        <div class="card payment-option {{ old('forma_pagamento') == $key ? 'border-primary' : '' }}" data-payment="{{ $key }}">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="radio" 
                                                           name="forma_pagamento" 
                                                           id="forma_{{ $key }}" 
                                                           value="{{ $key }}" 
                                                           {{ old('forma_pagamento') == $key ? 'checked' : '' }}
                                                           data-desconto="{{ $forma['desconto_adicional'] }}">
                                                    <label class="form-check-label d-flex align-items-center" for="forma_{{ $key }}">
                                                        <div class="me-3">
                                                            <i class="{{ $forma['icone'] }} fa-2x text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">{{ $forma['nome'] }}</h6>
                                                            <small class="text-muted">{{ $forma['descricao'] }}</small>
                                                            @if($forma['desconto_adicional'] > 0)
                                                                <div class="badge bg-success mt-1">
                                                                    {{ number_format($forma['desconto_adicional'] * 100) }}% OFF
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('forma_pagamento')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Cupom de desconto -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-tag"></i> Cupom de Desconto
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($cupomAplicado)
                                <div class="alert alert-success d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>{{ $cupomAplicado->nome }}</strong> aplicado com sucesso!
                                        <br><small>Código: {{ $cupomAplicado->codigo }}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="remover-cupom">
                                        <i class="fas fa-times"></i> Remover
                                    </button>
                                </div>
                            @else
                                <div id="cupom-form">
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               id="codigo_cupom" 
                                               placeholder="Digite o código do cupom">
                                        <button type="button" class="btn btn-outline-primary" id="aplicar-cupom">
                                            <i class="fas fa-tag"></i> Aplicar
                                        </button>
                                    </div>
                                    <div id="cupom-message" class="mt-2"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-comment"></i> Observações (Opcional)
                            </h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" 
                                      name="observacoes" 
                                      rows="3" 
                                      placeholder="Instruções especiais para entrega, referências, etc.">{{ old('observacoes') }}</textarea>
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
                                <h6 class="mb-2">Itens ({{ $resumo['total_itens'] }})</h6>
                                <div class="max-height-200 overflow-auto">
                                    @foreach($carrinho->items as $item)
                                        <div class="d-flex justify-content-between align-items-start mb-2 small">
                                            <div class="flex-grow-1 me-2">
                                                <div class="fw-bold">{{ Str::limit($item->livro->titulo, 30) }}</div>
                                                <div class="text-muted">
                                                    {{ $item->quantidade }}x R$ {{ number_format($item->livro->preco_final, 2, ',', '.') }}
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                R$ {{ number_format($item->quantidade * $item->livro->preco_final, 2, ',', '.') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Totais -->
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal-valor">R$ {{ number_format($resumo['subtotal'], 2, ',', '.') }}</span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frete:</span>
                                <span id="frete-valor" class="{{ $resumo['frete'] == 0 ? 'text-success' : '' }}">
                                    {{ $resumo['frete'] == 0 ? 'Grátis' : 'R$ ' . number_format($resumo['frete'], 2, ',', '.') }}
                                </span>
                            </div>
                            
                            @if($resumo['desconto'] > 0)
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Desconto:</span>
                                    <span id="desconto-valor">- R$ {{ number_format($resumo['desconto'], 2, ',', '.') }}</span>
                                </div>
                            @endif
                            
                            <div class="d-flex justify-content-between mb-2" id="desconto-forma-pagamento" style="display: none !important;">
                                <span class="text-success">Desconto pagamento:</span>
                                <span class="text-success" id="desconto-pagamento-valor">- R$ 0,00</span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong class="fs-5">Total:</strong>
                                <strong class="text-success fs-5" id="total-valor">
                                    R$ {{ number_format($resumo['total'], 2, ',', '.') }}
                                </strong>
                            </div>
                            
                            <!-- Botão finalizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="btn-finalizar">
                                    <i class="fas fa-lock"></i> Finalizar Pedido
                                </button>
                            </div>

                            <!-- Informações sobre frete grátis -->
                            @if($resumo['subtotal'] < 100)
                                <div class="alert alert-info mt-3 small">
                                    <i class="fas fa-truck"></i>
                                    Falta apenas <strong>R$ {{ number_format(100 - $resumo['subtotal'], 2, ',', '.') }}</strong> 
                                    para ganhar <strong>frete grátis!</strong>
                                </div>
                            @endif
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
                                        <div class="small">Entrega Rápida</div>
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

@if($enderecoSalvo)
    <!-- Dados do endereço salvo (hidden) -->
    <script>
        const enderecoSalvo = @json($enderecoSalvo);
    </script>
@endif
@endsection

@push('styles')
<style>
.payment-option {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #dee2e6;
}

.payment-option:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.payment-option.border-primary {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.05);
}

.max-height-200 {
    max-height: 200px;
}

.form-check-input:checked ~ .form-check-label {
    color: #0d6efd;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    color: white;
    font-size: 2rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Máscara para telefone
    const telefoneInput = document.getElementById('telefone_cliente');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d{4})$/, '$1-$2');
            e.target.value = value;
        });
    }

    // Máscara para CEP
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d{3})$/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // Buscar CEP
    document.getElementById('buscar-cep').addEventListener('click', function() {
        const cep = document.getElementById('cep').value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            alert('CEP deve ter 8 dígitos');
            return;
        }
        
        const loading = document.getElementById('cep-loading');
        loading.style.display = 'block';
        
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    alert('CEP não encontrado');
                    return;
                }
                
                document.getElementById('endereco_entrega').value = data.logradouro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('estado').value = data.uf || '';
                
                // Calcular frete
                calcularFrete(cep);
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao buscar CEP');
            })
            .finally(() => {
                loading.style.display = 'none';
            });
    });
    
    // Usar endereço salvo
    const btnEnderecoSalvo = document.getElementById('usar-endereco-salvo');
    if (btnEnderecoSalvo && typeof enderecoSalvo !== 'undefined') {
        btnEnderecoSalvo.addEventListener('click', function() {
            document.getElementById('cep').value = enderecoSalvo.cep || '';
            document.getElementById('endereco_entrega').value = enderecoSalvo.endereco_entrega || '';
            document.getElementById('cidade').value = enderecoSalvo.cidade || '';
            document.getElementById('estado').value = enderecoSalvo.estado || '';
        });
    }
    
    // Seleção de forma de pagamento
    document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Remover seleção anterior
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('border-primary');
                });
                
                // Adicionar seleção atual
                this.classList.add('border-primary');
                
                // Calcular desconto da forma de pagamento
                calcularDescontoFormaPagamento();
            }
        });
    });
    
    // Aplicar cupom
    document.getElementById('aplicar-cupom').addEventListener('click', function() {
        const codigo = document.getElementById('codigo_cupom').value.trim();
        const messageDiv = document.getElementById('cupom-message');
        
        if (!codigo) {
            messageDiv.innerHTML = '<div class="text-danger small">Digite um código de cupom</div>';
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aplicando...';
        
        fetch('{{ route("checkout.aplicar-cupom") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                codigo_cupom: codigo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<div class="text-success small">' + data.message + '</div>';
                // Recarregar página para atualizar resumo
                setTimeout(() => location.reload(), 1000);
            } else {
                messageDiv.innerHTML = '<div class="text-danger small">' + data.message + '</div>';
            }
        })
        .catch(error => {
            messageDiv.innerHTML = '<div class="text-danger small">Erro ao aplicar cupom</div>';
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-tag"></i> Aplicar';
        });
    });
    
    // Remover cupom
    const btnRemoverCupom = document.getElementById('remover-cupom');
    if (btnRemoverCupom) {
        btnRemoverCupom.addEventListener('click', function() {
            if (confirm('Deseja remover o cupom aplicado?')) {
                fetch('{{ route("checkout.remover-cupom") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        });
    }
    
    // Validação do formulário
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked');
        
        if (!formaPagamento) {
            e.preventDefault();
            alert('Por favor, selecione uma forma de pagamento.');
            return;
        }
        
        // Mostrar loading
        const submitBtn = document.getElementById('btn-finalizar');
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><br>Processando pedido...</div>';
        document.body.appendChild(loadingOverlay);
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        
        // Confirmação final
        if (!confirm('Confirma a finalização do pedido?')) {
            e.preventDefault();
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock"></i> Finalizar Pedido';
            document.body.removeChild(loadingOverlay);
        }
    });
    
    // Auto-select primeira forma de pagamento se nenhuma estiver selecionada
    const formaPagamentos = document.querySelectorAll('input[name="forma_pagamento"]');
    const algumSelecionado = Array.from(formaPagamentos).some(radio => radio.checked);
    
    if (!algumSelecionado && formaPagamentos.length > 0) {
        formaPagamentos[0].checked = true;
        formaPagamentos[0].closest('.payment-option').classList.add('border-primary');
    }
});

// Função para calcular frete
function calcularFrete(cep) {
    // Implementar lógica de cálculo de frete
    console.log('Calculando frete para CEP:', cep);
}

// Função para calcular desconto da forma de pagamento
function calcularDescontoFormaPagamento() {
    const formaSelecionada = document.querySelector('input[name="forma_pagamento"]:checked');
    
    if (formaSelecionada) {
        const desconto = parseFloat(formaSelecionada.dataset.desconto || 0);
        const subtotal = {{ $resumo['subtotal'] }};
        const valorDesconto = subtotal * desconto;
        
        const divDesconto = document.getElementById('desconto-forma-pagamento');
        const spanValor = document.getElementById('desconto-pagamento-valor');
        
        if (valorDesconto > 0) {
            divDesconto.style.display = 'flex';
            spanValor.textContent = '- R$ ' + valorDesconto.toFixed(2).replace('.', ',');
        } else {
            divDesconto.style.display = 'none';
        }
        
        // Atualizar total
        atualizarTotal();
    }
}

// Função para atualizar total
function atualizarTotal() {
    const subtotal = {{ $resumo['subtotal'] }};
    const frete = {{ $resumo['frete'] }};
    const desconto = {{ $resumo['desconto'] }};
    
    // Desconto da forma de pagamento
    const formaSelecionada = document.querySelector('input[name="forma_pagamento"]:checked');
    let descontoFormaPagamento = 0;
    
    if (formaSelecionada) {
        const percentualDesconto = parseFloat(formaSelecionada.dataset.desconto || 0);
        descontoFormaPagamento = subtotal * percentualDesconto;
    }
    
    const total = subtotal + frete - desconto - descontoFormaPagamento;
    
    document.getElementById('total-valor').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
}
</script>
@endpush