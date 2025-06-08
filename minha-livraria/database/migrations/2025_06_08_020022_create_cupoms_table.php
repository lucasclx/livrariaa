<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cupoms', function (Blueprint $table) {
            $table->id();
            
            // Informações básicas do cupom
            $table->string('codigo', 50)->unique()->index();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            
            // Tipo e valor do desconto
            $table->enum('tipo', ['percentual', 'valor_fixo'])->default('percentual');
            $table->decimal('valor', 10, 2); // Valor do desconto (% ou R$)
            
            // Restrições de uso
            $table->decimal('valor_minimo_pedido', 10, 2)->nullable()->comment('Valor mínimo do pedido para usar o cupom');
            $table->decimal('valor_maximo_desconto', 10, 2)->nullable()->comment('Valor máximo de desconto (para % apenas)');
            
            // Limites de uso
            $table->integer('limite_uso_total')->nullable()->comment('Limite total de usos do cupom');
            $table->integer('limite_uso_usuario')->default(1)->comment('Limite de usos por usuário');
            $table->integer('total_usado')->default(0)->comment('Total de vezes que foi usado');
            
            // Validade
            $table->datetime('data_inicio')->nullable()->comment('Data de início da validade');
            $table->datetime('data_fim')->nullable()->comment('Data de fim da validade');
            
            // Restrições por categoria/produto
            $table->json('categorias_permitidas')->nullable()->comment('IDs das categorias que o cupom se aplica');
            $table->json('categorias_excluidas')->nullable()->comment('IDs das categorias que o cupom NÃO se aplica');
            $table->json('livros_permitidos')->nullable()->comment('IDs dos livros que o cupom se aplica');
            $table->json('livros_excluidos')->nullable()->comment('IDs dos livros que o cupom NÃO se aplica');
            
            // Status e controle
            $table->boolean('ativo')->default(true)->index();
            $table->boolean('primeira_compra_apenas')->default(false)->comment('Válido apenas para primeira compra');
            $table->boolean('publico')->default(false)->comment('Cupom aparece publicamente ou é secreto');
            
            // Metadados
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->json('configuracoes_extras')->nullable()->comment('Configurações extras em JSON');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para performance
            $table->index(['ativo', 'data_inicio', 'data_fim']);
            $table->index(['tipo', 'ativo']);
            $table->index('publico');
            
            // Foreign keys
            $table->foreign('criado_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupoms');
    }
};